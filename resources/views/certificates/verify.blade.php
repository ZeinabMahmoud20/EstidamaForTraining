@extends('layouts.guest')

@section('title', 'التحقق من شهادة')

@section('content')
    <h1 style="margin-block-start:0;font-size:1.3rem">التحقق من شهادة</h1>

    @if ($certificate)
        <x-alert type="{{ $certificate->status?->value === 'issued' ? 'success' : 'error' }}">
            {{ $certificate->status?->value === 'issued' ? 'الشهادة صادرة وصحيحة.' : 'هذه الشهادة ملغاة وغير سارية.' }}
        </x-alert>

        <dl class="details" style="grid-template-columns:1fr;margin-block-start:14px">
            <div><dt>رقم الشهادة</dt><dd class="num">{{ $certificate->certificate_no }}</dd></div>
            <div><dt>المتدرب</dt><dd>{{ $certificate->enrollment?->trainee?->full_name ?? '—' }}</dd></div>
            <div><dt>الدورة</dt><dd>{{ $certificate->enrollment?->session?->course?->title ?? '—' }}</dd></div>
            <div><dt>الجلسة</dt><dd class="num">{{ $certificate->enrollment?->session?->session_code ?? '—' }}</dd></div>
            <div><dt>النوع</dt><dd>{{ $certificate->certificate_type?->label() ?? '—' }}</dd></div>
            <div><dt>تاريخ الإصدار</dt><dd class="num">{{ $certificate->issue_date?->format('Y-m-d') ?? '—' }}</dd></div>
        </dl>

        @if ($certificate->status?->value === 'revoked')
            <x-alert type="error" style="margin-block-start:14px">
                سبب الإلغاء: {{ $certificate->revoke_reason ?: '—' }}
            </x-alert>
        @endif
    @else
        <x-alert type="error">لا توجد شهادة بهذا الرمز. تأكد من صحة رابط التحقق.</x-alert>
        <p class="muted num" style="word-break:break-all">{{ $code }}</p>
    @endif

    <div class="actions" style="margin-block-start:18px">
        <a class="btn btn-ghost" href="{{ route('login') }}">العودة لتسجيل الدخول</a>
    </div>
@endsection
