<?php

namespace App\Http\Requests;

use App\Enums\EvalAnswerType;
use App\Enums\EvalEvaluator;
use App\Enums\EvalTarget;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class EvaluationFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('EVALUATION_FORMS_MANAGE');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $form = $this->route('evaluation');

        return [
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('evaluation_forms', 'code')->ignore($form?->id),
            ],
            'title' => ['required', 'string', 'max:250'],
            'target_type' => ['required', Rule::enum(EvalTarget::class)],
            'evaluator_type' => ['required', Rule::enum(EvalEvaluator::class)],
            'is_anonymous' => ['nullable', 'boolean'],
            'is_system' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'version' => ['nullable', 'integer', 'min:1'],

            'sections' => ['nullable', 'array'],
            'sections.*.id' => ['nullable', 'integer'],
            'sections.*.title' => ['nullable', 'string', 'max:200'],
            'sections.*.weight' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'sections.*.items' => ['nullable', 'array'],
            'sections.*.items.*.id' => ['nullable', 'integer'],
            'sections.*.items.*.item_text' => ['nullable', 'string', 'max:500'],
            'sections.*.items.*.answer_type' => ['nullable', Rule::enum(EvalAnswerType::class)],
            'sections.*.items.*.weight' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'sections.*.items.*.is_required' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ((array) $this->input('sections', []) as $sectionIndex => $section) {
                if (! is_array($section)) {
                    continue;
                }

                $items = collect((array) ($section['items'] ?? []))
                    ->filter(fn ($item) => is_array($item) && trim((string) ($item['item_text'] ?? '')) !== '');

                if ($items->isEmpty()) {
                    continue;
                }

                if (blank($section['title'] ?? null)) {
                    $validator->errors()->add(
                        "sections.$sectionIndex.title",
                        'يجب إدخال عنوان القسم الذي يحتوي على بنود تقييم.',
                    );
                }

                foreach ($items as $itemIndex => $item) {
                    if (blank($item['answer_type'] ?? null)) {
                        $validator->errors()->add(
                            "sections.$sectionIndex.items.$itemIndex.answer_type",
                            'يجب تحديد نوع الإجابة لكل بند.',
                        );
                    }
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
            'integer' => 'حقل :attribute يجب أن يكون عددًا صحيحًا.',
            'numeric' => 'حقل :attribute يجب أن يكون رقمًا.',
            'max' => 'حقل :attribute يجب ألا يتجاوز :max.',
            'min' => 'حقل :attribute يجب ألا يقل عن :min.',
            'unique' => 'كود النموذج مستخدم بالفعل.',
            'boolean' => 'قيمة :attribute غير صالحة.',
            'enum' => 'القيمة المحددة في :attribute غير صالحة.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'code' => 'كود النموذج',
            'title' => 'عنوان النموذج',
            'target_type' => 'نوع الهدف',
            'evaluator_type' => 'نوع المقيّم',
            'version' => 'الإصدار',
            'sections.*.title' => 'عنوان القسم',
            'sections.*.weight' => 'وزن القسم',
            'sections.*.items.*.item_text' => 'نص البند',
            'sections.*.items.*.answer_type' => 'نوع الإجابة',
            'sections.*.items.*.weight' => 'وزن البند',
        ];
    }
}
