<?php

namespace App\Http\Requests;

use App\Enums\NominationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Moves a nomination through its lifecycle with the permission required by the target status.
 */
class TransitionNominationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $status = NominationStatus::tryFrom((string) $this->input('status'));

        if ($this->boolean('override') && ! $this->user()?->can('OVERRIDE_CRITERIA')) {
            return false;
        }

        return match ($status) {
            NominationStatus::UnderReview => (bool) $this->user()?->can('REVIEW_NOMINATION'),
            NominationStatus::Approved,
            NominationStatus::Rejected,
            NominationStatus::Waitlisted => (bool) $this->user()?->can('APPROVE_NOMINATION'),
            NominationStatus::Withdrawn => (bool) $this->user()?->can('REVIEW_NOMINATION')
                || (bool) $this->user()?->can('APPROVE_NOMINATION'),
            default => false,
        };
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(NominationStatus::class)],
            'reason' => ['nullable', 'string', 'max:500'],
            'override' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'override' => $this->has('override') ? $this->boolean('override') : false,
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'string' => 'حقل :attribute يجب أن يكون نصًا.',
            'boolean' => 'حقل :attribute يجب أن يكون نعم/لا.',
            'max' => 'حقل :attribute يجب ألا يتجاوز :max حرفًا.',
            'enum' => 'حالة الترشيح المحددة غير صالحة.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'status' => 'الحالة الجديدة',
            'reason' => 'السبب',
            'override' => 'تجاوز معايير الالتحاق',
        ];
    }
}
