<?php

namespace App\Http\Requests;

use App\Enums\DeliveryMode;
use App\Enums\TrainerRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared validation for creating and updating a scheduled course session,
 * including its days and planned trainers.
 */
abstract class SessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('SESSIONS_MANAGE');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $sessionId = $this->route('session')?->id;

        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'session_code' => [
                'required', 'string', 'max:40',
                Rule::unique('course_sessions', 'session_code')->ignore($sessionId),
            ],
            'provider_id' => ['nullable', 'integer', 'exists:training_providers,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'accreditation_id' => ['nullable', 'integer', 'exists:accreditations,id'],
            'delivery_mode' => ['required', Rule::enum(DeliveryMode::class)],
            'online_url' => ['nullable', 'url', 'max:500', 'required_if:delivery_mode,online'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'registration_open' => ['nullable', 'date'],
            'registration_close' => ['nullable', 'date', 'after_or_equal:registration_open'],
            'max_trainees' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],

            'days' => ['nullable', 'array', 'max:60'],
            'days.*.id' => ['nullable', 'integer', 'min:1', 'distinct'],
            'days.*.day_no' => ['nullable', 'integer', 'min:1', 'max:60', 'distinct'],
            'days.*.day_date' => ['required', 'date'],
            'days.*.start_time' => ['required', 'date_format:H:i'],
            'days.*.end_time' => ['required', 'date_format:H:i'],
            'days.*.hall_id' => ['nullable', 'integer', 'exists:halls,id'],
            'days.*.online_url' => ['nullable', 'url', 'max:500'],

            'trainers' => ['nullable', 'array', 'max:30'],
            'trainers.*.trainer_id' => ['required', 'integer', 'distinct', 'exists:trainers,id'],
            'trainers.*.planned_role' => ['required', Rule::enum(TrainerRole::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('days')) {
            $days = collect((array) $this->input('days', []))
                ->filter(fn ($row) => is_array($row) && collect(['day_no', 'day_date', 'start_time', 'end_time', 'hall_id', 'online_url'])
                    ->contains(fn ($key) => filled($row[$key] ?? null)))
                ->map(function (array $row) {
                    foreach (['hall_id', 'online_url'] as $key) {
                        if (($row[$key] ?? null) === '') {
                            $row[$key] = null;
                        }
                    }

                    return $row;
                })
                ->values()
                ->all();

            $this->merge(['days' => $days]);
        }

        if ($this->has('trainers')) {
            $trainers = collect((array) $this->input('trainers', []))
                ->filter(fn ($row) => is_array($row) && filled($row['trainer_id'] ?? null))
                ->values()
                ->all();

            $this->merge(['trainers' => $trainers]);
        }
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'required_if' => 'حقل :attribute مطلوب عندما تكون طريقة التنفيذ عن بعد.',
            'string' => 'حقل :attribute يجب أن يكون نصًا.',
            'integer' => 'حقل :attribute يجب أن يكون عددًا صحيحًا.',
            'numeric' => 'حقل :attribute يجب أن يكون رقمًا.',
            'date' => 'حقل :attribute يجب أن يكون تاريخًا صحيحًا.',
            'date_format' => 'حقل :attribute يجب أن يكون بصيغة صحيحة.',
            'after_or_equal' => 'حقل :attribute يجب أن يكون بعد أو مساويًا للقيمة المرتبطة به.',
            'min' => 'حقل :attribute يجب ألا يقل عن :min.',
            'max' => 'حقل :attribute يجب ألا يزيد عن :max.',
            'exists' => 'القيمة المحددة في :attribute غير موجودة.',
            'unique' => 'كود الجلسة مستخدم بالفعل.',
            'distinct' => 'حقل :attribute يحتوي على قيمة مكررة.',
            'url' => 'حقل :attribute يجب أن يكون رابطًا صحيحًا.',
            'max.string' => 'حقل :attribute يجب ألا يتجاوز :max حرفًا.',
            'max.array' => 'حقل :attribute يجب ألا يحتوي أكثر من :max عنصرًا.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'course_id' => 'الدورة',
            'session_code' => 'كود الجلسة',
            'provider_id' => 'جهة التدريب',
            'branch_id' => 'الفرع',
            'accreditation_id' => 'الاعتماد',
            'delivery_mode' => 'طريقة التنفيذ',
            'online_url' => 'رابط الجلسة',
            'start_date' => 'تاريخ البداية',
            'end_date' => 'تاريخ النهاية',
            'registration_open' => 'بداية التسجيل',
            'registration_close' => 'نهاية التسجيل',
            'max_trainees' => 'الحد الأقصى للمتدربين',
            'price' => 'السعر',
            'days' => 'أيام الجلسة',
            'days.*.id' => 'معرّف اليوم',
            'days.*.day_no' => 'رقم اليوم',
            'days.*.day_date' => 'تاريخ اليوم',
            'days.*.start_time' => 'وقت البداية',
            'days.*.end_time' => 'وقت النهاية',
            'days.*.hall_id' => 'القاعة',
            'days.*.online_url' => 'رابط اليوم',
            'trainers' => 'المدربون',
            'trainers.*.trainer_id' => 'المدرب',
            'trainers.*.planned_role' => 'دور المدرب',
        ];
    }
}
