@extends('layouts.app')

@section('title', 'تقارير التقييمات')

@section('content')
    <x-page-header title="تقارير التقييمات" subtitle="متابعة إرسالات التقييم ومتوسطات الدرجات">
        <a class="btn btn-ghost" href="{{ route('evaluations.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع للتقييمات
        </a>
    </x-page-header>

    <div class="grid grid-3" style="margin-block-end:18px">
        <x-stat label="إجمالي الإرسالات" :value="$total" icon="fa-paper-plane" />
        <x-stat label="متوسط الدرجة" :value="$average !== null ? number_format((float) $average, 1).'%' : '—'" icon="fa-chart-line" tone="ok" />
        <x-stat label="النماذج المفعّلة" :value="count($forms)" icon="fa-file-circle-check" />
    </div>

    <x-card title="بحث وتصفية">
        <form method="GET" action="{{ route('evaluations.reports.index') }}" class="filter-grid">
            <x-form.select name="form_id" label="النموذج" :options="$forms" placeholder="كل النماذج"
                           :selected="request('form_id')" />
            <x-form.select name="session_id" label="الجلسة" :options="$sessions" placeholder="كل الجلسات"
                           :selected="request('session_id')" />
            <x-form.select name="target_type" label="الهدف" :options="$targets" placeholder="كل الأهداف"
                           :selected="request('target_type')" />
            <div class="actions full">
                <button class="btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i> تطبيق</button>
                <a class="btn btn-ghost" href="{{ route('evaluations.reports.index') }}">إعادة ضبط</a>
            </div>
        </form>
    </x-card>

    <div style="margin-block-start:18px">
        <x-card :flush="true">
            @if ($submissions->isEmpty())
                <x-empty text="لا توجد إرسالات تقييم مطابقة لبحثك." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>النموذج</th>
                            <th>الجلسة</th>
                            <th>المقيّم</th>
                            <th>الهدف</th>
                            <th>الدرجة</th>
                            <th>تاريخ الإرسال</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($submissions as $submission)
                            @php
                                $target = $submission->targetTrainer?->full_name
                                    ?? $submission->targetEnrollment?->trainee?->full_name
                                    ?? $submission->targetHall?->name
                                    ?? $submission->target_type?->label()
                                    ?? '—';
                                $evaluator = $submission->form?->is_anonymous
                                    ? 'مجهول'
                                    : ($submission->evaluatorUser?->full_name ?? '—');
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $submission->form?->title ?? '—' }}</strong>
                                    <div class="muted" style="font-size:.8rem">{{ $submission->form?->code }}</div>
                                </td>
                                <td>
                                    <div class="num">{{ $submission->session?->session_code ?? '—' }}</div>
                                    <div class="muted" style="font-size:.8rem">{{ $submission->session?->course?->title ?? '—' }}</div>
                                </td>
                                <td>{{ $evaluator }}</td>
                                <td>
                                    {{ $target }}
                                    <div class="muted" style="font-size:.8rem">{{ $submission->target_type?->label() ?? '—' }}</div>
                                </td>
                                <td>
                                    @if ($submission->total_score !== null)
                                        <x-badge :tone="(float) $submission->total_score >= 70 ? 'ok' : 'warn'">
                                            {{ $submission->total_score }}%
                                        </x-badge>
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                                <td class="num">{{ $submission->submitted_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>

                <div style="padding:0 18px 14px">{{ $submissions->links() }}</div>
            @endif
        </x-card>
    </div>
@endsection
