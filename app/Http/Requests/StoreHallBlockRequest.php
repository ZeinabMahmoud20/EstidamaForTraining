<?php

namespace App\Http\Requests;

use App\Enums\HallBlockReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHallBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('HALL_BLOCK');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'reason' => ['nullable', Rule::enum(HallBlockReason::class)],
            'notes' => ['nullable', 'string', 'max:300'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'date' => 'حقل :attribute يجب أن يكون تاريخًا ووقتًا صحيحًا.',
            'after' => 'حقل :attribute يجب أن يكون بعد وقت البداية.',
            'string' => 'حقل :attribute يجب أن يكون نصًا.',
            'max' => 'حقل :attribute يجب ألا يتجاوز :max حرفًا.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'start_at' => 'وقت البداية',
            'end_at' => 'وقت النهاية',
            'reason' => 'سبب الحجب',
            'notes' => 'ملاحظات',
        ];
    }
}
