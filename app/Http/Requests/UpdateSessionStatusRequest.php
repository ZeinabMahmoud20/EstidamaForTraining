<?php

namespace App\Http\Requests;

use App\Enums\SessionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSessionStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('SESSIONS_PUBLISH');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(SessionStatus::class)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'status.required' => 'اختر الحالة الجديدة.',
            'status.enum' => 'الحالة المحددة غير صحيحة.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'status' => 'حالة الجلسة',
        ];
    }
}
