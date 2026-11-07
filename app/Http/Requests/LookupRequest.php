<?php

namespace App\Http\Requests;

use App\Models\CourseCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validation for the reference lists managed by the lookups screen.
 * Rules change according to the {type} route segment.
 */
class LookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('LOOKUPS_MANAGE');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $type = $this->type();
        $id = $this->route('lookup');

        $name = [
            'required', 'string', 'max:'.($type === 'education-levels' || $type === 'job-grades' ? 100 : 150),
            Rule::unique($this->table(), 'name')->ignore($id),
        ];

        return match ($type) {
            'categories' => [
                'name' => $name,
                'parent_id' => ['nullable', 'integer', 'exists:course_categories,id'],
            ],
            'course-types' => [
                'name' => $name,
                'description' => ['nullable', 'string', 'max:300'],
            ],
            'criteria-types' => [
                'name' => $name,
                'code' => [
                    'required', 'string', 'max:60', 'alpha_dash:ascii',
                    Rule::unique('criteria_types', 'code')->ignore($id),
                ],
                'description' => ['nullable', 'string', 'max:300'],
            ],
            'education-levels', 'job-grades' => [
                'name' => $name,
                'rank_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            ],
            default => [],
        };
    }

    protected function prepareForValidation(): void
    {
        $code = $this->input('code');
        if ($this->type() === 'criteria-types' && is_string($code)) {
            $this->merge(['code' => Str::upper(trim($code))]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->type() !== 'categories') {
                return;
            }

            $id = (int) $this->route('lookup');
            $parentId = (int) $this->input('parent_id');

            if ($id === 0 || $parentId === 0) {
                return;
            }

            if ($parentId === $id) {
                $validator->errors()->add('parent_id', 'لا يمكن أن يكون التصنيف تابعًا لنفسه.');

                return;
            }

            $seen = [];
            $current = CourseCategory::find($parentId);

            while ($current) {
                if ($current->id === $id) {
                    $validator->errors()->add('parent_id', 'لا يمكن أن يكون التصنيف تابعًا لأحد تصنيفاته الفرعية.');

                    return;
                }

                if (isset($seen[$current->id])) {
                    return;
                }

                $seen[$current->id] = true;
                $current = $current->parent()->first();
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
            'min.integer' => 'حقل :attribute يجب ألا يقل عن :min.',
            'max.integer' => 'حقل :attribute يجب ألا يزيد عن :max.',
            'unique' => 'قيمة :attribute مستخدمة بالفعل.',
            'exists' => 'القيمة المحددة في :attribute غير موجودة.',
            'alpha_dash' => 'حقل :attribute يسمح بالحروف والأرقام والشرطات فقط.',
            'not_in' => 'القيمة المحددة في :attribute غير مسموح بها.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'الاسم',
            'code' => 'الكود',
            'description' => 'الوصف',
            'parent_id' => 'التصنيف الأب',
            'rank_order' => 'ترتيب العرض',
        ];
    }

    public function type(): string
    {
        return (string) $this->route('type');
    }

    private function table(): string
    {
        return match ($this->type()) {
            'categories' => 'course_categories',
            'course-types' => 'course_types',
            'criteria-types' => 'criteria_types',
            'education-levels' => 'education_levels',
            'job-grades' => 'job_grades',
            default => '',
        };
    }
}
