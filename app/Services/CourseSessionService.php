<?php

namespace App\Services;

use App\Enums\SessionStatus;
use App\Enums\TrainerRole;
use App\Models\CourseSession;
use App\Models\SessionDay;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Writes a scheduled course session together with its days and planned trainers.
 * Hall conflicts and hall-change history are handled here as well.
 */
class CourseSessionService
{
    public function __construct(private readonly HallAvailabilityService $availability) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $createdBy = null): CourseSession
    {
        return DB::transaction(function () use ($data, $createdBy) {
            [$attributes, $days, $trainers] = $this->extract($data);

            $attributes['status'] = SessionStatus::Draft;
            $attributes['created_by'] = $createdBy;

            $session = CourseSession::create($attributes);

            $this->syncDays($session, $days, $createdBy);
            $this->syncTrainers($session, $trainers, $createdBy);

            return $session;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(CourseSession $session, array $data, ?int $actorId = null): CourseSession
    {
        return DB::transaction(function () use ($session, $data, $actorId) {
            [$attributes, $days, $trainers] = $this->extract($data);

            $session->update($attributes);

            $this->syncDays($session, $days, $actorId);
            $this->syncTrainers($session, $trainers, $actorId);

            return $session;
        });
    }

    public function delete(CourseSession $session): void
    {
        if ($blockers = $this->deletionBlockers($session)) {
            throw ValidationException::withMessages([
                'session' => 'لا يمكن حذف الجلسة لارتباطها بـ: '.implode('، ', $blockers).'.',
            ]);
        }

        DB::transaction(function () use ($session) {
            foreach ($session->sessionDays()->get() as $day) {
                $this->deleteDay($day);
            }

            $session->sessionTrainers()->delete();
            $session->delete();
        });
    }

    /**
     * Reasons preventing deletion (empty array means the session can be deleted).
     *
     * @return list<string>
     */
    public function deletionBlockers(CourseSession $session): array
    {
        $blockers = [];

        $relations = [
            'nominations' => 'ترشيحات',
            'enrollments' => 'تسجيلات',
            'exams' => 'اختبارات',
            'invoices' => 'فواتير',
            'trainerPayments' => 'مستحقات مدربين',
            'traineeWarnings' => 'إنذارات متدربين',
            'trainingKits' => 'حقائب تدريبية',
        ];

        foreach ($relations as $relation => $label) {
            if ($session->{$relation}()->exists()) {
                $blockers[] = $label;
            }
        }

        if ($session->evaluationParticipation()->exists() || $session->evaluationSubmissions()->exists()) {
            $blockers[] = 'تقييمات';
        }

        if ($session->sessionDays()->whereHas('traineeAttendance')->exists()
            || $session->sessionDays()->whereHas('trainerAttendance')->exists()) {
            $blockers[] = 'حضور مسجل';
        }

        return array_values(array_unique($blockers));
    }

    public function changeHall(SessionDay $day, ?int $hallId, ?string $reason, ?int $actorId): bool
    {
        $oldHallId = $day->hall_id ? (int) $day->hall_id : null;
        $newHallId = $hallId ?: null;

        if ($oldHallId === $newHallId) {
            return false;
        }

        if ($newHallId !== null) {
            $this->availability->assertAvailable(
                $newHallId,
                $day->day_date,
                $day->start_time,
                $day->end_time,
                $day->id,
                'hall_id',
                ownerSessionId: (int) $day->session_id,
            );
        }

        DB::transaction(function () use ($day, $oldHallId, $newHallId, $reason, $actorId) {
            $day->update(['hall_id' => $newHallId]);

            $day->hallChangeLog()->create([
                'old_hall_id' => $oldHallId,
                'new_hall_id' => $newHallId,
                'reason' => $reason,
                'changed_by' => $actorId,
                'changed_at' => now(),
            ]);
        });

        return true;
    }

    public function transitionStatus(CourseSession $session, SessionStatus $status): void
    {
        if (! in_array($status, $session->status->nextStatuses(), true)) {
            throw ValidationException::withMessages([
                'status' => "لا يمكن الانتقال من الحالة «{$session->status->label()}» إلى «{$status->label()}».",
            ]);
        }

        $session->update(['status' => $status]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: array<int, array<string, mixed>>, 2: array<int, array<string, mixed>>}
     */
    private function extract(array $data): array
    {
        $days = Arr::pull($data, 'days', []);
        $trainers = Arr::pull($data, 'trainers', []);

        // Status is controlled by the publishing endpoint, never by the session form.
        unset($data['status'], $data['created_by']);

        if (($data['online_url'] ?? null) === '') {
            $data['online_url'] = null;
        }

        return [
            $data,
            array_values(array_filter((array) $days, 'is_array')),
            array_values(array_filter((array) $trainers, 'is_array')),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncDays(CourseSession $session, array $rows, ?int $actorId): void
    {
        $startDate = $session->start_date->copy()->startOfDay();
        $endDate = $session->end_date->copy()->endOfDay();

        $normalized = [];
        $seenDayNumbers = [];

        foreach ($rows as $index => $row) {
            $date = filled($row['day_date'] ?? null) ? Carbon::parse($row['day_date'])->startOfDay() : null;
            $start = $this->normalizeTime($row['start_time'] ?? null);
            $end = $this->normalizeTime($row['end_time'] ?? null);

            if (! $date) {
                throw ValidationException::withMessages(["days.$index.day_date" => 'تاريخ اليوم مطلوب.']);
            }

            if ($date->lt($startDate) || $date->gt($endDate)) {
                throw ValidationException::withMessages([
                    "days.$index.day_date" => 'تاريخ اليوم يجب أن يكون ضمن فترة الجلسة.',
                ]);
            }

            if (! $start || ! $end) {
                throw ValidationException::withMessages([
                    "days.$index.start_time" => 'وقت البداية والنهاية مطلوبان بصيغة صحيحة.',
                ]);
            }

            if ($start >= $end) {
                throw ValidationException::withMessages([
                    "days.$index.end_time" => 'وقت النهاية يجب أن يكون بعد وقت البداية.',
                ]);
            }

            $dayNo = filled($row['day_no'] ?? null) ? (int) $row['day_no'] : $index + 1;

            if ($dayNo < 1) {
                $dayNo = $index + 1;
            }

            if (isset($seenDayNumbers[$dayNo])) {
                throw ValidationException::withMessages([
                    "days.$index.day_no" => 'رقم اليوم مستخدم في صف آخر.',
                ]);
            }

            $seenDayNumbers[$dayNo] = true;

            $normalized[] = [
                'id' => filled($row['id'] ?? null) ? (int) $row['id'] : null,
                'day_no' => $dayNo,
                'day_date' => $date->format('Y-m-d'),
                'start_time' => $start,
                'end_time' => $end,
                'hall_id' => filled($row['hall_id'] ?? null) ? (int) $row['hall_id'] : null,
                'online_url' => filled($row['online_url'] ?? null) ? (string) $row['online_url'] : null,
            ];
        }

        $this->availability->assertRowsAvailable($normalized, $session->id);

        $existingDays = $session->sessionDays()->get()->keyBy('id');

        $keptIds = collect($normalized)
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $existingDays->has($id))
            ->unique()
            ->values()
            ->all();

        // Remove days that are no longer part of the submitted schedule before
        // renumbering, so its original number is available in the error message.
        $removedDays = $existingDays->whereNotIn('id', $keptIds);

        foreach ($removedDays as $day) {
            if ($day->traineeAttendance()->exists() || $day->trainerAttendance()->exists()) {
                throw ValidationException::withMessages([
                    'days' => "لا يمكن حذف اليوم رقم {$day->day_no} لوجود حضور مسجل عليه.",
                ]);
            }

            $this->deleteDay($day);
        }

        // Shift remaining day numbers first so reordering or swapping cannot
        // violate the unique (session_id, day_no) index while updating.
        foreach ($existingDays->whereIn('id', $keptIds) as $existingDay) {
            $existingDay->update(['day_no' => (int) $existingDay->day_no + 1000]);
        }

        foreach ($normalized as $row) {
            $day = $row['id'] ? $existingDays->get($row['id']) : null;
            $isNew = $day === null;
            $oldHallId = $day?->hall_id;

            $attributes = Arr::only($row, [
                'day_no', 'day_date', 'start_time', 'end_time', 'hall_id', 'online_url',
            ]);

            if ($day) {
                $day->update($attributes);
            } else {
                $day = $session->sessionDays()->create($attributes);
            }

            if (! $isNew && (int) ($oldHallId ?? 0) !== (int) ($attributes['hall_id'] ?? 0)) {
                $day->hallChangeLog()->create([
                    'old_hall_id' => $oldHallId,
                    'new_hall_id' => $attributes['hall_id'],
                    'reason' => 'تحديث جدول الجلسة',
                    'changed_by' => $actorId,
                    'changed_at' => now(),
                ]);
            }
        }
    }

    private function deleteDay(SessionDay $day): void
    {
        $day->hallChangeLog()->delete();
        $day->sessionDayModules()->delete();
        $day->sessionDayTrainers()->delete();
        $day->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncTrainers(CourseSession $session, array $rows, ?int $actorId): void
    {
        $session->sessionTrainers()->delete();

        $seen = [];

        foreach ($rows as $row) {
            $trainerId = (int) ($row['trainer_id'] ?? 0);

            if ($trainerId <= 0 || isset($seen[$trainerId])) {
                continue;
            }

            $role = $row['planned_role'] ?? TrainerRole::Primary->value;

            $session->sessionTrainers()->create([
                'trainer_id' => $trainerId,
                'planned_role' => $role,
                'assigned_by' => $actorId,
            ]);

            $seen[$trainerId] = true;
        }
    }

    private function normalizeTime(mixed $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        $value = trim((string) $value);

        if (! preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $value)) {
            return null;
        }

        [$hours, $minutes, $seconds] = array_pad(explode(':', $value), 3, '00');

        return sprintf('%02d:%02d:%02d', (int) $hours, (int) $minutes, (int) $seconds);
    }
}
