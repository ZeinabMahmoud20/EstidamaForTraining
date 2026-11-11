<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\ExcuseStatus;
use App\Models\AttendanceExcuse;
use App\Models\CourseSession;
use App\Models\SessionDay;
use App\Models\TraineeAttendance;
use App\Models\TrainerAttendance;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Records trainee/trainer attendance for a session day and reviews excuses.
 */
class AttendanceService
{
    public function __construct(private readonly EnrollmentService $enrollments) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{trainees: int, trainers: int}
     */
    public function saveDay(CourseSession $session, SessionDay $day, array $data, User $actor): array
    {
        if ((int) $day->session_id !== (int) $session->id) {
            throw ValidationException::withMessages([
                'session_day_id' => 'اليوم المحدد لا ينتمي إلى هذه الجلسة.',
            ]);
        }

        return DB::transaction(function () use ($session, $day, $data, $actor) {
            $session->loadMissing(['enrollments.trainee', 'enrollments.traineeAttendance']);
            $day->loadMissing(['sessionDayTrainers', 'session.sessionTrainers']);

            $validEnrollmentIds = $session->enrollments
                ->whereNotIn('status', ['withdrawn', 'dismissed'])
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $validTrainerIds = $this->trainerIdsForDay($day);

            $traineeRows = (array) ($data['trainees'] ?? []);
            $trainerRows = (array) ($data['trainers'] ?? []);
            $recalculate = [];

            foreach ($traineeRows as $enrollmentId => $row) {
                if (! is_array($row) || blank($row['status'] ?? null)) {
                    continue;
                }

                $enrollmentId = (int) $enrollmentId;

                if (! in_array($enrollmentId, $validEnrollmentIds, true)) {
                    throw ValidationException::withMessages([
                        'trainees' => 'أحد المتدربين المحددين لا ينتمي إلى هذه الجلسة.',
                    ]);
                }

                $status = AttendanceStatus::from((string) $row['status']);
                $attendance = TraineeAttendance::firstOrNew([
                    'enrollment_id' => $enrollmentId,
                    'session_day_id' => $day->id,
                ]);

                $excuse = $this->resolveExcuse($attendance, $row, $actor, $status);
                [$checkIn, $checkOut] = $this->timesForStatus($day, $status, $row);

                $attendance->fill([
                    'status' => $status,
                    'late_minutes' => $status === AttendanceStatus::Late ? (int) ($row['late_minutes'] ?? 0) : null,
                    'notes' => $row['notes'] ?? null,
                    'excuse_id' => $excuse?->id,
                    'check_in_at' => $checkIn,
                    'check_in_by' => $checkIn ? $actor->id : null,
                    'check_out_at' => $checkOut,
                    'check_out_by' => $checkOut ? $actor->id : null,
                ])->save();

                $recalculate[] = $enrollmentId;
            }

            $trainerCount = 0;

            foreach ($trainerRows as $trainerId => $row) {
                if (! is_array($row) || blank($row['status'] ?? null)) {
                    continue;
                }

                $trainerId = (int) $trainerId;

                if (! in_array($trainerId, $validTrainerIds, true)) {
                    throw ValidationException::withMessages([
                        'trainers' => 'أحد المدربين المحددين غير معين على هذه الجلسة.',
                    ]);
                }

                $status = AttendanceStatus::from((string) $row['status']);
                $attendance = TrainerAttendance::firstOrNew([
                    'trainer_id' => $trainerId,
                    'session_day_id' => $day->id,
                ]);

                $excuse = $this->resolveExcuse($attendance, $row, $actor, $status);
                [$checkIn, $checkOut] = $this->timesForStatus($day, $status, $row);

                $attendance->fill([
                    'status' => $status,
                    'late_minutes' => $status === AttendanceStatus::Late ? (int) ($row['late_minutes'] ?? 0) : null,
                    'notes' => $row['notes'] ?? null,
                    'excuse_id' => $excuse?->id,
                    'check_in_at' => $checkIn,
                    'check_in_by' => $checkIn ? $actor->id : null,
                    'check_out_at' => $checkOut,
                    'check_out_by' => $checkOut ? $actor->id : null,
                ])->save();

                $trainerCount++;
            }

            foreach (array_unique($recalculate) as $enrollmentId) {
                $enrollment = $session->enrollments->firstWhere('id', $enrollmentId);

                if ($enrollment) {
                    $this->enrollments->recalculateAttendance($enrollment);
                }
            }

            return [
                'trainees' => count(array_unique($recalculate)),
                'trainers' => $trainerCount,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function reviewExcuse(AttendanceExcuse $excuse, array $data, User $actor): AttendanceExcuse
    {
        return DB::transaction(function () use ($excuse, $data, $actor) {
            $status = ExcuseStatus::from((string) $data['status']);

            $excuse->update([
                'status' => $status,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_notes' => $data['review_notes'] ?? null,
            ]);

            $targetStatus = $status === ExcuseStatus::Approved
                ? AttendanceStatus::AbsentExcused
                : AttendanceStatus::AbsentUnexcused;

            $excuse->traineeAttendance()->update([
                'status' => $targetStatus->value,
                'check_in_at' => null,
                'check_in_by' => null,
                'check_out_at' => null,
                'check_out_by' => null,
                'late_minutes' => null,
            ]);

            $excuse->trainerAttendance()->update([
                'status' => $targetStatus->value,
                'check_in_at' => null,
                'check_in_by' => null,
                'check_out_at' => null,
                'check_out_by' => null,
                'late_minutes' => null,
            ]);

            $excuse->loadMissing('traineeAttendance.enrollment');

            foreach ($excuse->traineeAttendance->pluck('enrollment')->filter()->unique('id') as $enrollment) {
                $this->enrollments->recalculateAttendance($enrollment);
            }

            return $excuse->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function resolveExcuse(
        TraineeAttendance|TrainerAttendance $attendance,
        array $row,
        User $actor,
        AttendanceStatus $status,
    ): ?AttendanceExcuse {
        if ($status !== AttendanceStatus::AbsentExcused) {
            return null;
        }

        $reason = trim((string) ($row['excuse_reason'] ?? $row['notes'] ?? ''));
        $existing = $attendance->exists ? $attendance->excuse : null;

        if ($reason === '') {
            if ($existing) {
                return $existing;
            }

            throw ValidationException::withMessages([
                'excuse_reason' => 'يجب إدخال سبب العذر عند اختيار حالة «غائب بعذر».',
            ]);
        }

        if ($existing && $existing->status === ExcuseStatus::Pending) {
            $existing->update(['reason' => $reason]);

            return $existing;
        }

        if ($existing && $existing->status === ExcuseStatus::Approved && $existing->reason === $reason) {
            return $existing;
        }

        return AttendanceExcuse::create([
            'reason' => $reason,
            'status' => ExcuseStatus::Pending,
            'submitted_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{0: Carbon|null, 1: Carbon|null}
     */
    private function timesForStatus(SessionDay $day, AttendanceStatus $status, array $row): array
    {
        if (! in_array($status, [AttendanceStatus::Present, AttendanceStatus::Late, AttendanceStatus::EarlyLeave], true)) {
            return [null, null];
        }

        $date = $day->day_date?->format('Y-m-d') ?? now()->toDateString();
        $start = Carbon::parse($date.' '.($day->start_time ?: '09:00:00'));
        $end = Carbon::parse($date.' '.($day->end_time ?: '12:00:00'));

        return match ($status) {
            AttendanceStatus::Present => [$start, null],
            AttendanceStatus::Late => [(clone $start)->addMinutes((int) ($row['late_minutes'] ?? 0)), null],
            AttendanceStatus::EarlyLeave => [$start, $end],
            default => [null, null],
        };
    }

    /**
     * @return list<int>
     */
    private function trainerIdsForDay(SessionDay $day): array
    {
        $dayTrainers = $day->sessionDayTrainers->pluck('trainer_id');

        if ($dayTrainers->isNotEmpty()) {
            return $dayTrainers->map(fn ($id) => (int) $id)->unique()->values()->all();
        }

        return $day->session->sessionTrainers->pluck('trainer_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
