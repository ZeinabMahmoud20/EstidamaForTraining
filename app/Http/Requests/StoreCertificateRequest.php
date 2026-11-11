<?php

namespace App\Http\Requests;

use App\Enums\CertificateType;
use App\Models\Enrollment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('ISSUE_CERTIFICATE');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'enrollment_id' => ['required', 'integer', 'exists:enrollments,id', Rule::unique('certificates', 'enrollment_id')],
            'template_id' => ['nullable', 'integer', 'exists:certificate_templates,id'],
            'certificate_type' => ['required', Rule::enum(CertificateType::class)],
            'issue_date' => ['required', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $enrollment = Enrollment::find($this->input('enrollment_id'));

            if ($enrollment && in_array($enrollment->status?->value, ['withdrawn', 'dismissed'], true)) {
                $validator->errors()->add(
                    'enrollment_id',
                    'لا يمكن إصدار شهادة لتسجيل منسحب أو مفصول.',
                );
            }
        });
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'integer' => 'حقل :attribute يجب أن يكون عددًا صحيحًا.',
            'exists' => 'القيمة المحددة في :attribute غير موجودة.',
            'unique' => 'هذا التسجيل مرتبط بشهادة صادرة بالفعل.',
            'date' => 'حقل :attribute يجب أن يكون تاريخًا صالحًا.',
            'after_or_equal' => 'حقل :attribute يجب أن يكون بعد أو يساوي تاريخ الإصدار.',
            'enum' => 'القيمة المحددة في :attribute غير صالحة.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'enrollment_id' => 'التسجيل',
            'template_id' => 'القالب',
            'certificate_type' => 'نوع الشهادة',
            'issue_date' => 'تاريخ الإصدار',
            'expiry_date' => 'تاريخ الانتهاء',
        ];
    }
}
