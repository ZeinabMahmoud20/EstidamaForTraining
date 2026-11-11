@php
    $editing = isset($invoice) && $invoice && $invoice->exists;
@endphp

<form method="POST" action="{{ $editing ? route('invoices.update', $invoice) : route('invoices.store') }}">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <div class="grid">
        <x-card title="بيانات الفاتورة">
            <div class="form-grid">
                <x-form.select name="session_id" label="الجلسة" :options="$sessions" required
                               :selected="$editing ? $invoice->session_id : null" />

                <x-form.select name="bill_to" label="جهة الفاتورة" :options="$billTo" required
                               :selected="$editing ? $invoice->bill_to?->value : 'trainee'" />

                <x-form.select name="trainee_id" label="المتدرب (عند الفاتورة للمتدرب)" :options="$trainees"
                               placeholder="اختر المتدرب..."
                               :selected="$editing ? $invoice->trainee_id : null" />

                <x-form.select name="provider_id" label="جهة التدريب (عند الفاتورة للجهة)" :options="$providers"
                               placeholder="اختر جهة التدريب..."
                               :selected="$editing ? $invoice->provider_id : null" />

                <x-form.input name="quantity" type="number" min="1" label="الكمية" required
                              :value="$editing ? $invoice->quantity : 1" />

                <x-form.input name="amount" type="number" step="0.01" min="0.01" label="قيمة البند" required
                              :value="$editing ? $invoice->amount : null"
                              hint="الإجمالي = قيمة البند × الكمية + الضريبة." />

                <x-form.input name="tax_amount" type="number" step="0.01" min="0" label="قيمة الضريبة"
                              :value="$editing ? $invoice->tax_amount : 0" />

                <x-form.select name="status" label="حالة الفاتورة" :options="$statuses"
                               :selected="$editing ? $invoice->status?->value : 'draft'" />

                <x-form.input name="issue_date" type="date" label="تاريخ الإصدار"
                              :value="$editing ? $invoice->issue_date?->format('Y-m-d') : now()->toDateString()" />

                <x-form.input name="due_date" type="date" label="تاريخ الاستحقاق"
                              :value="$editing ? $invoice->due_date?->format('Y-m-d') : null" />
            </div>

            @if ($editing)
                <p class="muted" style="margin-block-start:14px">
                    رقم الفاتورة: <span class="num">{{ $invoice->invoice_no }}</span>
                    · الإجمالي الحالي: <span class="num">{{ number_format((float) $invoice->total_amount, 2) }}</span>
                </p>
            @endif
        </x-card>

        <div class="actions" style="justify-content:flex-end">
            <a class="btn btn-ghost" href="{{ $editing ? route('invoices.show', $invoice) : route('invoices.index') }}">إلغاء</a>
            <button type="submit" class="btn">
                <i class="fa-solid fa-floppy-disk"></i> {{ $editing ? 'حفظ التعديلات' : 'إصدار الفاتورة' }}
            </button>
        </div>
    </div>
</form>
