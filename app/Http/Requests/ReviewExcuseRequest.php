<?php

namespace App\Http\Requests;

use App\Enums\ExcuseStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewExcuseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('ATTENDANCE_EXCUSE_REVIEW');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in([ExcuseStatus::Approved->value, ExcuseStatus::Rejected->value]),
            ],
            'review_notes' => ['nullable', 'string', 'max:300', 'required_if:status,'.ExcuseStatus::Rejected->value],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'required_if' => 'حقل :attribute مطلوب عند رفض العذر.',
            'string' => 'حقل :attribute يجب أن يكون نصًا.',
            'max' => 'حقل :attribute يجب ألا يتجاوز :max حرفًا.',
            'in' => 'حالة العذر المحددة غير صالحة.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'status' => 'حالة العذر',
            'review_notes' => 'ملاحظات المراجعة',
        ];
    }
}
