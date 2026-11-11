<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GradeExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('EXAMS_GRADE');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'results' => ['required', 'array'],
            'results.*.score' => ['nullable', 'numeric', 'min:0'],
            'results.*.taken_at' => ['nullable', 'date'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'numeric' => 'حقل :attribute يجب أن يكون رقمًا.',
            'min' => 'حقل :attribute يجب ألا يقل عن :min.',
            'date' => 'حقل :attribute يجب أن يكون تاريخًا صالحًا.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'results' => 'نتائج المتدربين',
            'results.*.score' => 'درجة المتدرب',
            'results.*.taken_at' => 'تاريخ الأداء',
        ];
    }
}
