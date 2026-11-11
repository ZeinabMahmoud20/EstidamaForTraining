@extends('layouts.app')

@section('title', 'الاختبارات')

@section('content')
    <x-page-header title="الاختبارات" subtitle="إدارة اختبارات الجلسات وربطها ببنوك الأسئلة وتسجيل النتائج">
        @can('QUESTION_BANK_MANAGE')
            <a class="btn btn-ghost" href="{{ route('question-banks.index') }}">
                <i class="fa-solid fa-database"></i> بنوك الأسئلة
            </a>
        @endcan
        @can('EXAMS_MANAGE')
            <a class="btn" href="{{ route('exams.create') }}">
                <i class="fa-solid fa-plus"></i> اختبار جديد
            </a>
        @endcan
    </x-page-header>

    <x-card title="بحث وتصفية">
        <form method="GET" action="{{ route('exams.index') }}" class="filter-grid">
            <x-form.input name="q" label="بحث" :value="request('q')" placeholder="عنوان الاختبار أو كود الجلسة..." />
            <x-form.select name="session_id" label="الجلسة" :options="$sessions" placeholder="كل الجلسات"
                           :selected="request('session_id')" />
            <x-form.select name="exam_type" label="النوع" :options="$examTypes" placeholder="كل الأنواع"
                           :selected="request('exam_type')" />
            <div class="actions full">
                <button class="btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i> تطبيق</button>
                <a class="btn btn-ghost" href="{{ route('exams.index') }}">إعادة ضبط</a>
            </div>
        </form>
    </x-card>

    <div style="margin-block-start:18px">
        <x-card :flush="true">
            @if ($exams->isEmpty())
                <x-empty text="لا توجد اختبارات مطابقة لبحثك." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>الاختبار</th>
                            <th>الجلسة</th>
                            <th>النوع</th>
                            <th>الموعد</th>
                            <th>الأسئلة</th>
                            <th>النتائج</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($exams as $exam)
                            <tr>
                                <td>
                                    <a href="{{ route('exams.show', $exam) }}"><strong>{{ $exam->title ?: 'اختبار #'.$exam->id }}</strong></a>
                                    <div class="muted" style="font-size:.8rem">
                                        الدرجة الكلية: {{ $exam->total_score ?? '—' }}
                                        @if ($exam->pass_score !== null) · النجاح: {{ $exam->pass_score }} @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="num">{{ $exam->session?->session_code ?? '—' }}</div>
                                    <div class="muted" style="font-size:.8rem">{{ $exam->session?->course?->title ?? '—' }}</div>
                                </td>
                                <td><x-badge>{{ $exam->exam_type?->label() ?? '—' }}</x-badge></td>
                                <td class="num">{{ $exam->scheduled_start?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td><x-badge :tone="$exam->questions_count > 0 ? 'brand' : 'neutral'">{{ $exam->questions_count }}</x-badge></td>
                                <td><x-badge :tone="$exam->exam_results_count > 0 ? 'ok' : 'neutral'">{{ $exam->exam_results_count }}</x-badge></td>
                                <td class="actions-cell">
                                    <div class="actions" style="justify-content:flex-end">
                                        <a class="btn btn-ghost btn-sm" href="{{ route('exams.show', $exam) }}">تفاصيل</a>
                                        @can('EXAMS_MANAGE')
                                            <a class="btn btn-outline btn-sm" href="{{ route('exams.edit', $exam) }}">تعديل</a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>

                <div style="padding:0 18px 14px">{{ $exams->links() }}</div>
            @endif
        </x-card>
    </div>
@endsection
