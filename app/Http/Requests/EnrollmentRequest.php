<?php

namespace App\Http\Requests;

use App\Enums\EnrollmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for creating an enrollment from an approved nomination and updating it later.
 */
abstract class EnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('ENROLLMENTS_MANAGE');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $editing = $this->route('enrollment')?->id !== null;

        $rules = [
            'status' => ['required', Rule::enum(EnrollmentStatus::class)],
            'attendance_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'final_score_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_passed' => ['nullable', 'boolean'],
            'is_certificate_eligible' => ['nullable', 'boolean'],
            'completed_at' => ['nullable', 'date'],
        ];

        if (! $editing) {
            $rules['nomination_id'] = [
                'required', 'integer', 'exists:nominations,id',
                Rule::unique('enrollments', 'nomination_id'),
            ];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        foreach (['is_passed', 'is_certificate_eligible'] as $field) {
            if ($this->has($field)) {
                $value = $this->input($field);
                $this->merge([$field => $value === '' || $value === null ? null : $this->boolean($field)]);
            }
        }
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'integer' => 'حقل :attribute يجب أن يكون عددًا صحيحًا.',
            'boolean' => 'حقل :attribute يجب أن يكون نعم/لا.',
            'numeric' => 'حقل :attribute يجب أن يكون رقمًا.',
            'min' => 'حقل :attribute يجب ألا يقل عن :min.',
            'max' => 'حقل :attribute يجب ألا يزيد عن :max.',
            'date' => 'حقل :attribute يجب أن يكون تاريخًا صحيحًا.',
            'exists' => 'القيمة المحددة في :attribute غير موجودة.',
            'unique' => 'هذا الترشيح مرتبط بتسجيل بالفعل.',
            'enum' => 'القيمة المحددة في :attribute غير صالحة.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'nomination_id' => 'الترشيح',
            'status' => 'حالة التسجيل',
            'attendance_pct' => 'نسبة الحضور',
            'final_score_pct' => 'الدرجة النهائية',
            'is_passed' => 'النتيجة',
            'is_certificate_eligible' => 'أهلية الشهادة',
            'completed_at' => 'تاريخ الإتمام',
        ];
    }
}
