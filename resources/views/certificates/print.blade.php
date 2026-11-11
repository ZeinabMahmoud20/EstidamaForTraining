<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>شهادة {{ $certificate->certificate_no }}</title>
    <style>
        :root { --brand: #14406f; --gold: #b48a3c; --ink: #1c2733; --line: #d9dee5; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 28px; font-family: "Segoe UI", Tahoma, Arial, sans-serif; color: var(--ink); background: #f3f5f8; }
        .toolbar { max-width: 920px; margin: 0 auto 16px; display: flex; justify-content: space-between; align-items: center; }
        .toolbar a, .toolbar button { border: 1px solid var(--brand); background: #fff; color: var(--brand); padding: 9px 16px; border-radius: 6px; text-decoration: none; cursor: pointer; font: inherit; }
        .certificate { max-width: 920px; margin: 0 auto; background: #fff; border: 10px solid var(--brand); outline: 2px solid var(--gold); outline-offset: -18px; padding: 48px 56px; min-height: 620px; display: flex; flex-direction: column; justify-content: center; text-align: center; }
        .certificate h1 { color: var(--brand); font-size: 2rem; margin: 0 0 8px; }
        .certificate .type { color: var(--gold); font-size: 1.15rem; font-weight: 700; margin-block-end: 28px; }
        .certificate .lead { font-size: 1.05rem; color: #55616d; }
        .certificate .name { font-size: 2rem; font-weight: 800; color: var(--brand); margin: 12px 0; }
        .certificate .course { font-size: 1.15rem; font-weight: 700; margin: 10px 0 22px; }
        .certificate .meta { display: flex; justify-content: space-between; gap: 20px; margin-block-start: 38px; font-size: .88rem; color: #55616d; }
        .certificate .meta div { text-align: start; }
        .certificate .code { margin-block-start: 26px; font-size: .75rem; color: #7a8590; word-break: break-all; }
        .certificate .revoked { margin-block-start: 18px; color: #b42318; font-weight: 700; }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .certificate { border-width: 8px; box-shadow: none; max-width: none; min-height: 96vh; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <strong>شهادة {{ $certificate->certificate_no }}</strong>
        <button type="button" onclick="window.print()">طباعة</button>
    </div>

    @if ($renderedHtml)
        <div class="certificate">{!! $renderedHtml !!}</div>
    @else
        <div class="certificate">
            <h1>شهادة {{ $certificate->certificate_type?->label() ?? '' }}</h1>
            <div class="type">استدامة للتدريب والتطوير</div>

            <p class="lead">تشهد إدارة المركز بأن</p>
            <div class="name">{{ $certificate->enrollment?->trainee?->full_name ?? '—' }}</div>
            <p class="lead">قد أتم بنجاح البرنامج التدريبي</p>
            <div class="course">{{ $certificate->enrollment?->session?->course?->title ?? '—' }}</div>

            <p class="lead">
                المنعقد ضمن الجلسة <span class="num">{{ $certificate->enrollment?->session?->session_code ?? '—' }}</span>
                @if ($certificate->enrollment?->session?->provider)
                    بالتعاون مع {{ $certificate->enrollment->session->provider->name }}
                @endif
            </p>

            <div class="meta">
                <div>
                    <strong>تاريخ الإصدار</strong><br>
                    <span class="num">{{ $certificate->issue_date?->format('Y-m-d') ?? '—' }}</span>
                </div>
                <div>
                    <strong>رقم الشهادة</strong><br>
                    <span class="num">{{ $certificate->certificate_no }}</span>
                </div>
                <div>
                    <strong>أصدرها</strong><br>
                    {{ $certificate->issuedBy?->full_name ?? '—' }}
                </div>
            </div>

            @if ($certificate->status?->value === 'revoked')
                <div class="revoked">هذه الشهادة ملغاة — {{ $certificate->revoke_reason }}</div>
            @endif

            <div class="code">للتحقق: {{ route('certificates.verify', $certificate->verification_code) }}</div>
        </div>
    @endif
</body>
</html>
