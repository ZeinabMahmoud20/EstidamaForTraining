<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TrainerPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('TRAINER_PAYMENTS_MANAGE');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $trainerPayment = $this->route('trainerPayment');

        return [
            'trainer_id' => ['required', 'integer', 'exists:trainers,id'],
            'session_id' => [
                'required', 'integer', 'exists:course_sessions,id',
                Rule::unique('trainer_payments', 'session_id')
                    ->where('trainer_id', $this->input('trainer_id'))
                    ->ignore($trainerPayment?->id),
            ],
            'total_hours' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'integer' => 'حقل :attribute يجب أن يكون عددًا صحيحًا.',
            'numeric' => 'حقل :attribute يجب أن يكون رقمًا.',
            'exists' => 'القيمة المحددة في :attribute غير موجودة.',
            'unique' => 'يوجد مستحق مسجّل لهذا المدرب في الجلسة نفسها.',
            'min' => 'حقل :attribute يجب ألا يقل عن :min.',
            'max' => 'حقل :attribute يجب ألا يتجاوز :max.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'trainer_id' => 'المدرب',
            'session_id' => 'الجلسة',
            'total_hours' => 'عدد الساعات',
            'hourly_rate' => 'سعر الساعة',
            'amount' => 'المبلغ',
        ];
    }
}
