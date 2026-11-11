@extends('layouts.app')

@section('title', 'تسجيل: '.($enrollment->trainee?->full_name ?? ''))

@section('content')
    <x-page-header :title="'تسجيل: '.($enrollment->trainee?->full_name ?? '')"
                   :subtitle="($enrollment->session?->session_code ?? '').' · '.($enrollment->session?->course?->title ?? '')">
        <a class="btn btn-ghost" href="{{ route('enrollments.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع للتسجيلات
        </a>
        @can('ENROLLMENTS_MANAGE')
            <a class="btn" href="{{ route('enrollments.edit', $enrollment) }}">
                <i class="fa-solid fa-pen"></i> تعديل
            </a>
            <form method="POST" action="{{ route('enrollments.recalculate', $enrollment) }}">
                @csrf
                <button class="btn btn-outline" type="submit"><i class="fa-solid fa-calculator"></i> احتساب الحضور</button>
            </form>
            <form method="POST" action="{{ route('enrollments.destroy', $enrollment) }}"
                  data-confirm="هل تريد حذف هذا التسجيل؟">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit"><i class="fa-solid fa-trash"></i> حذف</button>
            </form>
        @endcan
    </x-page-header>

    <div class="grid grid-4" style="margin-block-end:18px">
        <x-stat label="الحالة" :value="$enrollment->status?->label() ?? '—'" icon="fa-circle-info" />
        <x-stat label="نسبة الحضور" :value="$enrollment->attendance_pct !== null ? $enrollment->attendance_pct.'%' : '—'" icon="fa-user-clock" />
        <x-stat label="الدرجة النهائية" :value="$enrollment->final_score_pct !== null ? $enrollment->final_score_pct.'%' : '—'" icon="fa-chart-line" />
        <x-stat label="النتيجة" :value="$enrollment->is_passed === null ? 'لم تحدد' : ($enrollment->is_passed ? 'ناجح' : 'راسب')"
                icon="fa-award" :tone="$enrollment->is_passed === null ? '' : ($enrollment->is_passed ? 'ok' : 'danger')" />
    </div>

    <div class="grid grid-2">
        <x-card title="بيانات التسجيل">
            <dl class="details">
                <div><dt>المتدرب</dt><dd><a href="{{ route('trainees.show', $enrollment->trainee_id) }}">{{ $enrollment->trainee?->full_name ?? '—' }}</a></dd></div>
                <div><dt>الرقم القومي</dt><dd class="num">{{ $enrollment->trainee?->national_id ?? '—' }}</dd></div>
                <div><dt>الجلسة</dt><dd class="num">{{ $enrollment->session?->session_code ?? '—' }}</dd></div>
                <div><dt>الدورة</dt><dd>{{ $enrollment->session?->course?->title ?? '—' }}</dd></div>
                <div><dt>الترشيح</dt><dd><a href="{{ route('nominations.show', $enrollment->nomination_id) }}">عرض الترشيح</a></dd></div>
                <div><dt>تاريخ التسجيل</dt><dd class="num">{{ $enrollment->created_at?->format('Y-m-d') ?? '—' }}</dd></div>
                <div><dt>تاريخ الإتمام</dt><dd class="num">{{ $enrollment->completed_at?->format('Y-m-d') ?? '—' }}</dd></div>
                <div><dt>أهلية الشهادة</dt><dd>{{ $enrollment->is_certificate_eligible === null ? 'لم تحدد' : ($enrollment->is_certificate_eligible ? 'مؤهل' : 'غير مؤهل') }}</dd></div>
            </dl>
        </x-card>

        <x-card title="الشهادة">
            @if ($enrollment->certificate)
                <dl class="details">
                    <div><dt>رقم الشهادة</dt><dd class="num">{{ $enrollment->certificate->certificate_no }}</dd></div>
                    <div><dt>النوع</dt><dd>{{ $enrollment->certificate->certificate_type?->label() ?? '—' }}</dd></div>
                    <div><dt>الحالة</dt><dd><x-badge :tone="$enrollment->certificate->status?->tone() ?? 'neutral'">{{ $enrollment->certificate->status?->label() ?? '—' }}</x-badge></dd></div>
                    <div><dt>تاريخ الإصدار</dt><dd class="num">{{ $enrollment->certificate->issue_date?->format('Y-m-d') ?? '—' }}</dd></div>
                </dl>
            @else
                <x-empty text="لم تصدر شهادة لهذا التسجيل بعد." />
                @if ($enrollment->is_certificate_eligible)
                    <div class="alert alert-info" style="margin-block-start:12px">
                        المتدرب مؤهل للشهادة. يمكن إصدارها من شاشة الشهادات.
                    </div>
                @endif
            @endif
        </x-card>
    </div>

    <div style="margin-block-start:18px">
        <x-card title="سجلات الحضور" :flush="true">
            @if ($enrollment->traineeAttendance->isEmpty())
                <x-empty text="لا توجد سجلات حضور لهذا التسجيل بعد." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>اليوم</th>
                            <th>التاريخ</th>
                            <th>الحالة</th>
                            <th>دخول</th>
                            <th>خروج</th>
                            <th>تأخير</th>
                            <th>ملاحظات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($enrollment->traineeAttendance as $attendance)
                            <tr>
                                <td class="num">{{ $attendance->sessionDay?->day_no ?? '—' }}</td>
                                <td class="num">{{ $attendance->sessionDay?->day_date?->format('Y-m-d') ?? '—' }}</td>
                                <td>
                                    @if ($attendance->status)
                                        <x-badge :tone="$attendance->status->tone()">{{ $attendance->status->label() }}</x-badge>
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                                <td class="num">{{ $attendance->check_in_at?->format('H:i') ?? '—' }}</td>
                                <td class="num">{{ $attendance->check_out_at?->format('H:i') ?? '—' }}</td>
                                <td class="num">{{ $attendance->late_minutes !== null ? $attendance->late_minutes.' د' : '—' }}</td>
                                <td class="muted">{{ $attendance->notes ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>
    </div>

    <div style="margin-block-start:18px">
        <x-card title="نتائج الاختبارات" :flush="true">
            @if ($enrollment->examResults->isEmpty())
                <x-empty text="لا توجد نتائج اختبارات لهذا التسجيل بعد." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>الاختبار</th>
                            <th>النوع</th>
                            <th>الدرجة</th>
                            <th>النسبة</th>
                            <th>النتيجة</th>
                            <th>الحالة</th>
                            <th>تاريخ الأداء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($enrollment->examResults as $result)
                            <tr>
                                <td>{{ $result->exam?->title ?: 'اختبار #'.$result->exam_id }}</td>
                                <td>{{ $result->exam?->exam_type?->label() ?? '—' }}</td>
                                <td class="num">{{ $result->score !== null ? $result->score : '—' }}</td>
                                <td class="num">{{ $result->percentage !== null ? $result->percentage.'%' : '—' }}</td>
                                <td>
                                    @if ($result->is_passed === null)
                                        <span class="muted">—</span>
                                    @elseif ($result->is_passed)
                                        <x-badge tone="ok">ناجح</x-badge>
                                    @else
                                        <x-badge tone="danger">راسب</x-badge>
                                    @endif
                                </td>
                                <td>
                                    @if ($result->status)
                                        <x-badge :tone="$result->status->tone()">{{ $result->status->label() }}</x-badge>
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                                <td class="num">{{ $result->taken_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>
    </div>

    <div style="margin-block-start:18px">
        <x-card title="التقييمات المرتبطة" :flush="true">
            @if ($enrollment->evaluationSubmissions->isEmpty())
                <x-empty text="لا توجد تقييمات مرتبطة بهذا التسجيل بعد." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>النموذج</th>
                            <th>الجهة المقيمة</th>
                            <th>الهدف</th>
                            <th>الدرجة</th>
                            <th>تاريخ التقديم</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($enrollment->evaluationSubmissions as $submission)
                            <tr>
                                <td>{{ $submission->form?->title ?? '—' }}</td>
                                <td>{{ $submission->evaluator_type?->label() ?? '—' }}</td>
                                <td>{{ $submission->target_type?->label() ?? '—' }}</td>
                                <td class="num">{{ $submission->total_score !== null ? $submission->total_score : '—' }}</td>
                                <td class="num">{{ $submission->submitted_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>
    </div>
@endsection
