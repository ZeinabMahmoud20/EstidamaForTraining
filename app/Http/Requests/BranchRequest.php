<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Shared validation for creating and updating a branch.
 */
abstract class BranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('BRANCHES_MANAGE');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $branchId = $this->route('branch')?->id;

        return [
            'code' => [
                'required', 'string', 'max:20', 'alpha_dash:ascii',
                Rule::unique('branches', 'code')->ignore($branchId),
            ],
            'name' => ['required', 'string', 'max:150'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:300'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $code = $this->input('code');

        if (is_string($code)) {
            $this->merge(['code' => Str::upper(trim($code))]);
        }

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
            'boolean' => 'حقل :attribute يجب أن يكون نعم/لا.',
            'alpha_dash' => 'حقل :attribute يسمح بالحروف والأرقام والشرطات فقط.',
            'max.string' => 'حقل :attribute يجب ألا يتجاوز :max حرفًا.',
            'unique' => 'قيمة :attribute مستخدمة بالفعل.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'code' => 'كود الفرع',
            'name' => 'اسم الفرع',
            'city' => 'المدينة',
            'address' => 'العنوان',
            'phone' => 'الهاتف',
            'is_active' => 'حالة الفرع',
        ];
    }
}
