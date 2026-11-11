@php
    $editing = isset($hall) && $hall && $hall->exists;

    $equipmentRows = old('equipment');
    if ($equipmentRows === null) {
        $equipmentRows = $editing
            ? $hall->equipment->map(fn ($item) => [
                'equipment_id' => $item->id,
                'quantity' => $item->pivot->quantity,
            ])->values()->all()
            : [];
    }
@endphp

<form method="POST" action="{{ $editing ? route('halls.update', $hall) : route('halls.store') }}">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <div class="grid">
        <x-card title="بيانات القاعة">
            <div class="form-grid">
                <x-form.select name="branch_id" label="الفرع" :options="$branches" required
                               :selected="$editing ? $hall->branch_id : null" />

                <x-form.input name="code" label="كود القاعة" :value="$editing ? $hall->code : null" required
                              hint="حروف لاتينية وأرقام وشرطات، وفريد داخل الفرع." />

                <x-form.input name="name" label="اسم القاعة" :value="$editing ? $hall->name : null" required />

                <x-form.input name="capacity" type="number" min="1" max="10000" label="السعة"
                              :value="$editing ? $hall->capacity : null" required />

                <x-form.input name="floor" label="الدور" :value="$editing ? $hall->floor : null" />
                <x-form.input name="location_description" label="وصف الموقع"
                              :value="$editing ? $hall->location_description : null" />

                <x-form.checkbox name="is_active" label="القاعة نشطة ومتاحة للجدولة"
                                 :checked="$editing ? $hall->is_active : true" />
            </div>
        </x-card>

        <x-card title="تجهيزات القاعة">
            <p class="muted" style="margin-block-start:0">
                حدّد التجهيزات الموجودة في القاعة وكمياتها. أضف التجهيزات الجديدة من تبويب «التجهيزات».
            </p>

            @if ($equipmentOptions->isEmpty())
                <p class="muted">لا توجد تجهيزات مسجّلة بعد. أضفها من تبويب التجهيزات أولًا.</p>
            @endif

            <div data-repeat-group>
                <div class="repeat-list" data-repeat>
                    @foreach ($equipmentRows as $i => $row)
                        <div class="repeat-row repeat-row--hall-equipment" data-repeat-row>
                            <div class="field">
                                <label for="equipment-id-{{ $i }}">التجهيز <span class="req">*</span></label>
                                <select id="equipment-id-{{ $i }}" name="equipment[{{ $i }}][equipment_id]"
                                        class="control @error("equipment.$i.equipment_id") is-invalid @enderror" required>
                                    <option value="">اختر التجهيز...</option>
                                    @foreach ($equipmentOptions as $equipmentId => $equipmentName)
                                        <option value="{{ $equipmentId }}" @selected((string) ($row['equipment_id'] ?? '') === (string) $equipmentId)>
                                            {{ $equipmentName }}
                                        </option>
                                    @endforeach
                                </select>
                                @error("equipment.$i.equipment_id")<div class="error">{{ $message }}</div>@enderror
                            </div>

                            <div class="field">
                                <label for="equipment-qty-{{ $i }}">الكمية <span class="req">*</span></label>
                                <input id="equipment-qty-{{ $i }}" name="equipment[{{ $i }}][quantity]" type="number"
                                       min="1" max="10000" class="control @error("equipment.$i.quantity") is-invalid @enderror"
                                       value="{{ $row['quantity'] ?? 1 }}" required>
                                @error("equipment.$i.quantity")<div class="error">{{ $message }}</div>@enderror
                            </div>

                            <div class="field">
                                <label>&nbsp;</label>
                                <button type="button" class="btn btn-ghost btn-sm" data-repeat-remove>
                                    <i class="fa-solid fa-trash"></i> حذف
                                </button>
                            </div>
                        </div>
                    @endforeach

                    <template data-repeat-template>
                        <div class="repeat-row repeat-row--hall-equipment" data-repeat-row>
                            <div class="field">
                                <label for="equipment-id-__INDEX__">التجهيز <span class="req">*</span></label>
                                <select id="equipment-id-__INDEX__" name="equipment[__INDEX__][equipment_id]" class="control" required>
                                    <option value="">اختر التجهيز...</option>
                                    @foreach ($equipmentOptions as $equipmentId => $equipmentName)
                                        <option value="{{ $equipmentId }}">{{ $equipmentName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="field">
                                <label for="equipment-qty-__INDEX__">الكمية <span class="req">*</span></label>
                                <input id="equipment-qty-__INDEX__" name="equipment[__INDEX__][quantity]" type="number"
                                       min="1" max="10000" class="control" value="1" required>
                            </div>

                            <div class="field">
                                <label>&nbsp;</label>
                                <button type="button" class="btn btn-ghost btn-sm" data-repeat-remove>
                                    <i class="fa-solid fa-trash"></i> حذف
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="actions" style="margin-block-start:10px">
                    <button type="button" class="btn btn-outline btn-sm" data-repeat-add @disabled($equipmentOptions->isEmpty())>
                        <i class="fa-solid fa-plus"></i> إضافة تجهيز
                    </button>
                </div>
            </div>
        </x-card>

        <div class="actions" style="justify-content:flex-end">
            <a class="btn btn-ghost" href="{{ $editing ? route('halls.show', $hall) : route('halls.index') }}">إلغاء</a>
            <button type="submit" class="btn">
                <i class="fa-solid fa-floppy-disk"></i>
                {{ $editing ? 'حفظ التعديلات' : 'إضافة القاعة' }}
            </button>
        </div>
    </div>
</form>
