<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RevokeCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('REVOKE_CERTIFICATE');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'revoke_reason' => ['required', 'string', 'max:300'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'string' => 'حقل :attribute يجب أن يكون نصًا.',
            'max' => 'حقل :attribute يجب ألا يتجاوز :max حرفًا.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['revoke_reason' => 'سبب الإلغاء'];
    }
}
