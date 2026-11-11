<?php

namespace App\Services;

use App\Models\Hall;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Writes a hall together with its equipment quantities and guards deletion.
 */
class HallService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Hall
    {
        return DB::transaction(function () use ($data) {
            [$attributes, $equipment] = $this->extract($data);

            $hall = Hall::create($attributes);
            $this->syncEquipment($hall, $equipment);

            return $hall;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Hall $hall, array $data): Hall
    {
        return DB::transaction(function () use ($hall, $data) {
            [$attributes, $equipment] = $this->extract($data);

            $hall->update($attributes);
            $this->syncEquipment($hall, $equipment);

            return $hall;
        });
    }

    public function delete(Hall $hall): void
    {
        if ($blockers = $this->deletionBlockers($hall)) {
            throw ValidationException::withMessages([
                'hall' => 'لا يمكن حذف القاعة لارتباطها بـ: '.implode('، ', $blockers).'.',
            ]);
        }

        DB::transaction(function () use ($hall) {
            $hall->hallBlocks()->delete();
            $hall->equipment()->detach();
            $hall->delete();
        });
    }

    /**
     * Reasons preventing deletion (empty array means the hall can be deleted).
     *
     * @return list<string>
     */
    public function deletionBlockers(Hall $hall): array
    {
        $blockers = [];

        if ($hall->sessionDays()->exists()) {
            $blockers[] = 'أيام جلسات مجدولة';
        }
        if ($hall->hallChangeLogAsOldHall()->exists() || $hall->hallChangeLogAsNewHall()->exists()) {
            $blockers[] = 'سجلات تغيير قاعة';
        }
        if ($hall->evaluationParticipation()->exists() || $hall->evaluationSubmissions()->exists()) {
            $blockers[] = 'تقييمات مرتبطة بالقاعة';
        }

        return array_values(array_unique($blockers));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: array<int, array<string, mixed>>}
     */
    private function extract(array $data): array
    {
        $equipment = Arr::pull($data, 'equipment', []);

        if (($data['is_active'] ?? null) === null) {
            $data['is_active'] = true;
        }

        return [
            $data,
            array_values(array_filter((array) $equipment, 'is_array')),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncEquipment(Hall $hall, array $rows): void
    {
        $sync = [];

        foreach ($rows as $row) {
            $equipmentId = (int) ($row['equipment_id'] ?? 0);

            if ($equipmentId > 0) {
                $sync[$equipmentId] = ['quantity' => max(1, (int) ($row['quantity'] ?? 1))];
            }
        }

        $hall->equipment()->sync($sync);
    }
}
