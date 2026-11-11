@extends('layouts.app')

@section('title', $exam->title ?: 'اختبار #'.$exam->id)

@section('content')
    <x-page-header :title="$exam->title ?: 'اختبار #'.$exam->id"
                   :subtitle="($exam->exam_type?->label() ?? '—').' · '.($exam->session?->session_code ?? '—').' · '.($exam->session?->course?->title ?? '—')">
        <a class="btn btn-ghost" href="{{ route('exams.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع للاختبارات
        </a>
        @can('EXAMS_MANAGE')
            <a class="btn btn-outline" href="{{ route('exams.edit', $exam) }}">
                <i class="fa-solid fa-pen"></i> تعديل
            </a>
            <form method="POST" action="{{ route('exams.destroy', $exam) }}"
                  data-confirm="هل تريد حذف الاختبار؟">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit"><i class="fa-solid fa-trash"></i> حذف</button>
            </form>
        @endcan
    </x-page-header>

    <div class="grid grid-4" style="margin-block-end:18px">
        <x-stat label="الدرجة الكلية" :value="$exam->total_score ?? '—'" icon="fa-award" />
        <x-stat label="درجة النجاح" :value="$exam->pass_score ?? '50%'" icon="fa-check-double" />
        <x-stat label="الأسئلة" :value="$exam->questions->count()" icon="fa-circle-question" />
        <x-stat label="النتائج" :value="$exam->examResults->count()" icon="fa-clipboard-check" tone="ok" />
    </div>

    <div class="grid grid-2">
        <x-card title="بيانات الاختبار">
            <dl class="details">
                <div><dt>الجلسة</dt><dd class="num">{{ $exam->session?->session_code ?? '—' }}</dd></div>
                <div><dt>الدورة</dt><dd>{{ $exam->session?->course?->title ?? '—' }}</dd></div>
                <div><dt>النوع</dt><dd>{{ $exam->exam_type?->label() ?? '—' }}</dd></div>
                <div><dt>الموعد</dt><dd class="num">{{ $exam->scheduled_start?->format('Y-m-d H:i') ?? '—' }}</dd></div>
                <div><dt>المدة</dt><dd class="num">{{ $exam->duration_minutes !== null ? $exam->duration_minutes.' دقيقة' : '—' }}</dd></div>
                <div><dt>الوزن</dt><dd class="num">{{ $exam->weight_pct !== null ? $exam->weight_pct.'%' : '—' }}</dd></div>
                <div><dt>أنشئ بواسطة</dt><dd>{{ $exam->createdBy?->full_name ?? '—' }}</dd></div>
            </dl>
        </x-card>

        <x-card title="الأسئلة المختارة" :flush="true">
            @if ($exam->questions->isEmpty())
                <x-empty text="لم تُحدد أسئلة لهذا الاختبار." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>السؤال</th>
                            <th>النوع</th>
                            <th>الدرجة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($exam->questions as $question)
                            <tr>
                                <td class="num">{{ $loop->iteration }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($question->question_text, 120) }}</td>
                                <td>{{ $question->question_type?->label() ?? '—' }}</td>
                                <td class="num">{{ $question->pivot->points ?? $question->default_points }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>
    </div>

    <div style="margin-block-start:18px">
        @can('EXAMS_GRADE')
            <form method="POST" action="{{ route('exams.grade', $exam) }}">
                @csrf
                <x-card title="تصحيح النتائج" :flush="true">
                    @if ($enrollments->isEmpty())
                        <x-empty text="لا يوجد متدربون مسجلون في جلسة هذا الاختبار." />
                    @else
                        <x-table>
                            <thead>
                                <tr>
                                    <th>المتدرب</th>
                                    <th>الدرجة</th>
                                    <th>النسبة</th>
                                    <th>النتيجة</th>
                                    <th>تاريخ الأداء</th>
                                    <th>صحح بواسطة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($enrollments as $enrollment)
                                    @php($result = $results->get($enrollment->id))
                                    <tr>
                                        <td>
                                            <strong>{{ $enrollment->trainee?->full_name ?? '—' }}</strong>
                                            <div class="muted num" style="font-size:.8rem">{{ $enrollment->trainee?->national_id ?? '' }}</div>
                                        </td>
                                        <td style="width:130px">
                                            <input type="number" step="0.01" min="0" max="{{ $exam->total_score }}" class="control"
                                                   name="results[{{ $enrollment->id }}][score]"
                                                   value="{{ old('results.'.$enrollment->id.'.score', $result?->score) }}"
                                                   placeholder="0 - {{ $exam->total_score }}">
                                        </td>
                                        <td class="num">{{ $result?->percentage !== null ? $result->percentage.'%' : '—' }}</td>
                                        <td>
                                            @if ($result?->is_passed === null)
                                                <span class="muted">—</span>
                                            @elseif ($result->is_passed)
                                                <x-badge tone="ok">ناجح</x-badge>
                                            @else
                                                <x-badge tone="danger">راسب</x-badge>
                                            @endif
                                        </td>
                                        <td class="num">{{ $result?->taken_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                        <td class="muted">{{ $result?->gradedBy?->full_name ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </x-table>

                        <div class="actions" style="padding:14px 18px;justify-content:flex-end">
                            <button type="submit" class="btn">
                                <i class="fa-solid fa-floppy-disk"></i> حفظ النتائج
                            </button>
                        </div>
                    @endif
                </x-card>
            </form>
        @else
            <x-card title="النتائج" :flush="true">
                @if ($exam->examResults->isEmpty())
                    <x-empty text="لا توجد نتائج مسجّلة بعد. التصحيح يتطلب صلاحية تصحيح الاختبارات." />
                @else
                    <x-table>
                        <thead>
                            <tr>
                                <th>المتدرب</th>
                                <th>الدرجة</th>
                                <th>النسبة</th>
                                <th>النتيجة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($exam->examResults as $result)
                                <tr>
                                    <td>{{ $result->enrollment?->trainee?->full_name ?? '—' }}</td>
                                    <td class="num">{{ $result->score }}</td>
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
                                </tr>
                            @endforeach
                        </tbody>
                    </x-table>
                @endif
            </x-card>
        @endcan
    </div>
@endsection
