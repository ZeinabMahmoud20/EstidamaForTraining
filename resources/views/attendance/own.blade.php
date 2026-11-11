@extends('layouts.app')

@section('title', 'حضوري')

@section('content')
    <x-page-header title="حضوري" subtitle="سجل حضورك في الجلسات التدريبية">
        <a class="btn btn-ghost" href="{{ route('dashboard') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع للوحة التحكم
        </a>
    </x-page-header>

    @if ($traineeEnrollments->isEmpty() && $trainerAttendance->isEmpty())
        <x-card>
            <x-empty text="لا يوجد سجل حضور مرتبط بحسابك حتى الآن." />
        </x-card>
    @endif

    @if ($traineeEnrollments->isNotEmpty())
        <x-card title="حضور المتدرب" :flush="true">
            <x-table>
                <thead>
                    <tr>
                        <th>الجلسة</th>
                        <th>الفترة</th>
                        <th>نسبة الحضور</th>
                        <th>سجل الأيام</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($traineeEnrollments as $enrollment)
                        <tr>
                            <td>
                                <strong class="num">{{ $enrollment->session?->session_code ?? '—' }}</strong>
                                <div class="muted" style="font-size:.8rem">{{ $enrollment->session?->course?->title ?? '—' }}</div>
                            </td>
                            <td class="num">
                                {{ $enrollment->session?->start_date?->format('Y-m-d') ?? '—' }}
                                <span class="muted">—</span>
                                {{ $enrollment->session?->end_date?->format('Y-m-d') ?? '—' }}
                            </td>
                            <td>
                                @if ($enrollment->attendance_pct !== null)
                                    <x-badge :tone="(float) $enrollment->attendance_pct >= 75 ? 'ok' : 'warn'">
                                        {{ $enrollment->attendance_pct }}%
                                    </x-badge>
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                            <td>
                                @php($records = $enrollment->traineeAttendance->sortBy(fn ($row) => $row->sessionDay?->day_no))
                                @if ($records->isEmpty())
                                    <span class="muted">لم يُسجَّل حضور بعد.</span>
                                @else
                                    <div style="display:flex;flex-wrap:wrap;gap:6px">
                                        @foreach ($records as $record)
                                            <x-badge :tone="$record->status?->tone() ?? 'neutral'">
                                                اليوم {{ $record->sessionDay?->day_no ?? '—' }}:
                                                {{ $record->status?->label() ?? '—' }}
                                            </x-badge>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        </x-card>
    @endif

    @if ($trainerAttendance->isNotEmpty())
        <div style="margin-block-start:18px">
            <x-card title="حضور المدرب" :flush="true">
                <x-table>
                    <thead>
                        <tr>
                            <th>الجلسة</th>
                            <th>اليوم</th>
                            <th>التاريخ</th>
                            <th>الحالة</th>
                            <th>دقائق التأخير</th>
                            <th>ملاحظات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($trainerAttendance as $record)
                            <tr>
                                <td>
                                    <strong class="num">{{ $record->sessionDay?->session?->session_code ?? '—' }}</strong>
                                    <div class="muted" style="font-size:.8rem">
                                        {{ $record->sessionDay?->session?->course?->title ?? '—' }}
                                    </div>
                                </td>
                                <td class="num">{{ $record->sessionDay?->day_no ?? '—' }}</td>
                                <td class="num">{{ $record->sessionDay?->day_date?->format('Y-m-d') ?? '—' }}</td>
                                <td><x-badge :tone="$record->status?->tone() ?? 'neutral'">{{ $record->status?->label() ?? '—' }}</x-badge></td>
                                <td class="num">{{ $record->late_minutes ?? '—' }}</td>
                                <td class="muted">{{ $record->notes ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            </x-card>
        </div>
    @endif
@endsection
