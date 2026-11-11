<?php

namespace App\Http\Requests;

use App\Enums\AttendanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('ATTENDANCE_RECORD');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'trainees' => ['nullable', 'array'],
            'trainees.*.status' => ['nullable', Rule::enum(AttendanceStatus::class)],
            'trainees.*.late_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'trainees.*.notes' => ['nullable', 'string', 'max:300'],
            'trainees.*.excuse_reason' => ['nullable', 'string', 'max:1000'],

            'trainers' => ['nullable', 'array'],
            'trainers.*.status' => ['nullable', Rule::enum(AttendanceStatus::class)],
            'trainers.*.late_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'trainers.*.notes' => ['nullable', 'string', 'max:300'],
            'trainers.*.excuse_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['trainees', 'trainers'] as $group) {
            $rows = (array) $this->input($group, []);

            $this->merge([
                $group => collect($rows)->map(function ($row) {
                    if (! is_array($row)) {
                        return $row;
                    }

                    foreach (['status', 'late_minutes', 'notes', 'excuse_reason'] as $key) {
                        if (($row[$key] ?? null) === '') {
                            $row[$key] = null;
                        }
                    }

                    return $row;
                })->all(),
            ]);
        }
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'integer' => 'حقل :attribute يجب أن يكون عددًا صحيحًا.',
            'min' => 'حقل :attribute يجب ألا يقل عن :min.',
            'max' => 'حقل :attribute يجب ألا يتجاوز :max.',
            'string' => 'حقل :attribute يجب أن يكون نصًا.',
            'enum' => 'حالة الحضور المحددة غير صالحة.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'trainees.*.status' => 'حالة حضور المتدرب',
            'trainees.*.late_minutes' => 'دقائق التأخير',
            'trainees.*.notes' => 'ملاحظات المتدرب',
            'trainees.*.excuse_reason' => 'سبب العذر',
            'trainers.*.status' => 'حالة حضور المدرب',
            'trainers.*.late_minutes' => 'دقائق تأخير المدرب',
            'trainers.*.notes' => 'ملاحظات المدرب',
            'trainers.*.excuse_reason' => 'سبب عذر المدرب',
        ];
    }
}
