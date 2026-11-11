@extends('layouts.app')

@section('title', 'ترشيح: '.($nomination->trainee?->full_name ?? ''))

@section('content')
    <x-page-header :title="'ترشيح: '.($nomination->trainee?->full_name ?? '')"
                   :subtitle="($nomination->session?->session_code ?? 'بدون جلسة').' · '.($nomination->session?->course?->title ?? 'بدون دورة')">
        <a class="btn btn-ghost" href="{{ route('nominations.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع للترشيحات
        </a>
        @can('NOMINATE_TRAINEE')
            @if (in_array($nomination->status?->value, ['submitted', 'under_review'], true))
                <a class="btn" href="{{ route('nominations.edit', $nomination) }}">
                    <i class="fa-solid fa-pen"></i> تعديل
                </a>
            @endif
        @endcan
        @if (auth()->user()?->can('REVIEW_NOMINATION') || auth()->user()?->can('APPROVE_NOMINATION'))
            <form method="POST" action="{{ route('nominations.destroy', $nomination) }}"
                  data-confirm="هل تريد حذف هذا الترشيح؟">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit"><i class="fa-solid fa-trash"></i> حذف</button>
            </form>
        @endif
    </x-page-header>

    <div class="grid grid-4" style="margin-block-end:18px">
        <x-stat label="الحالة" :value="$nomination->status?->label() ?? '—'" icon="fa-circle-info" />
        <x-stat label="المصدر" :value="$nomination->source?->label() ?? '—'" icon="fa-arrow-right-to-bracket" />
        <x-stat label="تاريخ التقديم" :value="$nomination->application_date?->format('Y-m-d') ?? '—'" icon="fa-calendar-plus" />
        <x-stat label="التسجيل" :value="$nomination->enrollment ? 'مسجل' : 'لم يسجل'" icon="fa-clipboard-list"
                :tone="$nomination->enrollment ? 'ok' : 'warn'" />
    </div>

    <div class="grid grid-2">
        <x-card title="بيانات الترشيح">
            <dl class="details">
                <div><dt>المتدرب</dt><dd><a href="{{ route('trainees.show', $nomination->trainee_id) }}">{{ $nomination->trainee?->full_name ?? '—' }}</a></dd></div>
                <div><dt>الرقم القومي</dt><dd class="num">{{ $nomination->trainee?->national_id ?? '—' }}</dd></div>
                <div><dt>الجلسة</dt><dd><a href="{{ route('sessions.show', $nomination->session_id) }}">{{ $nomination->session?->session_code ?? '—' }}</a></dd></div>
                <div><dt>الدورة</dt><dd>{{ $nomination->session?->course?->title ?? '—' }}</dd></div>
                <div><dt>جهة الترشيح</dt><dd>{{ $nomination->nominatedByProvider?->name ?? '—' }}</dd></div>
                <div><dt>ملاحظات</dt><dd class="muted">{{ data_get($nomination->application_data, 'notes') ?: '—' }}</dd></div>
                <div><dt>تاريخ القرار</dt><dd class="num">{{ $nomination->decision_at?->format('Y-m-d H:i') ?? '—' }}</dd></div>
                <div><dt>صاحب القرار</dt><dd>{{ $nomination->decisionBy?->full_name ?? $nomination->decisionBy?->username ?? '—' }}</dd></div>
                <div><dt>سبب القرار</dt><dd class="muted">{{ $nomination->decision_reason ?: '—' }}</dd></div>
                @if ($nomination->waitlist_order)
                    <div><dt>ترتيب الانتظار</dt><dd class="num">{{ $nomination->waitlist_order }}</dd></div>
                @endif
            </dl>
        </x-card>

        <x-card title="معايير الالتحاق">
            @if (empty($assessment['items']))
                <x-empty text="لا توجد معايير التحاق مسجلة لهذه الدورة." />
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>المعيار</th>
                                <th>المطلوب</th>
                                <th>الحالي</th>
                                <th>النتيجة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($assessment['items'] as $item)
                                <tr>
                                    <td>
                                        {{ $item['name'] }}
                                        @if ($item['mandatory'])
                                            <x-badge tone="warn">إلزامي</x-badge>
                                        @endif
                                    </td>
                                    <td class="muted">{{ $item['required'] ?: '—' }}</td>
                                    <td class="muted">{{ $item['actual'] ?: '—' }}</td>
                                    <td>
                                        @if ($item['passed'])
                                            <x-badge tone="ok">مستوفى</x-badge>
                                        @else
                                            <x-badge tone="danger">غير مستوفى</x-badge>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div style="margin-block-start:10px">
                    @if ($assessment['blocking'])
                        <div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation"></i> يوجد معيار إلزامي غير مستوفى.</div>
                    @else
                        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> كل المعايير الإلزامية مستوفاة.</div>
                    @endif
                </div>
            @endif
        </x-card>
    </div>

    @if (! empty($nextStatuses))
        <div style="margin-block-start:18px">
            <x-card title="تحديث حالة الترشيح">
                <form method="POST" action="{{ route('nominations.transition', $nomination) }}">
                    @csrf
                    @method('PATCH')

                    <div class="form-grid">
                        <x-form.select name="status" label="الحالة الجديدة" :options="$nextStatuses" required />
                        <x-form.input name="reason" label="سبب القرار / ملاحظة" />
                        @if ($canOverride)
                            <x-form.checkbox name="override" label="تجاوز معايير الالتحاق عند الاعتماد" />
                        @endif
                    </div>

                    <div class="actions" style="margin-block-start:12px;justify-content:flex-end">
                        <button type="submit" class="btn"><i class="fa-solid fa-arrow-right-arrow-left"></i> تطبيق الحالة</button>
                    </div>
                </form>
            </x-card>
        </div>
    @endif

    @if ($nomination->enrollment)
        <div style="margin-block-start:18px">
            <x-card title="التسجيل الناتج">
                <dl class="details">
                    <div><dt>حالة التسجيل</dt><dd><x-badge :tone="$nomination->enrollment->status?->tone() ?? 'neutral'">{{ $nomination->enrollment->status?->label() ?? '—' }}</x-badge></dd></div>
                    <div><dt>نسبة الحضور</dt><dd class="num">{{ $nomination->enrollment->attendance_pct !== null ? $nomination->enrollment->attendance_pct.'%' : '—' }}</dd></div>
                    <div><dt>الدرجة النهائية</dt><dd class="num">{{ $nomination->enrollment->final_score_pct !== null ? $nomination->enrollment->final_score_pct.'%' : '—' }}</dd></div>
                    <div><dt>النتيجة</dt><dd>{{ $nomination->enrollment->is_passed === null ? 'لم تحدد' : ($nomination->enrollment->is_passed ? 'ناجح' : 'راسب') }}</dd></div>
                </dl>
                <div class="actions" style="margin-block-start:12px">
                    <a class="btn btn-outline" href="{{ route('enrollments.show', $nomination->enrollment) }}">فتح التسجيل</a>
                </div>
            </x-card>
        </div>
    @endif

    <div style="margin-block-start:18px">
        <x-card title="سجل الحالات" :flush="true">
            @if ($nomination->nominationStatusHistory->isEmpty())
                <x-empty text="لا توجد تغييرات مسجلة بعد." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>من</th>
                            <th>إلى</th>
                            <th>بواسطة</th>
                            <th>التاريخ</th>
                            <th>ملاحظة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($nomination->nominationStatusHistory as $history)
                            <tr>
                                <td>{{ $history->from_status?->label() ?? '—' }}</td>
                                <td><x-badge :tone="$history->to_status?->tone() ?? 'neutral'">{{ $history->to_status?->label() ?? '—' }}</x-badge></td>
                                <td>{{ $history->changedBy?->full_name ?? $history->changedBy?->username ?? '—' }}</td>
                                <td class="num">{{ $history->changed_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td class="muted">{{ $history->note ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>
    </div>
@endsection
