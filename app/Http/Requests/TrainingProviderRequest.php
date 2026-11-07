<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared validation for creating and updating a training provider.
 */
abstract class TrainingProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('PROVIDERS_MANAGE');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:300'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : true,
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'string' => 'حقل :attribute يجب أن يكون نصًا.',
            'max.string' => 'حقل :attribute يجب ألا يتجاوز :max حرفًا.',
            'boolean' => 'حقل :attribute يجب أن يكون نعم/لا.',
            'email' => 'حقل :attribute يجب أن يكون بريدًا إلكترونيًا صحيحًا.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'اسم جهة التدريب',
            'contact_person' => 'مسؤول التواصل',
            'phone' => 'الهاتف',
            'email' => 'البريد الإلكتروني',
            'address' => 'العنوان',
            'is_active' => 'حالة جهة التدريب',
        ];
    }
}
