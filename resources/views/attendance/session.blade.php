@extends('layouts.app')

@section('title', 'حضور '.$session->session_code)

@section('content')
    <x-page-header :title="'حضور '.$session->session_code"
                   :subtitle="($session->course?->title ?? 'دورة محذوفة').' · '.($session->start_date?->format('Y-m-d') ?? '—').' إلى '.($session->end_date?->format('Y-m-d') ?? '—')">
        <a class="btn btn-ghost" href="{{ route('attendance.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع للحضور
        </a>
        @can('SESSIONS_MANAGE')
            <a class="btn btn-outline" href="{{ route('sessions.show', $session) }}">
                <i class="fa-solid fa-calendar-days"></i> تفاصيل الجلسة
            </a>
        @endcan
    </x-page-header>

    @if ($session->sessionDays->isEmpty())
        <x-card>
            <x-empty text="لا توجد أيام لهذه الجلسة. أضف أيامًا من صفحة الجلسة أولًا." />
        </x-card>
    @else
        <div class="tabs">
            @foreach ($session->sessionDays as $sessionDay)
                <a class="tabs__link {{ $day && $day->id === $sessionDay->id ? 'is-active' : '' }}"
                   href="{{ route('attendance.session', ['session' => $session, 'day' => $sessionDay->id]) }}">
                    اليوم {{ $sessionDay->day_no }}
                    <span class="muted" style="font-size:.78rem">{{ $sessionDay->day_date?->format('Y-m-d') }}</span>
                </a>
            @endforeach
        </div>

        @if ($day)
            <div class="grid grid-4" style="margin-block-end:18px">
                <x-stat label="تاريخ اليوم" :value="$day->day_date?->format('Y-m-d') ?? '—'" icon="fa-calendar-day" />
                <x-stat label="التوقيت" :value="substr((string) $day->start_time, 0, 5).' - '.substr((string) $day->end_time, 0, 5)" icon="fa-clock" />
                <x-stat label="المتدربون" :value="$session->enrollments->count()" icon="fa-users" />
                <x-stat label="المدربون" :value="$trainers->count()" icon="fa-chalkboard-user" />
            </div>

            @if ($day->hall)
                <p class="muted" style="margin-block-start:-6px">
                    <i class="fa-solid fa-door-open"></i> القاعة: {{ $day->hall->name }}
                    @if ($day->hall->branch) — {{ $day->hall->branch->name }} @endif
                </p>
            @endif

            <form method="POST" action="{{ route('attendance.record', ['session' => $session, 'day' => $day]) }}">
                @csrf

                <x-card title="حضور المتدربين" :flush="true">
                    @if ($session->enrollments->isEmpty())
                        <x-empty text="لا يوجد متدربون مسجلون في هذه الجلسة." />
                    @else
                        <x-table>
                            <thead>
                                <tr>
                                    <th>المتدرب</th>
                                    <th>الحالة</th>
                                    <th>دقائق التأخير</th>
                                    <th>سبب العذر</th>
                                    <th>ملاحظات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($session->enrollments as $enrollment)
                                    @php
                                        $record = $traineeAttendance->get($enrollment->id);
                                        $key = 'trainees.'.$enrollment->id;
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $enrollment->trainee?->full_name ?? 'متدرب محذوف' }}</strong>
                                            <div class="muted num" style="font-size:.8rem">{{ $enrollment->trainee?->national_id ?? '' }}</div>
                                        </td>
                                        <td style="min-width:150px">
                                            <select name="{{ $key }}[status]" class="control">
                                                <option value="">— لم يُسجَّل —</option>
                                                @foreach ($statuses as $value => $label)
                                                    <option value="{{ $value }}"
                                                        @selected(old($key.'.status', $record?->status?->value) === $value)>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td style="min-width:110px">
                                            <input type="number" min="0" max="1440" class="control"
                                                   name="{{ $key }}[late_minutes]"
                                                   value="{{ old($key.'.late_minutes', $record?->late_minutes) }}"
                                                   placeholder="0">
                                        </td>
                                        <td style="min-width:180px">
                                            <input type="text" class="control" maxlength="1000"
                                                   name="{{ $key }}[excuse_reason]"
                                                   value="{{ old($key.'.excuse_reason', $record?->excuse?->reason) }}"
                                                   placeholder="مطلوب عند الغياب بعذر">
                                        </td>
                                        <td style="min-width:180px">
                                            <input type="text" class="control" maxlength="300"
                                                   name="{{ $key }}[notes]"
                                                   value="{{ old($key.'.notes', $record?->notes) }}"
                                                   placeholder="ملاحظات اختيارية">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </x-table>
                    @endif
                </x-card>

                <div style="margin-block-start:18px">
                    <x-card title="حضور المدربين" :flush="true">
                        @if ($trainers->isEmpty())
                            <x-empty text="لا يوجد مدربون معينون لهذا اليوم أو لهذه الجلسة." />
                        @else
                            <x-table>
                                <thead>
                                    <tr>
                                        <th>المدرب</th>
                                        <th>الحالة</th>
                                        <th>دقائق التأخير</th>
                                        <th>سبب العذر</th>
                                        <th>ملاحظات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($trainers as $trainer)
                                        @php
                                            $record = $trainerAttendance->get($trainer->id);
                                            $key = 'trainers.'.$trainer->id;
                                        @endphp
                                        <tr>
                                            <td>
                                                <strong>{{ $trainer->full_name }}</strong>
                                                <div class="muted" style="font-size:.8rem">
                                                    {{ $trainer->trainer_type?->label() ?? '—' }}
                                                    @if ($trainer->provider) — {{ $trainer->provider->name }} @endif
                                                </div>
                                            </td>
                                            <td style="min-width:150px">
                                                <select name="{{ $key }}[status]" class="control">
                                                    <option value="">— لم يُسجَّل —</option>
                                                    @foreach ($statuses as $value => $label)
                                                        <option value="{{ $value }}"
                                                            @selected(old($key.'.status', $record?->status?->value) === $value)>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td style="min-width:110px">
                                                <input type="number" min="0" max="1440" class="control"
                                                       name="{{ $key }}[late_minutes]"
                                                       value="{{ old($key.'.late_minutes', $record?->late_minutes) }}"
                                                       placeholder="0">
                                            </td>
                                            <td style="min-width:180px">
                                                <input type="text" class="control" maxlength="1000"
                                                       name="{{ $key }}[excuse_reason]"
                                                       value="{{ old($key.'.excuse_reason', $record?->excuse?->reason) }}"
                                                       placeholder="مطلوب عند الغياب بعذر">
                                            </td>
                                            <td style="min-width:180px">
                                                <input type="text" class="control" maxlength="300"
                                                       name="{{ $key }}[notes]"
                                                       value="{{ old($key.'.notes', $record?->notes) }}"
                                                       placeholder="ملاحظات اختيارية">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </x-table>
                        @endif
                    </x-card>
                </div>

                <div class="actions" style="margin-block-start:18px;justify-content:flex-end">
                    <button type="submit" class="btn">
                        <i class="fa-solid fa-floppy-disk"></i> حفظ حضور اليوم
                    </button>
                </div>
            </form>
        @else
            <x-card>
                <x-empty text="تعذّر العثور على اليوم المطلوب ضمن هذه الجلسة." />
            </x-card>
        @endif
    @endif
@endsection
