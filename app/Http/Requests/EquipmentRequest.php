<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class EquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('HALLS_MANAGE');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $equipmentId = $this->route('equipment')?->id;

        return [
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('equipment', 'name')->ignore($equipmentId),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'string' => 'حقل :attribute يجب أن يكون نصًا.',
            'max' => 'حقل :attribute يجب ألا يتجاوز :max حرفًا.',
            'unique' => 'اسم التجهيز مستخدم بالفعل.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'اسم التجهيز',
        ];
    }
}
