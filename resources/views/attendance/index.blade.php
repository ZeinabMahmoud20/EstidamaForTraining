@extends('layouts.app')

@section('title', 'الحضور')

@section('content')
    <x-page-header title="الحضور" subtitle="تسجيل حضور المتدربين والمدربين حسب أيام الجلسات ومتابعة الأعذار">
        @can('ATTENDANCE_EXCUSE_REVIEW')
            <a class="btn btn-ghost" href="{{ route('attendance.excuses.index') }}">
                <i class="fa-solid fa-file-medical"></i> مراجعة الأعذار
            </a>
        @endcan
        @if (auth()->user()?->trainee_id || auth()->user()?->trainer_id)
            <a class="btn btn-outline" href="{{ route('attendance.own') }}">
                <i class="fa-solid fa-user-clock"></i> حضوري
            </a>
        @endif
    </x-page-header>

    <x-card title="بحث وتصفية">
        <form method="GET" action="{{ route('attendance.index') }}" class="filter-grid">
            <x-form.input name="q" label="بحث" :value="request('q')" placeholder="كود الجلسة أو اسم الدورة..." />
            <x-form.select name="status" label="حالة الجلسة" :options="$statuses" placeholder="كل الحالات"
                           :selected="request('status')" />
            <div class="actions full">
                <button class="btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i> تطبيق</button>
                <a class="btn btn-ghost" href="{{ route('attendance.index') }}">إعادة ضبط</a>
            </div>
        </form>
    </x-card>

    <div style="margin-block-start:18px">
        <x-card :flush="true">
            @if ($sessions->isEmpty())
                <x-empty text="لا توجد جلسات مطابقة لبحثك." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>الجلسة</th>
                            <th>الفترة</th>
                            <th>الأيام</th>
                            <th>التسجيلات</th>
                            <th>الحالة</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sessions as $session)
                            <tr>
                                <td>
                                    <a href="{{ route('attendance.session', $session) }}">
                                        <strong>{{ $session->session_code }}</strong>
                                    </a>
                                    <div class="muted" style="font-size:.8rem">{{ $session->course?->title ?? 'دورة محذوفة' }}</div>
                                </td>
                                <td class="num">
                                    {{ $session->start_date?->format('Y-m-d') ?? '—' }}
                                    <span class="muted">—</span>
                                    {{ $session->end_date?->format('Y-m-d') ?? '—' }}
                                </td>
                                <td>
                                    <x-badge :tone="$session->session_days_count > 0 ? 'brand' : 'neutral'">
                                        {{ $session->session_days_count }}
                                    </x-badge>
                                </td>
                                <td>
                                    <x-badge :tone="$session->enrollments_count > 0 ? 'ok' : 'neutral'">
                                        {{ $session->enrollments_count }}
                                    </x-badge>
                                </td>
                                <td><x-badge :tone="$session->status->tone()">{{ $session->status->label() }}</x-badge></td>
                                <td class="actions-cell">
                                    <div class="actions" style="justify-content:flex-end">
                                        <a class="btn btn-ghost btn-sm" href="{{ route('attendance.session', $session) }}">
                                            تسجيل الحضور
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>

                <div style="padding:0 18px 14px">{{ $sessions->links() }}</div>
            @endif
        </x-card>
    </div>
@endsection
