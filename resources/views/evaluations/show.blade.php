@extends('layouts.app')

@section('title', $form->title)

@section('content')
    <x-page-header :title="$form->title" :subtitle="$form->code.' · إصدار '.$form->version">
        <a class="btn btn-ghost" href="{{ route('evaluations.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع للتقييمات
        </a>
        @can('EVALUATION_SUBMIT')
            @if ($form->is_active)
                <a class="btn" href="{{ route('evaluations.submissions.create', $form) }}">
                    <i class="fa-solid fa-pen-to-square"></i> تعبئة النموذج
                </a>
            @endif
        @endcan
        @can('EVALUATION_FORMS_MANAGE')
            <a class="btn btn-outline" href="{{ route('evaluations.edit', $form) }}">
                <i class="fa-solid fa-pen"></i> تعديل
            </a>
            <form method="POST" action="{{ route('evaluations.destroy', $form) }}"
                  data-confirm="هل تريد حذف نموذج التقييم؟">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit"><i class="fa-solid fa-trash"></i> حذف</button>
            </form>
        @endcan
    </x-page-header>

    <div class="grid grid-3" style="margin-block-end:18px">
        <x-stat label="الهدف" :value="$form->target_type?->label() ?? '—'" icon="fa-bullseye" />
        <x-stat label="المقيّم" :value="$form->evaluator_type?->label() ?? '—'" icon="fa-user-pen" />
        <x-stat label="الحالة" :value="$form->is_active ? 'مفعّل' : 'موقوف'" :icon="$form->is_active ? 'fa-circle-check' : 'fa-circle-pause'" :tone="$form->is_active ? 'ok' : 'neutral'" />
    </div>

    <x-card title="بيانات النموذج">
        <dl class="details">
            <div><dt>الكود</dt><dd class="num">{{ $form->code }}</dd></div>
            <div><dt>الإصدار</dt><dd class="num">{{ $form->version }}</dd></div>
            <div><dt>مجهول الهوية</dt><dd>{{ $form->is_anonymous ? 'نعم' : 'لا' }}</dd></div>
            <div><dt>نظامي</dt><dd>{{ $form->is_system ? 'نعم' : 'لا' }}</dd></div>
            <div><dt>أنشئ بواسطة</dt><dd>{{ $form->createdBy?->full_name ?? '—' }}</dd></div>
            <div><dt>تاريخ الإنشاء</dt><dd class="num">{{ $form->created_at?->format('Y-m-d') ?? '—' }}</dd></div>
        </dl>
    </x-card>

    <div style="margin-block-start:18px">
        <x-card title="أقسام وبنود التقييم" :flush="true">
            @if ($form->evaluationSections->isEmpty())
                <x-empty text="لم تُضف أقسام لهذا النموذج بعد." />
            @else
                @foreach ($form->evaluationSections as $section)
                    <div style="padding:16px 18px;border-block-end:1px solid var(--line)">
                        <h4 style="margin:0 0 10px">
                            {{ $section->sort_order }}. {{ $section->title }}
                            @if ($section->weight !== null)
                                <span class="muted" style="font-weight:500;font-size:.85rem">· الوزن {{ $section->weight }}%</span>
                            @endif
                        </h4>

                        @if ($section->evaluationItems->isEmpty())
                            <p class="muted" style="margin:0">لا توجد بنود في هذا القسم.</p>
                        @else
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>البند</th>
                                        <th>نوع الإجابة</th>
                                        <th>الوزن</th>
                                        <th>إلزامي</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($section->evaluationItems as $item)
                                        <tr>
                                            <td class="num">{{ $item->sort_order }}</td>
                                            <td>{{ $item->item_text }}</td>
                                            <td>{{ $item->answer_type?->label() ?? '—' }}</td>
                                            <td class="num">{{ $item->weight }}</td>
                                            <td>{{ $item->is_required ? 'نعم' : 'لا' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                @endforeach
            @endif
        </x-card>
    </div>
@endsection
