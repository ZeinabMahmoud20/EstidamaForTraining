<?php

namespace App\Http\Requests;

use App\Enums\ExamType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('EXAMS_MANAGE');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'session_id' => ['required', 'integer', 'exists:course_sessions,id'],
            'exam_type' => ['required', Rule::enum(ExamType::class)],
            'title' => ['nullable', 'string', 'max:200'],
            'scheduled_start' => ['nullable', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'total_score' => ['required', 'numeric', 'min:0.01', 'max:9999'],
            'pass_score' => ['nullable', 'numeric', 'min:0', 'lte:total_score'],
            'weight_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['integer', 'distinct', 'exists:questions,id'],
            'points' => ['nullable', 'array'],
            'points.*' => ['nullable', 'numeric', 'min:0', 'max:999'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'integer' => 'حقل :attribute يجب أن يكون عددًا صحيحًا.',
            'numeric' => 'حقل :attribute يجب أن يكون رقمًا.',
            'string' => 'حقل :attribute يجب أن يكون نصًا.',
            'exists' => 'القيمة المحددة في :attribute غير موجودة.',
            'date' => 'حقل :attribute يجب أن يكون تاريخًا صالحًا.',
            'min' => 'حقل :attribute يجب ألا يقل عن :min.',
            'max' => 'حقل :attribute يجب ألا يتجاوز :max.',
            'lte' => 'حقل :attribute يجب ألا يتجاوز :value.',
            'array' => 'حقل :attribute يجب أن يكون قائمة.',
            'distinct' => 'لا يمكن تكرار السؤال في الاختبار.',
            'enum' => 'القيمة المحددة في :attribute غير صالحة.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'session_id' => 'الجلسة',
            'exam_type' => 'نوع الاختبار',
            'title' => 'عنوان الاختبار',
            'scheduled_start' => 'موعد الاختبار',
            'duration_minutes' => 'مدة الاختبار',
            'total_score' => 'الدرجة الكلية',
            'pass_score' => 'درجة النجاح',
            'weight_pct' => 'وزن الاختبار',
            'question_ids' => 'الأسئلة',
            'points.*' => 'درجة السؤال',
        ];
    }
}
