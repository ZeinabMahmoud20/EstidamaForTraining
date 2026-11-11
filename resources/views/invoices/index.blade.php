@extends('layouts.app')

@section('title', 'الفواتير')

@section('content')
    <x-page-header title="الفواتير" subtitle="إصدار فواتير التدريب ومتابعة التحصيل والدفعات">
        @can('TRAINER_PAYMENTS_MANAGE')
            <a class="btn btn-ghost" href="{{ route('trainer-payments.index') }}">
                <i class="fa-solid fa-chalkboard-user"></i> مستحقات المدربين
            </a>
        @endcan
        @can('INVOICES_MANAGE')
            <a class="btn" href="{{ route('invoices.create') }}">
                <i class="fa-solid fa-plus"></i> فاتورة جديدة
            </a>
        @endcan
    </x-page-header>

    <x-card title="بحث وتصفية">
        <form method="GET" action="{{ route('invoices.index') }}" class="filter-grid">
            <x-form.input name="q" label="بحث" :value="request('q')" placeholder="رقم الفاتورة أو اسم المتدرب..." />
            <x-form.select name="session_id" label="الجلسة" :options="$sessions" placeholder="كل الجلسات"
                           :selected="request('session_id')" />
            <x-form.select name="bill_to" label="جهة الفاتورة" :options="$billTo" placeholder="الكل"
                           :selected="request('bill_to')" />
            <x-form.select name="status" label="الحالة" :options="$statuses" placeholder="كل الحالات"
                           :selected="request('status')" />
            <div class="actions full">
                <button class="btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i> تطبيق</button>
                <a class="btn btn-ghost" href="{{ route('invoices.index') }}">إعادة ضبط</a>
            </div>
        </form>
    </x-card>

    <div style="margin-block-start:18px">
        <x-card :flush="true">
            @if ($invoices->isEmpty())
                <x-empty text="لا توجد فواتير مطابقة لبحثك." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>الفاتورة</th>
                            <th>الجهة</th>
                            <th>الجلسة</th>
                            <th>الإجمالي</th>
                            <th>المدفوع</th>
                            <th>الحالة</th>
                            <th>الاستحقاق</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoices as $invoice)
                            <tr>
                                <td>
                                    <a href="{{ route('invoices.show', $invoice) }}">
                                        <strong class="num">{{ $invoice->invoice_no }}</strong>
                                    </a>
                                    <div class="muted" style="font-size:.78rem">{{ $invoice->issue_date?->format('Y-m-d') ?? 'بدون تاريخ إصدار' }}</div>
                                </td>
                                <td>
                                    {{ $invoice->bill_to?->label() ?? '—' }}
                                    <div class="muted" style="font-size:.8rem">
                                        {{ $invoice->bill_to?->value === 'provider'
                                            ? ($invoice->provider?->name ?? '—')
                                            : ($invoice->trainee?->full_name ?? '—') }}
                                    </div>
                                </td>
                                <td>
                                    <div class="num">{{ $invoice->session?->session_code ?? '—' }}</div>
                                    <div class="muted" style="font-size:.8rem">{{ $invoice->session?->course?->title ?? '—' }}</div>
                                </td>
                                <td class="num">{{ number_format((float) $invoice->total_amount, 2) }}</td>
                                <td class="num">{{ number_format((float) ($invoice->payments_sum_amount ?? 0), 2) }}</td>
                                <td><x-badge :tone="$invoice->status?->tone() ?? 'neutral'">{{ $invoice->status?->label() ?? '—' }}</x-badge></td>
                                <td class="num">{{ $invoice->due_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="actions-cell">
                                    <div class="actions" style="justify-content:flex-end">
                                        <a class="btn btn-ghost btn-sm" href="{{ route('invoices.show', $invoice) }}">تفاصيل</a>
                                        @can('INVOICES_MANAGE')
                                            <a class="btn btn-outline btn-sm" href="{{ route('invoices.edit', $invoice) }}">تعديل</a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>

                <div style="padding:0 18px 14px">{{ $invoices->links() }}</div>
            @endif
        </x-card>
    </div>
@endsection
