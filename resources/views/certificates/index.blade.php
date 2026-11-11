@extends('layouts.app')

@section('title', 'الشهادات')

@section('content')
    <x-page-header title="الشهادات" subtitle="إصدار شهادات الحضور والإتمام والتقدير ومتابعة حالتها">
        @can('ISSUE_CERTIFICATE')
            <a class="btn btn-ghost" href="{{ route('certificates.templates.index') }}">
                <i class="fa-solid fa-layer-group"></i> قوالب الشهادات
            </a>
            <a class="btn" href="{{ route('certificates.create') }}">
                <i class="fa-solid fa-plus"></i> إصدار شهادة
            </a>
        @endcan
    </x-page-header>

    <x-card title="بحث وتصفية">
        <form method="GET" action="{{ route('certificates.index') }}" class="filter-grid">
            <x-form.input name="q" label="بحث" :value="request('q')" placeholder="رقم الشهادة أو اسم المتدرب..." />
            <x-form.select name="session_id" label="الجلسة" :options="$sessions" placeholder="كل الجلسات"
                           :selected="request('session_id')" />
            <x-form.select name="certificate_type" label="النوع" :options="$types" placeholder="كل الأنواع"
                           :selected="request('certificate_type')" />
            <x-form.select name="status" label="الحالة" :options="$statuses" placeholder="كل الحالات"
                           :selected="request('status')" />
            <div class="actions full">
                <button class="btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i> تطبيق</button>
                <a class="btn btn-ghost" href="{{ route('certificates.index') }}">إعادة ضبط</a>
            </div>
        </form>
    </x-card>

    <div style="margin-block-start:18px">
        <x-card :flush="true">
            @if ($certificates->isEmpty())
                <x-empty text="لا توجد شهادات مطابقة لبحثك." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>رقم الشهادة</th>
                            <th>المتدرب</th>
                            <th>الجلسة</th>
                            <th>النوع</th>
                            <th>تاريخ الإصدار</th>
                            <th>الحالة</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($certificates as $certificate)
                            <tr>
                                <td>
                                    <a href="{{ route('certificates.show', $certificate) }}">
                                        <strong class="num">{{ $certificate->certificate_no }}</strong>
                                    </a>
                                    <div class="muted" style="font-size:.78rem">{{ $certificate->template?->name ?? 'قالب افتراضي' }}</div>
                                </td>
                                <td>
                                    {{ $certificate->enrollment?->trainee?->full_name ?? '—' }}
                                    <div class="muted num" style="font-size:.8rem">{{ $certificate->enrollment?->trainee?->national_id ?? '' }}</div>
                                </td>
                                <td>
                                    <div class="num">{{ $certificate->enrollment?->session?->session_code ?? '—' }}</div>
                                    <div class="muted" style="font-size:.8rem">{{ $certificate->enrollment?->session?->course?->title ?? '—' }}</div>
                                </td>
                                <td>{{ $certificate->certificate_type?->label() ?? '—' }}</td>
                                <td class="num">{{ $certificate->issue_date?->format('Y-m-d') ?? '—' }}</td>
                                <td><x-badge :tone="$certificate->status?->tone() ?? 'neutral'">{{ $certificate->status?->label() ?? '—' }}</x-badge></td>
                                <td class="actions-cell">
                                    <div class="actions" style="justify-content:flex-end">
                                        <a class="btn btn-ghost btn-sm" href="{{ route('certificates.show', $certificate) }}">تفاصيل</a>
                                        <a class="btn btn-outline btn-sm" href="{{ route('certificates.print', $certificate) }}" target="_blank">طباعة</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>

                <div style="padding:0 18px 14px">{{ $certificates->links() }}</div>
            @endif
        </x-card>
    </div>
@endsection
