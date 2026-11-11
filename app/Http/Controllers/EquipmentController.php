<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Requests\UpdateEquipmentRequest;
use App\Models\Equipment;
use App\Services\EquipmentService;
use Illuminate\Http\RedirectResponse;

class EquipmentController extends Controller
{
    public function __construct(private readonly EquipmentService $equipment) {}

    public function store(StoreEquipmentRequest $request): RedirectResponse
    {
        $this->equipment->create($request->validated());

        return back()->with('success', 'تمت إضافة التجهيز بنجاح.');
    }

    public function update(UpdateEquipmentRequest $request, Equipment $equipment): RedirectResponse
    {
        $this->equipment->update($equipment, $request->validated());

        return back()->with('success', 'تم تحديث التجهيز بنجاح.');
    }

    public function destroy(Equipment $equipment): RedirectResponse
    {
        if ($equipment->halls()->exists()) {
            return back()->with('error', 'لا يمكن حذف التجهيز لارتباطه بقاعات. أزل التجهيز من القاعات أولًا.');
        }

        $this->equipment->delete($equipment);

        return back()->with('success', 'تم حذف التجهيز بنجاح.');
    }
}
