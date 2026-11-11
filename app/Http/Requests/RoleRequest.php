<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a role and the permissions assigned to it.
 */
class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('ROLES_MANAGE');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $roleId = $this->route('role')?->id;

        return [
            'name' => [
                'required', 'string', 'max:125', 'alpha_dash:ascii',
                Rule::unique('roles', 'name')->ignore($roleId),
            ],
            'permissions' => ['nullable', 'array', 'max:100'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input('name');

        if (is_string($name)) {
            $this->merge(['name' => trim($name)]);
        }

        $this->merge([
            'permissions' => array_values(array_filter(
                (array) $this->input('permissions', []),
                fn ($value) => filled($value)
            )),
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'string' => 'حقل :attribute يجب أن يكون نصًا.',
            'array' => 'حقل :attribute غير صالح.',
            'alpha_dash' => 'حقل :attribute يسمح بالحروف والأرقام والشرطات فقط.',
            'max.string' => 'حقل :attribute يجب ألا يتجاوز :max حرفًا.',
            'max.array' => 'حقل :attribute يجب ألا يحتوي أكثر من :max عنصرًا.',
            'unique' => 'قيمة :attribute مستخدمة بالفعل.',
            'exists' => 'القيمة المحددة في :attribute غير موجودة.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'اسم الدور',
            'permissions' => 'الصلاحيات',
            'permissions.*' => 'الصلاحية',
        ];
    }
}
