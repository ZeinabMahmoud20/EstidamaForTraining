@extends('layouts.app')

@section('title', 'الترشيحات')

@section('content')
    <x-page-header title="الترشيحات" subtitle="ترشيحات المتدربين للجلسات ومتابعة حالتها حتى الاعتماد والتسجيل">
        @can('NOMINATE_TRAINEE')
            <a class="btn" href="{{ route('nominations.create') }}">
                <i class="fa-solid fa-plus"></i> ترشيح جديد
            </a>
        @endcan
    </x-page-header>

    <x-card title="بحث وتصفية">
        <form method="GET" action="{{ route('nominations.index') }}" class="filter-grid">
            <x-form.input name="q" label="بحث" :value="request('q')" placeholder="اسم المتدرب أو الرقم القومي..." />
            <x-form.select name="session_id" label="الجلسة" :options="$sessions" placeholder="كل الجلسات"
                           :selected="request('session_id')" />
            <x-form.select name="status" label="الحالة" :options="$statuses" placeholder="كل الحالات"
                           :selected="request('status')" />
            <x-form.select name="source" label="المصدر" :options="$sources" placeholder="كل المصادر"
                           :selected="request('source')" />
            <x-form.select name="provider_id" label="جهة الترشيح" :options="$providers" placeholder="كل الجهات"
                           :selected="request('provider_id')" />
            <div class="actions full">
                <button class="btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i> تطبيق</button>
                <a class="btn btn-ghost" href="{{ route('nominations.index') }}">إعادة ضبط</a>
            </div>
        </form>
    </x-card>

    <div style="margin-block-start:18px">
        <x-card :flush="true">
            @if ($nominations->isEmpty())
                <x-empty text="لا توجد ترشيحات مطابقة لبحثك." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>المتدرب</th>
                            <th>الجلسة</th>
                            <th>المصدر</th>
                            <th>الحالة</th>
                            <th>تاريخ التقديم</th>
                            <th>القرار</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($nominations as $nomination)
                            <tr>
                                <td>
                                    <a href="{{ route('nominations.show', $nomination) }}">
                                        <strong>{{ $nomination->trainee?->full_name ?? '—' }}</strong>
                                    </a>
                                    <div class="muted num" style="font-size:.8rem">{{ $nomination->trainee?->national_id ?? '' }}</div>
                                </td>
                                <td>
                                    <div class="num">{{ $nomination->session?->session_code ?? '—' }}</div>
                                    <div class="muted" style="font-size:.8rem">{{ $nomination->session?->course?->title ?? '—' }}</div>
                                </td>
                                <td>
                                    {{ $nomination->source?->label() ?? '—' }}
                                    @if ($nomination->nominatedByProvider)
                                        <div class="muted" style="font-size:.8rem">{{ $nomination->nominatedByProvider->name }}</div>
                                    @endif
                                </td>
                                <td>
                                    <x-badge :tone="$nomination->status?->tone() ?? 'neutral'">
                                        {{ $nomination->status?->label() ?? '—' }}
                                    </x-badge>
                                </td>
                                <td class="num">{{ $nomination->application_date?->format('Y-m-d') ?? '—' }}</td>
                                <td>
                                    @if ($nomination->decision_at)
                                        <div class="num">{{ $nomination->decision_at->format('Y-m-d') }}</div>
                                        <div class="muted" style="font-size:.8rem">{{ $nomination->decisionBy?->full_name ?? $nomination->decisionBy?->username ?? '—' }}</div>
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                                <td class="actions-cell">
                                    <div class="actions" style="justify-content:flex-end">
                                        <a class="btn btn-ghost btn-sm" href="{{ route('nominations.show', $nomination) }}">تفاصيل</a>
                                        @can('NOMINATE_TRAINEE')
                                            @if (in_array($nomination->status?->value, ['submitted', 'under_review'], true))
                                                <a class="btn btn-outline btn-sm" href="{{ route('nominations.edit', $nomination) }}">تعديل</a>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>

                <div style="padding:0 18px 14px">{{ $nominations->links() }}</div>
            @endif
        </x-card>
    </div>
@endsection
