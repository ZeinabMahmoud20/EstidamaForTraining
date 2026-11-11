<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('PAYMENTS_MANAGE');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'method' => ['nullable', 'string', 'max:50'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'paid_at' => ['nullable', 'date'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'numeric' => 'حقل :attribute يجب أن يكون رقمًا.',
            'min' => 'حقل :attribute يجب ألا يقل عن :min.',
            'max' => 'حقل :attribute يجب ألا يتجاوز :max.',
            'string' => 'حقل :attribute يجب أن يكون نصًا.',
            'date' => 'حقل :attribute يجب أن يكون تاريخًا صالحًا.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'amount' => 'قيمة الدفعة',
            'method' => 'طريقة الدفع',
            'reference_no' => 'رقم المرجع',
            'paid_at' => 'تاريخ الدفع',
        ];
    }
}
