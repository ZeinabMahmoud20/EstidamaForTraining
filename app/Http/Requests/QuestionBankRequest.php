<?php

namespace App\Http\Requests;

use App\Enums\QuestionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class QuestionBankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('QUESTION_BANK_MANAGE');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'module_id' => ['nullable', 'integer', 'exists:course_modules,id'],
            'name' => ['nullable', 'string', 'max:200'],

            'questions' => ['nullable', 'array'],
            'questions.*.id' => ['nullable', 'integer'],
            'questions.*.question_text' => ['required', 'string'],
            'questions.*.question_type' => ['required', Rule::enum(QuestionType::class)],
            'questions.*.difficulty' => ['nullable', 'integer', 'min:1', 'max:5'],
            'questions.*.default_points' => ['required', 'numeric', 'min:0.01', 'max:999'],
            'questions.*.is_active' => ['nullable', 'boolean'],

            'questions.*.options' => ['nullable', 'array'],
            'questions.*.options.*.option_text' => ['nullable', 'string', 'max:500'],
            'questions.*.options.*.is_correct' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ((array) $this->input('questions', []) as $index => $question) {
                if (! is_array($question) || blank($question['question_text'] ?? null)) {
                    continue;
                }

                $type = $question['question_type'] ?? null;

                if (! in_array($type, [QuestionType::Mcq->value, QuestionType::TrueFalse->value], true)) {
                    continue;
                }

                $options = collect((array) ($question['options'] ?? []))
                    ->filter(fn ($option) => is_array($option) && trim((string) ($option['option_text'] ?? '')) !== '');

                if ($options->count() < 2) {
                    $validator->errors()->add(
                        "questions.$index.options",
                        'يجب إدخال خيارين على الأقل لسؤال الاختيار من متعدد أو صح/خطأ.',
                    );

                    continue;
                }

                if ($options->filter(fn ($option) => (bool) ($option['is_correct'] ?? false))->count() !== 1) {
                    $validator->errors()->add(
                        "questions.$index.options",
                        'يجب تحديد خيار صحيح واحد فقط لكل سؤال.',
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
            'integer' => 'حقل :attribute يجب أن يكون عددًا صحيحًا.',
            'numeric' => 'حقل :attribute يجب أن يكون رقمًا.',
            'string' => 'حقل :attribute يجب أن يكون نصًا.',
            'exists' => 'القيمة المحددة في :attribute غير موجودة.',
            'min' => 'حقل :attribute يجب ألا يقل عن :min.',
            'max' => 'حقل :attribute يجب ألا يتجاوز :max.',
            'boolean' => 'قيمة :attribute غير صالحة.',
            'enum' => 'القيمة المحددة في :attribute غير صالحة.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'course_id' => 'الدورة',
            'module_id' => 'الوحدة',
            'name' => 'اسم بنك الأسئلة',
            'questions.*.question_text' => 'نص السؤال',
            'questions.*.question_type' => 'نوع السؤال',
            'questions.*.difficulty' => 'مستوى الصعوبة',
            'questions.*.default_points' => 'الدرجة الافتراضية',
            'questions.*.options.*.option_text' => 'نص الخيار',
        ];
    }
}
