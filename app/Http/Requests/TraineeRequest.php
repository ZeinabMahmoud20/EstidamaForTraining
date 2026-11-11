<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Shared validation for creating and updating a trainee profile,
 * including the training-provider affiliations.
 */
abstract class TraineeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('TRAINEES_MANAGE');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $traineeId = $this->route('trainee')?->id;

        return [
            'national_id' => [
                'required', 'string', 'max:20',
                Rule::unique('trainees', 'national_id')->ignore($traineeId),
            ],
            'full_name' => ['required', 'string', 'max:200'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:300'],
            'education_level_id' => ['nullable', 'integer', 'exists:education_levels,id'],
            'qualification' => ['nullable', 'string', 'max:200'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:80'],
            'job_title' => ['nullable', 'string', 'max:150'],
            'job_grade_id' => ['nullable', 'integer', 'exists:job_grades,id'],
            'is_active' => ['required', 'boolean'],

            'providers' => ['nullable', 'array', 'max:20'],
            'providers.*.provider_id' => ['required', 'integer', 'distinct', 'exists:training_providers,id'],
            'providers.*.job_title_at_provider' => ['nullable', 'string', 'max:150'],
            'providers.*.from_date' => ['nullable', 'date'],
            'providers.*.to_date' => ['nullable', 'date'],
            'providers.*.is_primary' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $hasValue = fn (array $row): bool => collect($row)
            ->except(['is_primary'])
            ->filter(fn ($value) => filled($value))
            ->isNotEmpty();

        $this->merge([
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : true,
            'providers' => collect((array) $this->input('providers', []))
                ->filter(fn ($row) => is_array($row) && $hasValue($row))
                ->map(function (array $row): array {
                    $row['is_primary'] = filter_var($row['is_primary'] ?? false, FILTER_VALIDATE_BOOLEAN);

                    foreach (['from_date', 'to_date'] as $key) {
                        if (($row[$key] ?? null) === '') {
                            $row[$key] = null;
                        }
                    }

                    return $row;
                })
                ->values()
                ->all(),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $rows = (array) $this->input('providers', []);

            $primaryCount = collect($rows)
                ->filter(fn ($row) => is_array($row) && ($row['is_primary'] ?? false))
                ->count();

            if ($primaryCount > 1) {
                $validator->errors()->add('providers', 'يمكن تحديد جهة تدريب أساسية واحدة فقط.');
            }

            foreach ($rows as $index => $row) {
                if (! is_array($row) || blank($row['from_date'] ?? null) || blank($row['to_date'] ?? null)) {
                    continue;
                }

                if (strtotime((string) $row['to_date']) < strtotime((string) $row['from_date'])) {
                    $validator->errors()->add(
                        "providers.$index.to_date",
                        'تاريخ النهاية يجب ألا يسبق تاريخ البداية.'
                    );
                }
            }
        });
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'string' => 'حقل :attribute يجب أن يكون نصًا.',
            'max.string' => 'حقل :attribute يجب ألا يتجاوز :max حرفًا.',
            'integer' => 'حقل :attribute يجب أن يكون عددًا صحيحًا.',
            'boolean' => 'حقل :attribute يجب أن يكون نعم/لا.',
            'date' => 'حقل :attribute يجب أن يكون تاريخًا صحيحًا.',
            'email' => 'حقل :attribute يجب أن يكون بريدًا إلكترونيًا صحيحًا.',
            'before_or_equal' => 'حقل :attribute يجب أن يكون في الماضي أو اليوم.',
            'min.integer' => 'حقل :attribute يجب ألا يقل عن :min.',
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
            'national_id' => 'الرقم القومي',
            'full_name' => 'اسم المتدرب',
            'gender' => 'النوع',
            'birth_date' => 'تاريخ الميلاد',
            'phone' => 'الهاتف',
            'email' => 'البريد الإلكتروني',
            'address' => 'العنوان',
            'education_level_id' => 'المستوى التعليمي',
            'qualification' => 'المؤهل',
            'years_experience' => 'سنوات الخبرة',
            'job_title' => 'المسمى الوظيفي',
            'job_grade_id' => 'الدرجة الوظيفية',
            'is_active' => 'حالة المتدرب',
            'providers' => 'جهات التدريب',
            'providers.*.provider_id' => 'جهة التدريب',
            'providers.*.job_title_at_provider' => 'المسمى الوظيفي بالجهة',
            'providers.*.from_date' => 'تاريخ البداية',
            'providers.*.to_date' => 'تاريخ النهاية',
            'providers.*.is_primary' => 'الجهة الأساسية',
        ];
    }
}
