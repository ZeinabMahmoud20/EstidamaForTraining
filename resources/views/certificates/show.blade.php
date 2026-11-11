@extends('layouts.app')

@section('title', $certificate->certificate_no)

@section('content')
    <x-page-header :title="$certificate->certificate_no"
                   :subtitle="($certificate->certificate_type?->label() ?? '—').' · '.($certificate->enrollment?->trainee?->full_name ?? '—')">
        <a class="btn btn-ghost" href="{{ route('certificates.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع للشهادات
        </a>
        <a class="btn btn-outline" href="{{ route('certificates.print', $certificate) }}" target="_blank">
            <i class="fa-solid fa-print"></i> طباعة
        </a>
    </x-page-header>

    <div class="grid grid-2">
        <x-card title="بيانات الشهادة">
            <dl class="details">
                <div><dt>رقم الشهادة</dt><dd class="num">{{ $certificate->certificate_no }}</dd></div>
                <div><dt>الحالة</dt><dd><x-badge :tone="$certificate->status?->tone() ?? 'neutral'">{{ $certificate->status?->label() ?? '—' }}</x-badge></dd></div>
                <div><dt>النوع</dt><dd>{{ $certificate->certificate_type?->label() ?? '—' }}</dd></div>
                <div><dt>القالب</dt><dd>{{ $certificate->template?->name ?? 'قالب افتراضي' }}</dd></div>
                <div><dt>تاريخ الإصدار</dt><dd class="num">{{ $certificate->issue_date?->format('Y-m-d') ?? '—' }}</dd></div>
                <div><dt>تاريخ الانتهاء</dt><dd class="num">{{ $certificate->expiry_date?->format('Y-m-d') ?? '—' }}</dd></div>
                <div><dt>أصدرها</dt><dd>{{ $certificate->issuedBy?->full_name ?? '—' }}</dd></div>
                <div><dt>المتدرب</dt><dd>{{ $certificate->enrollment?->trainee?->full_name ?? '—' }}</dd></div>
                <div><dt>الجلسة</dt><dd class="num">{{ $certificate->enrollment?->session?->session_code ?? '—' }}</dd></div>
                <div><dt>الدورة</dt><dd>{{ $certificate->enrollment?->session?->course?->title ?? '—' }}</dd></div>
            </dl>
        </x-card>

        <x-card title="التحقق من الشهادة">
            <div class="field">
                <label>رمز التحقق</label>
                <input class="control num" value="{{ $certificate->verification_code }}" readonly>
            </div>
            <p class="muted">
                يمكن التحقق من الشهادة عبر الرابط:
                <a href="{{ route('certificates.verify', $certificate->verification_code) }}" target="_blank">
                    {{ route('certificates.verify', $certificate->verification_code) }}
                </a>
            </p>

            @if ($certificate->status?->value === 'revoked')
                <x-alert type="error">
                    أُلغيت هذه الشهادة بتاريخ {{ $certificate->revoked_at?->format('Y-m-d H:i') ?? '—' }}
                    بواسطة {{ $certificate->revokedBy?->full_name ?? '—' }}.
                    السبب: {{ $certificate->revoke_reason ?: '—' }}
                </x-alert>
            @endif

            @can('REVOKE_CERTIFICATE')
                @if ($certificate->status?->value === 'issued')
                    <form method="POST" action="{{ route('certificates.revoke', $certificate) }}"
                          data-confirm="هل تريد إلغاء هذه الشهادة؟">
                        @csrf
                        @method('PATCH')
                        <x-form.input name="revoke_reason" label="سبب الإلغاء" required wrapper="full" />
                        <div class="actions" style="margin-block-start:12px;justify-content:flex-end">
                            <button type="submit" class="btn btn-danger">
                                <i class="fa-solid fa-ban"></i> إلغاء الشهادة
                            </button>
                        </div>
                    </form>
                @endif
            @endcan
        </x-card>
    </div>
@endsection
