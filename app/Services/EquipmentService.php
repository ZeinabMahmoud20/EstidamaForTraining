<?php

namespace App\Services;

use App\Models\Equipment;
use Illuminate\Validation\ValidationException;

class EquipmentService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Equipment
    {
        return Equipment::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Equipment $equipment, array $data): Equipment
    {
        $equipment->update($data);

        return $equipment;
    }

    public function delete(Equipment $equipment): void
    {
        if ($equipment->halls()->exists()) {
            throw ValidationException::withMessages([
                'equipment' => 'لا يمكن حذف التجهيز لارتباطه بقاعات. أزل التجهيز من القاعات أولًا.',
            ]);
        }

        $equipment->delete();
    }
}
