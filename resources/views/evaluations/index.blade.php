@extends('layouts.app')

@section('title', 'نماذج التقييم')

@section('content')
    <x-page-header title="التقييمات" subtitle="بناء نماذج تقييم البرامج والمدربين والقاعات ومتابعة النتائج">
        @can('EVALUATION_REPORTS_VIEW')
            <a class="btn btn-ghost" href="{{ route('evaluations.reports.index') }}">
                <i class="fa-solid fa-chart-simple"></i> تقارير التقييمات
            </a>
        @endcan
        @can('EVALUATION_FORMS_MANAGE')
            <a class="btn" href="{{ route('evaluations.create') }}">
                <i class="fa-solid fa-plus"></i> نموذج تقييم جديد
            </a>
        @endcan
    </x-page-header>

    <x-card title="بحث وتصفية">
        <form method="GET" action="{{ route('evaluations.index') }}" class="filter-grid">
            <x-form.input name="q" label="بحث" :value="request('q')" placeholder="كود أو عنوان النموذج..." />
            <x-form.select name="target_type" label="الهدف" :options="$targets" placeholder="كل الأهداف"
                           :selected="request('target_type')" />
            <x-form.select name="evaluator_type" label="المقيّم" :options="$evaluators" placeholder="كل المقيّمين"
                           :selected="request('evaluator_type')" />
            <div class="actions full">
                <button class="btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i> تطبيق</button>
                <a class="btn btn-ghost" href="{{ route('evaluations.index') }}">إعادة ضبط</a>
            </div>
        </form>
    </x-card>

    <div style="margin-block-start:18px">
        <x-card :flush="true">
            @if ($forms->isEmpty())
                <x-empty text="لا توجد نماذج تقييم مطابقة لبحثك." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>النموذج</th>
                            <th>الهدف</th>
                            <th>المقيّم</th>
                            <th>الأقسام / البنود</th>
                            <th>الإرسالات</th>
                            <th>الحالة</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($forms as $form)
                            <tr>
                                <td>
                                    <a href="{{ route('evaluations.show', $form) }}"><strong>{{ $form->title }}</strong></a>
                                    <div class="muted num" style="font-size:.8rem">{{ $form->code }} · إصدار {{ $form->version }}</div>
                                </td>
                                <td>{{ $form->target_type?->label() ?? '—' }}</td>
                                <td>{{ $form->evaluator_type?->label() ?? '—' }}</td>
                                <td class="num">{{ $form->evaluation_sections_count }} / {{ $form->evaluation_items_count }}</td>
                                <td><x-badge :tone="$form->evaluation_submissions_count > 0 ? 'ok' : 'neutral'">{{ $form->evaluation_submissions_count }}</x-badge></td>
                                <td>
                                    <x-badge :tone="$form->is_active ? 'ok' : 'neutral'">{{ $form->is_active ? 'مفعّل' : 'موقوف' }}</x-badge>
                                </td>
                                <td class="actions-cell">
                                    <div class="actions" style="justify-content:flex-end">
                                        <a class="btn btn-ghost btn-sm" href="{{ route('evaluations.show', $form) }}">تفاصيل</a>
                                        @can('EVALUATION_SUBMIT')
                                            @if ($form->is_active)
                                                <a class="btn btn-outline btn-sm" href="{{ route('evaluations.submissions.create', $form) }}">تعبئة</a>
                                            @endif
                                        @endcan
                                        @can('EVALUATION_FORMS_MANAGE')
                                            <a class="btn btn-outline btn-sm" href="{{ route('evaluations.edit', $form) }}">تعديل</a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>

                <div style="padding:0 18px 14px">{{ $forms->links() }}</div>
            @endif
        </x-card>
    </div>
@endsection
