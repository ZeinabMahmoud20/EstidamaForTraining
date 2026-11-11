@extends('layouts.app')

@section('title', $invoice->invoice_no)

@php
    $remaining = max(0, (float) $invoice->total_amount - $paid);
    $methods = ['cash' => 'نقدي', 'bank_transfer' => 'تحويل بنكي', 'cheque' => 'شيك', 'card' => 'بطاقة'];
@endphp

@section('content')
    <x-page-header :title="$invoice->invoice_no"
                   :subtitle="($invoice->bill_to?->label() ?? '—').' · '.($invoice->session?->session_code ?? '—').' · '.($invoice->session?->course?->title ?? '—')">
        <a class="btn btn-ghost" href="{{ route('invoices.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع للفواتير
        </a>
        @can('INVOICES_MANAGE')
            <a class="btn btn-outline" href="{{ route('invoices.edit', $invoice) }}">
                <i class="fa-solid fa-pen"></i> تعديل
            </a>
            <form method="POST" action="{{ route('invoices.destroy', $invoice) }}"
                  data-confirm="هل تريد حذف الفاتورة {{ $invoice->invoice_no }}؟">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit"><i class="fa-solid fa-trash"></i> حذف</button>
            </form>
        @endcan
    </x-page-header>

    <div class="grid grid-4" style="margin-block-end:18px">
        <x-stat label="الإجمالي" :value="number_format((float) $invoice->total_amount, 2)" icon="fa-file-invoice-dollar" />
        <x-stat label="المدفوع" :value="number_format($paid, 2)" icon="fa-money-bill-wave" tone="ok" />
        <x-stat label="المتبقي" :value="number_format($remaining, 2)" icon="fa-hourglass-half" :tone="$remaining > 0 ? 'warn' : 'ok'" />
        <x-stat label="الحالة" :value="$invoice->status?->label() ?? '—'" icon="fa-circle-info" />
    </div>

    <div class="grid grid-2">
        <x-card title="بيانات الفاتورة">
            <dl class="details">
                <div><dt>رقم الفاتورة</dt><dd class="num">{{ $invoice->invoice_no }}</dd></div>
                <div><dt>الحالة</dt><dd><x-badge :tone="$invoice->status?->tone() ?? 'neutral'">{{ $invoice->status?->label() ?? '—' }}</x-badge></dd></div>
                <div><dt>جهة الفاتورة</dt><dd>{{ $invoice->bill_to?->label() ?? '—' }}</dd></div>
                <div>
                    <dt>{{ $invoice->bill_to?->value === 'provider' ? 'جهة التدريب' : 'المتدرب' }}</dt>
                    <dd>{{ $invoice->bill_to?->value === 'provider'
                        ? ($invoice->provider?->name ?? '—')
                        : ($invoice->trainee?->full_name ?? '—') }}</dd>
                </div>
                <div><dt>الجلسة</dt><dd class="num">{{ $invoice->session?->session_code ?? '—' }}</dd></div>
                <div><dt>الدورة</dt><dd>{{ $invoice->session?->course?->title ?? '—' }}</dd></div>
                <div><dt>الكمية × قيمة البند</dt><dd class="num">{{ $invoice->quantity }} × {{ number_format((float) $invoice->amount, 2) }}</dd></div>
                <div><dt>الضريبة</dt><dd class="num">{{ number_format((float) $invoice->tax_amount, 2) }}</dd></div>
                <div><dt>تاريخ الإصدار</dt><dd class="num">{{ $invoice->issue_date?->format('Y-m-d') ?? '—' }}</dd></div>
                <div><dt>تاريخ الاستحقاق</dt><dd class="num">{{ $invoice->due_date?->format('Y-m-d') ?? '—' }}</dd></div>
            </dl>
        </x-card>

        @can('PAYMENTS_MANAGE')
            <x-card title="تسجيل دفعة">
                @if ($invoice->status?->value === 'cancelled')
                    <x-alert type="warning">الفاتورة ملغاة ولا يمكن تسجيل دفعات عليها.</x-alert>
                @elseif ($remaining <= 0)
                    <x-alert type="success">تم سداد الفاتورة بالكامل.</x-alert>
                @else
                    <form method="POST" action="{{ route('invoices.payments.store', $invoice) }}">
                        @csrf
                        <div class="form-grid">
                            <x-form.input name="amount" type="number" step="0.01" min="0.01" :max="$remaining"
                                          label="قيمة الدفعة" required :value="$remaining" />
                            <x-form.select name="method" label="طريقة الدفع" :options="$methods" />
                            <x-form.input name="reference_no" label="رقم المرجع" />
                            <x-form.input name="paid_at" type="datetime-local" label="تاريخ الدفع"
                                          :value="now()->format('Y-m-d\TH:i')" />
                        </div>
                        <div class="actions" style="margin-block-start:14px;justify-content:flex-end">
                            <button type="submit" class="btn">
                                <i class="fa-solid fa-plus"></i> تسجيل الدفعة
                            </button>
                        </div>
                    </form>
                @endif
            </x-card>
        @endcan
    </div>

    <div style="margin-block-start:18px">
        <x-card title="الدفعات" :flush="true">
            @if ($invoice->payments->isEmpty())
                <x-empty text="لا توجد دفعات مسجّلة على هذه الفاتورة." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>المبلغ</th>
                            <th>طريقة الدفع</th>
                            <th>رقم المرجع</th>
                            <th>تاريخ الدفع</th>
                            <th>استلمها</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoice->payments as $payment)
                            <tr>
                                <td class="num">{{ $loop->iteration }}</td>
                                <td class="num">{{ number_format((float) $payment->amount, 2) }}</td>
                                <td>{{ $methods[$payment->method] ?? ($payment->method ?: '—') }}</td>
                                <td class="num">{{ $payment->reference_no ?: '—' }}</td>
                                <td class="num">{{ $payment->paid_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td>{{ $payment->receivedBy?->full_name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>
    </div>
@endsection
