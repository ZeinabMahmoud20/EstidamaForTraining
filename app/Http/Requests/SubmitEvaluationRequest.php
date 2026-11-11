<?php

namespace App\Http\Requests;

use App\Enums\EvalAnswerType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubmitEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('EVALUATION_SUBMIT');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'session_id' => ['required', 'integer', 'exists:course_sessions,id'],
            'target_trainer_id' => ['nullable', 'integer', 'exists:trainers,id'],
            'target_enrollment_id' => ['nullable', 'integer', 'exists:enrollments,id'],
            'target_hall_id' => ['nullable', 'integer', 'exists:halls,id'],

            'answers' => ['nullable', 'array'],
            'answers.*.score_value' => ['nullable', 'integer', 'between:1,5'],
            'answers.*.text_value' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $form = $this->route('evaluation');

            if (! $form) {
                return;
            }

            foreach ($form->evaluationItems as $item) {
                if (! $item->is_required) {
                    continue;
                }

                $row = (array) ($this->input('answers', [])[$item->id] ?? []);

                $hasValue = $item->answer_type === EvalAnswerType::Scale15
                    ? filled($row['score_value'] ?? null)
                    : filled($row['text_value'] ?? null);

                if (! $hasValue) {
                    $validator->errors()->add(
                        'answers.'.$item->id,
                        'يجب الإجابة على البند: '.$item->item_text,
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
            'exists' => 'القيمة المحددة في :attribute غير موجودة.',
            'between' => 'حقل :attribute يجب أن يكون بين :min و :max.',
            'string' => 'حقل :attribute يجب أن يكون نصًا.',
            'max' => 'حقل :attribute يجب ألا يتجاوز :max.',
            'array' => 'حقل :attribute يجب أن يكون قائمة.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'session_id' => 'الجلسة',
            'target_trainer_id' => 'المدرب المستهدف',
            'target_enrollment_id' => 'المتدرب المستهدف',
            'target_hall_id' => 'القاعة المستهدفة',
            'answers' => 'الإجابات',
            'answers.*.score_value' => 'التقييم',
            'answers.*.text_value' => 'الإجابة النصية',
        ];
    }
}
