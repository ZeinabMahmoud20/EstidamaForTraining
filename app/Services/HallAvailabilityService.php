<?php

namespace App\Services;

use App\Models\Hall;
use App\Models\HallBlock;
use App\Models\SessionDay;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Validates hall availability for session days.
 *
 * MySQL has no exclusion constraints, so double-booking is checked here:
 * - overlapping session days in the same hall,
 * - overlapping hall blocks (maintenance/reserved periods),
 * - overlaps between rows submitted together for one session.
 */
class HallAvailabilityService
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function assertRowsAvailable(array $rows, ?int $ignoreSessionId = null): void
    {
        $normalized = [];

        foreach ($rows as $index => $row) {
            $hallId = (int) ($row['hall_id'] ?? 0);

            if ($hallId <= 0) {
                continue;
            }

            $date = $this->normalizeDate($row['day_date'] ?? null);
            $start = $this->normalizeTime($row['start_time'] ?? null);
            $end = $this->normalizeTime($row['end_time'] ?? null);

            if ($date === null || $start === null || $end === null) {
                continue;
            }

            $normalized[$index] = [
                'hall_id' => $hallId,
                'date' => $date,
                'start' => $start,
                'end' => $end,
                'day_id' => filled($row['id'] ?? null) ? (int) $row['id'] : null,
            ];
        }

        $indexes = array_keys($normalized);

        foreach ($indexes as $position => $firstIndex) {
            foreach (array_slice($indexes, $position + 1) as $secondIndex) {
                $first = $normalized[$firstIndex];
                $second = $normalized[$secondIndex];

                if ($first['hall_id'] === $second['hall_id']
                    && $first['date'] === $second['date']
                    && $this->overlaps($first['start'], $first['end'], $second['start'], $second['end'])) {
                    throw ValidationException::withMessages([
                        "days.$secondIndex.hall_id" => 'يوجد تعارض بين صفوف الجدول: نفس القاعة والوقت مستخدمان في أكثر من يوم.',
                    ]);
                }
            }
        }

        foreach ($normalized as $index => $row) {
            $this->assertAvailable(
                $row['hall_id'],
                $row['date'],
                $row['start'],
                $row['end'],
                $row['day_id'],
                "days.$index.hall_id",
                $ignoreSessionId,
                $ignoreSessionId,
            );
        }
    }

    public function assertAvailable(
        int $hallId,
        mixed $date,
        ?string $start,
        ?string $end,
        ?int $ignoreSessionDayId = null,
        string $errorKey = 'hall_id',
        ?int $ignoreSessionId = null,
        ?int $ownerSessionId = null,
    ): void {
        $date = $this->normalizeDate($date);
        $start = $this->normalizeTime($start);
        $end = $this->normalizeTime($end);

        if ($date === null || $start === null || $end === null) {
            throw ValidationException::withMessages([$errorKey => 'تاريخ أو وقت اليوم غير صحيح.']);
        }

        if ($start >= $end) {
            throw ValidationException::withMessages([$errorKey => 'وقت النهاية يجب أن يكون بعد وقت البداية.']);
        }

        $hall = Hall::find($hallId);

        if (! $hall) {
            throw ValidationException::withMessages([$errorKey => 'القاعة المحددة غير موجودة.']);
        }

        if (! $hall->is_active && ! $this->isCurrentHall($hallId, $ignoreSessionDayId, $ownerSessionId)) {
            throw ValidationException::withMessages([$errorKey => 'القاعة المحددة غير نشطة ولا يمكن جدولتها.']);
        }

        $conflict = SessionDay::query()
            ->where('hall_id', $hallId)
            ->whereDate('day_date', $date)
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->when($ignoreSessionDayId, fn ($query) => $query->whereKeyNot($ignoreSessionDayId))
            ->when($ignoreSessionId, fn ($query) => $query->where('session_id', '!=', $ignoreSessionId))
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                $errorKey => "القاعة «{$hall->name}» محجوزة بجلسة أخرى في {$date} من {$this->short($start)} إلى {$this->short($end)}.",
            ]);
        }

        $blocked = HallBlock::query()
            ->where('hall_id', $hallId)
            ->where('start_at', '<', $date.' '.$end)
            ->where('end_at', '>', $date.' '.$start)
            ->exists();

        if ($blocked) {
            throw ValidationException::withMessages([
                $errorKey => "القاعة «{$hall->name}» محجوبة في {$date} من {$this->short($start)} إلى {$this->short($end)}.",
            ]);
        }
    }

    private function isCurrentHall(int $hallId, ?int $sessionDayId, ?int $ownerSessionId = null): bool
    {
        return $sessionDayId !== null
            && SessionDay::whereKey($sessionDayId)
                ->when($ownerSessionId, fn ($query) => $query->where('session_id', $ownerSessionId))
                ->where('hall_id', $hallId)
                ->exists();
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (! filled($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeTime(?string $value): ?string
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

    private function overlaps(string $firstStart, string $firstEnd, string $secondStart, string $secondEnd): bool
    {
        return $firstStart < $secondEnd && $secondStart < $firstEnd;
    }

    private function short(string $time): string
    {
        return substr($time, 0, 5);
    }
}
