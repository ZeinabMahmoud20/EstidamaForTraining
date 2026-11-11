<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared validation for creating and updating an accreditation body.
 */
abstract class AccreditationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('BRANCHES_MANAGE');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'issuing_body' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'string' => 'حقل :attribute يجب أن يكون نصًا.',
            'max.string' => 'حقل :attribute يجب ألا يتجاوز :max حرفًا.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'اسم الاعتماد',
            'issuing_body' => 'الجهة المانحة',
            'description' => 'الوصف',
        ];
    }
}
