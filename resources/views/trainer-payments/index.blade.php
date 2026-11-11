@extends('layouts.app')

@section('title', 'مستحقات المدربين')

@section('content')
    <x-page-header title="مستحقات المدربين" subtitle="احتساب واعتماد وصرف مستحقات المدربين عن الجلسات">
        <a class="btn btn-ghost" href="{{ route('invoices.index') }}">
            <i class="fa-solid fa-file-invoice-dollar"></i> الفواتير
        </a>
        <a class="btn" href="{{ route('trainer-payments.create') }}">
            <i class="fa-solid fa-plus"></i> مستحق جديد
        </a>
    </x-page-header>

    <x-card title="بحث وتصفية">
        <form method="GET" action="{{ route('trainer-payments.index') }}" class="filter-grid">
            <x-form.input name="q" label="بحث" :value="request('q')" placeholder="اسم المدرب..." />
            <x-form.select name="session_id" label="الجلسة" :options="$sessions" placeholder="كل الجلسات"
                           :selected="request('session_id')" />
            <x-form.select name="status" label="الحالة" :options="$statuses" placeholder="كل الحالات"
                           :selected="request('status')" />
            <div class="actions full">
                <button class="btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i> تطبيق</button>
                <a class="btn btn-ghost" href="{{ route('trainer-payments.index') }}">إعادة ضبط</a>
            </div>
        </form>
    </x-card>

    <div style="margin-block-start:18px">
        <x-card :flush="true">
            @if ($payments->isEmpty())
                <x-empty text="لا توجد مستحقات مطابقة لبحثك." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>المدرب</th>
                            <th>الجلسة</th>
                            <th>الساعات</th>
                            <th>سعر الساعة</th>
                            <th>المبلغ</th>
                            <th>الحالة</th>
                            <th>الاعتماد / الصرف</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr>
                                <td><strong>{{ $payment->trainer?->full_name ?? '—' }}</strong></td>
                                <td>
                                    <div class="num">{{ $payment->session?->session_code ?? '—' }}</div>
                                    <div class="muted" style="font-size:.8rem">{{ $payment->session?->course?->title ?? '—' }}</div>
                                </td>
                                <td class="num">{{ $payment->total_hours ?? '—' }}</td>
                                <td class="num">{{ $payment->hourly_rate ?? '—' }}</td>
                                <td class="num">{{ $payment->amount !== null ? number_format((float) $payment->amount, 2) : '—' }}</td>
                                <td>
                                    <x-badge :tone="$payment->status?->tone() ?? 'neutral'">{{ $payment->status?->label() ?? '—' }}</x-badge>
                                    @if ($payment->paid_at)
                                        <div class="muted" style="font-size:.78rem">{{ $payment->paid_at->format('Y-m-d H:i') }}</div>
                                    @endif
                                </td>
                                <td style="min-width:190px">
                                    @if ($payment->status?->value !== 'paid')
                                        <form method="POST" action="{{ route('trainer-payments.status', $payment) }}">
                                            @csrf
                                            @method('PATCH')
                                            <div style="display:flex;gap:8px;align-items:center">
                                                <select name="status" class="control">
                                                    @if ($payment->status?->value === 'pending')
                                                        <option value="approved">اعتماد</option>
                                                    @endif
                                                    <option value="paid">صرف</option>
                                                </select>
                                                <button type="submit" class="btn btn-sm">حفظ</button>
                                            </div>
                                        </form>
                                    @else
                                        <span class="muted">تم الصرف</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>

                <div style="padding:0 18px 14px">{{ $payments->links() }}</div>
            @endif
        </x-card>
    </div>
@endsection
