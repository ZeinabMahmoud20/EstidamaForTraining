<?php

namespace App\Http\Requests;

use App\Enums\BillTo;
use App\Enums\InvoiceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('INVOICES_MANAGE');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'session_id' => ['required', 'integer', 'exists:course_sessions,id'],
            'bill_to' => ['required', Rule::enum(BillTo::class)],
            'trainee_id' => ['nullable', 'required_if:bill_to,trainee', 'integer', 'exists:trainees,id'],
            'provider_id' => ['nullable', 'required_if:bill_to,provider', 'integer', 'exists:training_providers,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'tax_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'status' => ['nullable', Rule::enum(InvoiceStatus::class)],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'required_if' => 'حقل :attribute مطلوب عند اختيار جهة الفاتورة.',
            'integer' => 'حقل :attribute يجب أن يكون عددًا صحيحًا.',
            'numeric' => 'حقل :attribute يجب أن يكون رقمًا.',
            'exists' => 'القيمة المحددة في :attribute غير موجودة.',
            'min' => 'حقل :attribute يجب ألا يقل عن :min.',
            'max' => 'حقل :attribute يجب ألا يتجاوز :max.',
            'date' => 'حقل :attribute يجب أن يكون تاريخًا صالحًا.',
            'after_or_equal' => 'حقل :attribute يجب أن يكون بعد أو يساوي تاريخ الإصدار.',
            'enum' => 'القيمة المحددة في :attribute غير صالحة.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'session_id' => 'الجلسة',
            'bill_to' => 'جهة الفاتورة',
            'trainee_id' => 'المتدرب',
            'provider_id' => 'جهة التدريب',
            'quantity' => 'الكمية',
            'amount' => 'قيمة البند',
            'tax_amount' => 'قيمة الضريبة',
            'status' => 'حالة الفاتورة',
            'issue_date' => 'تاريخ الإصدار',
            'due_date' => 'تاريخ الاستحقاق',
        ];
    }
}
