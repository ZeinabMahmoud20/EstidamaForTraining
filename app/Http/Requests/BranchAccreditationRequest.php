<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates linking an accreditation to a branch.
 */
class BranchAccreditationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('BRANCHES_MANAGE');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $branchId = $this->route('branch')?->id;
        $linkId = $this->route('branchAccreditation')?->id;

        return [
            'accreditation_id' => [
                'required', 'integer', 'exists:accreditations,id',
                Rule::unique('branch_accreditations', 'accreditation_id')
                    ->where(fn ($query) => $query->where('branch_id', $branchId))
                    ->ignore($linkId),
            ],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
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
            'integer' => 'حقل :attribute يجب أن يكون عددًا صحيحًا.',
            'boolean' => 'حقل :attribute يجب أن يكون نعم/لا.',
            'date' => 'حقل :attribute يجب أن يكون تاريخًا صحيحًا.',
            'after_or_equal' => 'حقل :attribute يجب ألا يسبق :date.',
            'unique' => 'هذا الاعتماد مرتبط بالفرع بالفعل.',
            'exists' => 'القيمة المحددة في :attribute غير موجودة.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'accreditation_id' => 'الاعتماد',
            'issue_date' => 'تاريخ الإصدار',
            'expiry_date' => 'تاريخ الانتهاء',
            'is_active' => 'حالة الارتباط',
        ];
    }
}
