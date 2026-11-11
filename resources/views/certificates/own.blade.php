@extends('layouts.app')

@section('title', 'شهاداتي')

@section('content')
    <x-page-header title="شهاداتي" subtitle="الشهادات الصادرة لك في البرامج التدريبية">
        <a class="btn btn-ghost" href="{{ route('dashboard') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع للوحة التحكم
        </a>
    </x-page-header>

    <x-card :flush="true">
        @if ($certificates->isEmpty())
            <x-empty text="لا توجد شهادات صادرة لك حتى الآن." />
        @else
            <x-table>
                <thead>
                    <tr>
                        <th>رقم الشهادة</th>
                        <th>الجلسة / الدورة</th>
                        <th>النوع</th>
                        <th>تاريخ الإصدار</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($certificates as $certificate)
                        <tr>
                            <td class="num">{{ $certificate->certificate_no }}</td>
                            <td>
                                <div class="num">{{ $certificate->enrollment?->session?->session_code ?? '—' }}</div>
                                <div class="muted" style="font-size:.8rem">{{ $certificate->enrollment?->session?->course?->title ?? '—' }}</div>
                            </td>
                            <td>{{ $certificate->certificate_type?->label() ?? '—' }}</td>
                            <td class="num">{{ $certificate->issue_date?->format('Y-m-d') ?? '—' }}</td>
                            <td><x-badge :tone="$certificate->status?->tone() ?? 'neutral'">{{ $certificate->status?->label() ?? '—' }}</x-badge></td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        @endif
    </x-card>
@endsection
