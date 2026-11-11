<?php

namespace App\Http\Requests;

use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTrainerPaymentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('TRAINER_PAYMENTS_MANAGE');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in([PaymentStatus::Approved->value, PaymentStatus::Paid->value]),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'in' => 'حالة المستحق المحددة غير صالحة.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['status' => 'حالة المستحق'];
    }
}
