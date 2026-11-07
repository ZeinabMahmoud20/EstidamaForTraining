<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Enums\TrainerType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared validation for creating and updating a trainer profile,
 * including specialties, certifications and work experiences.
 */
abstract class TrainerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('TRAINERS_MANAGE');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $trainerId = $this->route('trainer')?->id;

        return [
            'trainer_type' => ['required', Rule::enum(TrainerType::class)],
            'provider_id' => ['nullable', 'integer', 'exists:training_providers,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'national_id' => [
                'nullable', 'string', 'max:20',
                Rule::unique('trainers', 'national_id')->ignore($trainerId),
            ],
            'full_name' => ['required', 'string', 'max:200'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:300'],
            'highest_qualification' => ['nullable', 'string', 'max:200'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:80'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'bank_account' => ['nullable', 'string', 'max:100'],
            'is_active' => ['required', 'boolean'],

            'specialties' => ['nullable', 'array', 'max:50'],
            'specialties.*' => ['integer', 'distinct', 'exists:course_categories,id'],

            'certifications' => ['nullable', 'array', 'max:50'],
            'certifications.*.title' => ['required', 'string', 'max:200'],
            'certifications.*.issuer' => ['nullable', 'string', 'max:200'],
            'certifications.*.issue_date' => ['nullable', 'date'],
            'certifications.*.expiry_date' => ['nullable', 'date'],

            'experiences' => ['nullable', 'array', 'max:50'],
            'experiences.*.organization' => ['nullable', 'string', 'max:200'],
            'experiences.*.position' => ['nullable', 'string', 'max:150'],
            'experiences.*.from_date' => ['nullable', 'date'],
            'experiences.*.to_date' => ['nullable', 'date'],
            'experiences.*.description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $hasValue = fn (array $row): bool => collect($row)
            ->filter(fn ($value) => filled($value))
            ->isNotEmpty();

        $this->merge([
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : true,
            'specialties' => array_values(array_filter(
                (array) $this->input('specialties', []),
                fn ($value) => filled($value)
            )),
            'certifications' => collect((array) $this->input('certifications', []))
                ->filter(fn ($row) => is_array($row) && $hasValue($row))
                ->values()
                ->all(),
            'experiences' => collect((array) $this->input('experiences', []))
                ->filter(fn ($row) => is_array($row) && $hasValue($row))
                ->values()
                ->all(),
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'string' => 'حقل :attribute يجب أن يكون نصًا.',
            'max.string' => 'حقل :attribute يجب ألا يتجاوز :max حرفًا.',
            'integer' => 'حقل :attribute يجب أن يكون عددًا صحيحًا.',
            'numeric' => 'حقل :attribute يجب أن يكون رقمًا.',
            'boolean' => 'حقل :attribute يجب أن يكون نعم/لا.',
            'date' => 'حقل :attribute يجب أن يكون تاريخًا صحيحًا.',
            'email' => 'حقل :attribute يجب أن يكون بريدًا إلكترونيًا صحيحًا.',
            'before_or_equal' => 'حقل :attribute يجب أن يكون في الماضي أو اليوم.',
            'min.numeric' => 'حقل :attribute يجب ألا يقل عن :min.',
            'min.integer' => 'حقل :attribute يجب ألا يقل عن :min.',
            'max.numeric' => 'حقل :attribute يجب ألا يزيد عن :max.',
            'max.integer' => 'حقل :attribute يجب ألا يزيد عن :max.',
            'max.array' => 'حقل :attribute يجب ألا يحتوي أكثر من :max عنصرًا.',
            'exists' => 'القيمة المحددة في :attribute غير موجودة.',
            'unique' => 'قيمة :attribute مستخدمة بالفعل.',
            'distinct' => 'حقل :attribute يحتوي على قيمة مكررة.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'trainer_type' => 'نوع المدرب',
            'provider_id' => 'جهة التدريب',
            'branch_id' => 'الفرع',
            'national_id' => 'الرقم القومي',
            'full_name' => 'اسم المدرب',
            'gender' => 'النوع',
            'birth_date' => 'تاريخ الميلاد',
            'phone' => 'الهاتف',
            'email' => 'البريد الإلكتروني',
            'address' => 'العنوان',
            'highest_qualification' => 'أعلى مؤهل',
            'years_experience' => 'سنوات الخبرة',
            'bio' => 'نبذة عن المدرب',
            'hourly_rate' => 'سعر الساعة',
            'bank_account' => 'الحساب البنكي',
            'is_active' => 'حالة المدرب',
            'specialties' => 'التخصصات',
            'specialties.*' => 'التخصص',
            'certifications' => 'الشهادات',
            'certifications.*.title' => 'اسم الشهادة',
            'certifications.*.issuer' => 'الجهة المانحة',
            'certifications.*.issue_date' => 'تاريخ الإصدار',
            'certifications.*.expiry_date' => 'تاريخ الانتهاء',
            'experiences' => 'الخبرات',
            'experiences.*.organization' => 'جهة العمل',
            'experiences.*.position' => 'المسمى الوظيفي',
            'experiences.*.from_date' => 'تاريخ البداية',
            'experiences.*.to_date' => 'تاريخ النهاية',
            'experiences.*.description' => 'وصف الخبرة',
        ];
    }
}
