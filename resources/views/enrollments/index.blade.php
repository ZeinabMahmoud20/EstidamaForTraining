@extends('layouts.app')

@section('title', 'التسجيلات')

@section('content')
    <x-page-header title="التسجيلات" subtitle="تسجيلات المتدربين في الجلسات ومتابعة الحضور والنتائج والشهادات">
        @can('ENROLLMENTS_MANAGE')
            <a class="btn" href="{{ route('enrollments.create') }}">
                <i class="fa-solid fa-plus"></i> تسجيل جديد
            </a>
        @endcan
    </x-page-header>

    <x-card title="بحث وتصفية">
        <form method="GET" action="{{ route('enrollments.index') }}" class="filter-grid">
            <x-form.input name="q" label="بحث" :value="request('q')" placeholder="اسم المتدرب أو الرقم القومي..." />
            <x-form.select name="session_id" label="الجلسة" :options="$sessions" placeholder="كل الجلسات"
                           :selected="request('session_id')" />
            <x-form.select name="status" label="الحالة" :options="$statuses" placeholder="كل الحالات"
                           :selected="request('status')" />
            <div class="field">
                <label for="is_passed">النتيجة</label>
                <select id="is_passed" name="is_passed" class="control">
                    <option value="">الكل</option>
                    <option value="1" @selected(request('is_passed') === '1')>ناجح</option>
                    <option value="0" @selected(request('is_passed') === '0')>راسب</option>
                </select>
            </div>
            <div class="field">
                <label for="certificate">أهلية الشهادة</label>
                <select id="certificate" name="certificate" class="control">
                    <option value="">الكل</option>
                    <option value="1" @selected(request('certificate') === '1')>مؤهل</option>
                    <option value="0" @selected(request('certificate') === '0')>غير مؤهل</option>
                </select>
            </div>
            <div class="actions full">
                <button class="btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i> تطبيق</button>
                <a class="btn btn-ghost" href="{{ route('enrollments.index') }}">إعادة ضبط</a>
            </div>
        </form>
    </x-card>

    <div style="margin-block-start:18px">
        <x-card :flush="true">
            @if ($enrollments->isEmpty())
                <x-empty text="لا توجد تسجيلات مطابقة لبحثك." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>المتدرب</th>
                            <th>الجلسة</th>
                            <th>الحالة</th>
                            <th>الحضور</th>
                            <th>الدرجة النهائية</th>
                            <th>النتيجة</th>
                            <th>الشهادة</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($enrollments as $enrollment)
                            <tr>
                                <td>
                                    <a href="{{ route('enrollments.show', $enrollment) }}">
                                        <strong>{{ $enrollment->trainee?->full_name ?? '—' }}</strong>
                                    </a>
                                    <div class="muted num" style="font-size:.8rem">{{ $enrollment->trainee?->national_id ?? '' }}</div>
                                </td>
                                <td>
                                    <div class="num">{{ $enrollment->session?->session_code ?? '—' }}</div>
                                    <div class="muted" style="font-size:.8rem">{{ $enrollment->session?->course?->title ?? '—' }}</div>
                                </td>
                                <td><x-badge :tone="$enrollment->status?->tone() ?? 'neutral'">{{ $enrollment->status?->label() ?? '—' }}</x-badge></td>
                                <td class="num">{{ $enrollment->attendance_pct !== null ? $enrollment->attendance_pct.'%' : '—' }}</td>
                                <td class="num">{{ $enrollment->final_score_pct !== null ? $enrollment->final_score_pct.'%' : '—' }}</td>
                                <td>
                                    @if ($enrollment->is_passed === null)
                                        <span class="muted">—</span>
                                    @elseif ($enrollment->is_passed)
                                        <x-badge tone="ok">ناجح</x-badge>
                                    @else
                                        <x-badge tone="danger">راسب</x-badge>
                                    @endif
                                </td>
                                <td>
                                    @if ($enrollment->is_certificate_eligible === null)
                                        <span class="muted">—</span>
                                    @elseif ($enrollment->is_certificate_eligible)
                                        <x-badge tone="ok">مؤهل</x-badge>
                                    @else
                                        <x-badge>غير مؤهل</x-badge>
                                    @endif
                                </td>
                                <td class="actions-cell">
                                    <div class="actions" style="justify-content:flex-end">
                                        <a class="btn btn-ghost btn-sm" href="{{ route('enrollments.show', $enrollment) }}">تفاصيل</a>
                                        @can('ENROLLMENTS_MANAGE')
                                            <a class="btn btn-outline btn-sm" href="{{ route('enrollments.edit', $enrollment) }}">تعديل</a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>

                <div style="padding:0 18px 14px">{{ $enrollments->links() }}</div>
            @endif
        </x-card>
    </div>
@endsection
