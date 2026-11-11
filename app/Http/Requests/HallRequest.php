<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared validation for creating and updating a hall and its equipment list.
 */
abstract class HallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('HALLS_MANAGE');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $hallId = $this->route('hall')?->id;

        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'code' => [
                'required', 'string', 'max:30',
                Rule::unique('halls', 'code')
                    ->where(fn ($query) => $query->where('branch_id', $this->input('branch_id')))
                    ->ignore($hallId),
            ],
            'name' => ['required', 'string', 'max:150'],
            'capacity' => ['required', 'integer', 'min:1', 'max:10000'],
            'floor' => ['nullable', 'string', 'max:30'],
            'location_description' => ['nullable', 'string', 'max:300'],
            'is_active' => ['required', 'boolean'],

            'equipment' => ['nullable', 'array', 'max:50'],
            'equipment.*.equipment_id' => ['required', 'integer', 'distinct', 'exists:equipment,id'],
            'equipment.*.quantity' => ['required', 'integer', 'min:1', 'max:10000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : true,
            'equipment' => collect((array) $this->input('equipment', []))
                ->filter(fn ($row) => is_array($row) && filled($row['equipment_id'] ?? null))
                ->values()
                ->all(),
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'string' => 'حقل :attribute يجب أن يكون نصًا.',
            'max.string' => 'حقل :attribute يجب ألا يتجاوز :max حرفًا.',
            'integer' => 'حقل :attribute يجب أن يكون عددًا صحيحًا.',
            'numeric' => 'حقل :attribute يجب أن يكون رقمًا.',
            'boolean' => 'حقل :attribute يجب أن يكون نعم/لا.',
            'exists' => 'القيمة المحددة في :attribute غير موجودة.',
            'unique' => 'كود القاعة مستخدم بالفعل في هذا الفرع.',
            'distinct' => 'حقل :attribute يحتوي على تجهيز مكرر.',
            'min.integer' => 'حقل :attribute يجب ألا يقل عن :min.',
            'max.integer' => 'حقل :attribute يجب ألا يزيد عن :max.',
            'max.array' => 'حقل :attribute يجب ألا يحتوي أكثر من :max عنصرًا.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'branch_id' => 'الفرع',
            'code' => 'كود القاعة',
            'name' => 'اسم القاعة',
            'capacity' => 'السعة',
            'floor' => 'الدور',
            'location_description' => 'وصف الموقع',
            'is_active' => 'حالة القاعة',
            'equipment' => 'التجهيزات',
            'equipment.*.equipment_id' => 'التجهيز',
            'equipment.*.quantity' => 'الكمية',
        ];
    }
}
