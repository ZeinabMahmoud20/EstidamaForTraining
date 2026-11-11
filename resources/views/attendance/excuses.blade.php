@extends('layouts.app')

@section('title', 'مراجعة الأعذار')

@section('content')
    <x-page-header title="مراجعة الأعذار" subtitle="قبول أو رفض أعذار الغياب المسجّلة للمتدربين والمدربين">
        <a class="btn btn-ghost" href="{{ route('attendance.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع للحضور
        </a>
    </x-page-header>

    <x-card title="بحث وتصفية">
        <form method="GET" action="{{ route('attendance.excuses.index') }}" class="filter-grid">
            <x-form.input name="q" label="بحث" :value="request('q')" placeholder="سبب العذر..." />
            <x-form.select name="status" label="الحالة" :options="$statuses" placeholder="كل الحالات"
                           :selected="request('status')" />
            <div class="actions full">
                <button class="btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i> تطبيق</button>
                <a class="btn btn-ghost" href="{{ route('attendance.excuses.index') }}">إعادة ضبط</a>
            </div>
        </form>
    </x-card>

    <div style="margin-block-start:18px">
        <x-card :flush="true">
            @if ($excuses->isEmpty())
                <x-empty text="لا توجد أعذار مطابقة لبحثك." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>السبب</th>
                            <th>صاحب العذر</th>
                            <th>الجلسة / اليوم</th>
                            <th>تاريخ التقديم</th>
                            <th>الحالة</th>
                            <th>المراجعة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($excuses as $excuse)
                            @php
                                $traineeRows = $excuse->traineeAttendance;
                                $trainerRows = $excuse->trainerAttendance;
                                $firstTrainee = $traineeRows->first();
                                $firstTrainer = $trainerRows->first();
                                $targetName = $firstTrainee?->enrollment?->trainee?->full_name
                                    ?? $firstTrainer?->trainer?->full_name
                                    ?? '—';
                                $targetType = $firstTrainee ? 'متدرب' : ($firstTrainer ? 'مدرب' : '—');
                                $dayRef = $firstTrainee?->sessionDay ?? $firstTrainer?->sessionDay;
                            @endphp
                            <tr>
                                <td style="min-width:220px">
                                    {{ $excuse->reason }}
                                    @if ($excuse->review_notes)
                                        <div class="muted" style="font-size:.8rem">ملاحظات: {{ $excuse->review_notes }}</div>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $targetName }}</strong>
                                    <div class="muted" style="font-size:.8rem">{{ $targetType }}</div>
                                </td>
                                <td>
                                    @if ($dayRef?->session)
                                        <div class="num">{{ $dayRef->session->session_code }}</div>
                                        <div class="muted" style="font-size:.8rem">
                                            اليوم {{ $dayRef->day_no }} — {{ $dayRef->day_date?->format('Y-m-d') }}
                                        </div>
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                                <td class="num">{{ $excuse->submitted_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td>
                                    <x-badge :tone="$excuse->status->tone()">{{ $excuse->status->label() }}</x-badge>
                                    @if ($excuse->reviewedBy)
                                        <div class="muted" style="font-size:.78rem">{{ $excuse->reviewedBy->full_name }}</div>
                                    @endif
                                </td>
                                <td style="min-width:260px">
                                    <form method="POST" action="{{ route('attendance.excuses.review', $excuse) }}">
                                        @csrf
                                        @method('PATCH')
                                        <div class="field">
                                            <select name="status" class="control" required>
                                                <option value="">اختر القرار...</option>
                                                @foreach ($statuses as $value => $label)
                                                    @continue($value === 'pending')
                                                    <option value="{{ $value }}" @selected(old('status') === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="field" style="margin-block-start:8px">
                                            <input type="text" name="review_notes" class="control" maxlength="300"
                                                   value="{{ old('review_notes') }}" placeholder="ملاحظات المراجعة">
                                        </div>
                                        <button type="submit" class="btn btn-sm" style="margin-block-start:8px">
                                            <i class="fa-solid fa-check"></i> حفظ القرار
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>

                <div style="padding:0 18px 14px">{{ $excuses->links() }}</div>
            @endif
        </x-card>
    </div>
@endsection
