<?php

namespace App\Http\Requests;

use App\Enums\CourseLevel;
use App\Enums\CriteriaOperator;
use App\Enums\DeliveryMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Shared validation for creating and updating a course (basic data + nested rows).
 */
abstract class CourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('COURSES_MANAGE');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $courseId = $this->route('course')?->id;

        $prerequisiteRules = ['integer', 'distinct', 'exists:courses,id'];
        if ($courseId) {
            $prerequisiteRules[] = Rule::notIn([$courseId]);
        }

        return [
            'code' => [
                'required', 'string', 'max:30', 'alpha_dash:ascii',
                Rule::unique('courses', 'code')->ignore($courseId),
            ],
            'title' => ['required', 'string', 'max:250'],
            'description' => ['nullable', 'string', 'max:5000'],
            'objectives' => ['nullable', 'string', 'max:5000'],
            'content_outline' => ['nullable', 'string', 'max:10000'],
            'target_audience' => ['nullable', 'string', 'max:1000'],
            'category_id' => ['nullable', 'integer', 'exists:course_categories,id'],
            'course_type_id' => ['nullable', 'integer', 'exists:course_types,id'],
            'level' => ['nullable', Rule::enum(CourseLevel::class)],
            'total_hours' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'days_count' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'default_delivery_mode' => ['nullable', Rule::enum(DeliveryMode::class)],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'min_trainees' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'max_trainees' => ['nullable', 'integer', 'min:1', 'max:100000', 'gte:min_trainees'],
            'min_attendance_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'pass_score_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['required', 'boolean'],

            'modules' => ['nullable', 'array', 'max:50'],
            'modules.*.id' => ['nullable', 'integer', 'min:1'],
            'modules.*.title' => ['required', 'string', 'max:250'],
            'modules.*.description' => ['nullable', 'string', 'max:2000'],
            'modules.*.hours' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'modules.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],

            'criteria' => ['nullable', 'array', 'max:50'],
            'criteria.*.criteria_type_id' => ['required', 'integer', 'exists:criteria_types,id'],
            'criteria.*.operator' => ['required', Rule::enum(CriteriaOperator::class)],
            'criteria.*.value' => ['nullable', 'string', 'max:200'],
            'criteria.*.is_mandatory' => ['required', 'boolean'],

            'prerequisites' => ['nullable', 'array', 'max:50'],
            'prerequisites.*' => $prerequisiteRules,
        ];
    }

    protected function prepareForValidation(): void
    {
        $code = $this->input('code');
        if (is_string($code)) {
            $this->merge(['code' => Str::upper(trim($code))]);
        }

        $this->merge([
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : true,
            'modules' => array_values(array_filter((array) $this->input('modules', []), 'is_array')),
            'criteria' => array_values(array_filter((array) $this->input('criteria', []), 'is_array')),
            'prerequisites' => array_values(array_filter(
                (array) $this->input('prerequisites', []),
                fn ($value) => filled($value)
            )),
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'string' => 'حقل :attribute يجب أن يكون نصًا.',
            'integer' => 'حقل :attribute يجب أن يكون عددًا صحيحًا.',
            'numeric' => 'حقل :attribute يجب أن يكون رقمًا.',
            'boolean' => 'حقل :attribute يجب أن يكون نعم/لا.',
            'array' => 'حقل :attribute غير صالح.',
            'max.string' => 'حقل :attribute يجب ألا يتجاوز :max حرفًا.',
            'max.numeric' => 'حقل :attribute يجب ألا يزيد عن :max.',
            'max.integer' => 'حقل :attribute يجب ألا يزيد عن :max.',
            'max.array' => 'حقل :attribute يجب ألا يحتوي أكثر من :max عنصرًا.',
            'min.numeric' => 'حقل :attribute يجب ألا يقل عن :min.',
            'min.integer' => 'حقل :attribute يجب ألا يقل عن :min.',
            'gte.numeric' => 'حقل :attribute يجب ألا يقل عن :value.',
            'gte.integer' => 'حقل :attribute يجب ألا يقل عن :value.',
            'exists' => 'القيمة المحددة في :attribute غير موجودة.',
            'unique' => 'قيمة :attribute مستخدمة بالفعل.',
            'alpha_dash' => 'حقل :attribute يسمح بالحروف والأرقام والشرطات فقط.',
            'enum' => 'القيمة المحددة في :attribute غير صالحة.',
            'not_in' => 'القيمة المحددة في :attribute غير مسموح بها.',
            'distinct' => 'حقل :attribute يحتوي على قيمة مكررة.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'code' => 'كود الدورة',
            'title' => 'اسم الدورة',
            'description' => 'وصف الدورة',
            'objectives' => 'أهداف الدورة',
            'content_outline' => 'محتوى الدورة',
            'target_audience' => 'الفئة المستهدفة',
            'category_id' => 'التصنيف',
            'course_type_id' => 'نوع الدورة',
            'level' => 'المستوى',
            'total_hours' => 'إجمالي الساعات',
            'days_count' => 'عدد الأيام',
            'default_delivery_mode' => 'طريقة التنفيذ',
            'price' => 'السعر',
            'min_trainees' => 'الحد الأدنى للمتدربين',
            'max_trainees' => 'الحد الأقصى للمتدربين',
            'min_attendance_pct' => 'نسبة الحضور المطلوبة',
            'pass_score_pct' => 'درجة النجاح',
            'is_active' => 'حالة الدورة',
            'modules' => 'الوحدات التدريبية',
            'modules.*.title' => 'عنوان الوحدة',
            'modules.*.description' => 'وصف الوحدة',
            'modules.*.hours' => 'ساعات الوحدة',
            'modules.*.sort_order' => 'ترتيب الوحدة',
            'criteria' => 'شروط الالتحاق',
            'criteria.*.criteria_type_id' => 'نوع المعيار',
            'criteria.*.operator' => 'عملية المقارنة',
            'criteria.*.value' => 'قيمة المعيار',
            'criteria.*.is_mandatory' => 'إلزامي',
            'prerequisites' => 'المتطلبات السابقة',
            'prerequisites.*' => 'المتطلب السابق',
        ];
    }
}
