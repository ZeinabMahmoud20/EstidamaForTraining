<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeSessionHallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('CHANGE_HALL');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'day_id' => ['required', 'integer', 'exists:session_days,id'],
            'hall_id' => ['nullable', 'integer', 'exists:halls,id'],
            'reason' => ['nullable', 'string', 'max:300'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'integer' => 'حقل :attribute يجب أن يكون عددًا صحيحًا.',
            'exists' => 'القيمة المحددة في :attribute غير موجودة.',
            'string' => 'حقل :attribute يجب أن يكون نصًا.',
            'max' => 'حقل :attribute يجب ألا يتجاوز :max حرفًا.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'day_id' => 'يوم الجلسة',
            'hall_id' => 'القاعة الجديدة',
            'reason' => 'سبب التغيير',
        ];
    }
}
