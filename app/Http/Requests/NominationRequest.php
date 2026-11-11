<?php

namespace App\Http\Requests;

use App\Enums\NominationSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared validation for creating and updating a nomination.
 */
abstract class NominationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('NOMINATE_TRAINEE');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $nominationId = $this->route('nomination')?->id;

        return [
            'session_id' => [
                'required', 'integer', 'exists:course_sessions,id',
                Rule::unique('nominations', 'session_id')
                    ->where(fn ($query) => $query->where('trainee_id', $this->input('trainee_id')))
                    ->ignore($nominationId),
            ],
            'trainee_id' => [
                'required', 'integer', 'exists:trainees,id',
                Rule::unique('nominations', 'trainee_id')
                    ->where(fn ($query) => $query->where('session_id', $this->input('session_id')))
                    ->ignore($nominationId),
            ],
            'source' => ['required', Rule::enum(NominationSource::class)],
            'nominated_by_provider_id' => [
                'nullable', 'integer', 'exists:training_providers,id',
                Rule::requiredIf(fn () => $this->input('source') === NominationSource::Provider->value),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
            'override' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'override' => $this->has('override') ? $this->boolean('override') : false,
            'nominated_by_provider_id' => $this->input('source') === NominationSource::Provider->value
                ? $this->input('nominated_by_provider_id')
                : null,
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'required_if' => 'حقل :attribute مطلوب عند اختيار الترشيح من جهة تدريب.',
            'integer' => 'حقل :attribute يجب أن يكون عددًا صحيحًا.',
            'boolean' => 'حقل :attribute يجب أن يكون نعم/لا.',
            'max' => 'حقل :attribute يجب ألا يتجاوز :max حرفًا.',
            'exists' => 'القيمة المحددة في :attribute غير موجودة.',
            'unique' => 'يوجد ترشيح مسجل بالفعل لهذا المتدرب في نفس الجلسة.',
            'enum' => 'القيمة المحددة في :attribute غير صالحة.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'session_id' => 'الجلسة',
            'trainee_id' => 'المتدرب',
            'source' => 'مصدر الترشيح',
            'nominated_by_provider_id' => 'جهة الترشيح',
            'notes' => 'ملاحظات',
            'override' => 'تجاوز معايير الالتحاق',
        ];
    }
}
