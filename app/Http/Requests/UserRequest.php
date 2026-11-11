<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Shared validation for creating and updating a system user.
 */
abstract class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('USERS_MANAGE');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'username' => [
                'required', 'string', 'max:80', 'regex:/^[a-z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'password' => $userId
                ? ['nullable', 'string', 'min:8', 'max:255', 'confirmed']
                : ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'full_name' => ['nullable', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:150'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'trainer_id' => ['nullable', 'integer', 'exists:trainers,id'],
            'trainee_id' => ['nullable', 'integer', 'exists:trainees,id'],
            'is_active' => ['required', 'boolean'],
            'roles' => ['nullable', 'array', 'max:20'],
            'roles.*' => ['string', 'exists:roles,name'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $username = $this->input('username');

        if (is_string($username)) {
            $this->merge(['username' => Str::lower(trim($username))]);
        }

        $this->merge([
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : true,
            'roles' => array_values(array_filter(
                (array) $this->input('roles', []),
                fn ($value) => filled($value)
            )),
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'string' => 'حقل :attribute يجب أن يكون نصًا.',
            'integer' => 'حقل :attribute يجب أن يكون عددًا صحيحًا.',
            'boolean' => 'حقل :attribute يجب أن يكون نعم/لا.',
            'email' => 'حقل :attribute يجب أن يكون بريدًا إلكترونيًا صحيحًا.',
            'array' => 'حقل :attribute غير صالح.',
            'confirmed' => 'تأكيد :attribute غير مطابق.',
            'alpha_dash' => 'حقل :attribute يسمح بالحروف والأرقام والشرطات فقط.',
            'regex' => 'حقل :attribute يسمح بالحروف الإنجليزية الصغيرة والأرقام والنقطة والشرطة فقط.',
            'max.string' => 'حقل :attribute يجب ألا يتجاوز :max حرفًا.',
            'min.string' => 'حقل :attribute يجب ألا يقل عن :min أحرف.',
            'unique' => 'قيمة :attribute مستخدمة بالفعل.',
            'exists' => 'القيمة المحددة في :attribute غير موجودة.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'username' => 'اسم المستخدم',
            'password' => 'كلمة المرور',
            'full_name' => 'الاسم الكامل',
            'email' => 'البريد الإلكتروني',
            'branch_id' => 'الفرع',
            'trainer_id' => 'ملف المدرب',
            'trainee_id' => 'ملف المتدرب',
            'is_active' => 'حالة المستخدم',
            'roles' => 'الأدوار',
            'roles.*' => 'الدور',
        ];
    }
}
