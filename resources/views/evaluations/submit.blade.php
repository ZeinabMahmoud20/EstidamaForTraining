@extends('layouts.app')

@section('title', 'تعبئة '.$form->title)

@section('content')
    <x-page-header :title="$form->title"
                   :subtitle="'الجلسة: '.$session->session_code.' — '.($session->course?->title ?? 'دورة محذوفة')">
        <a class="btn btn-ghost" href="{{ route('evaluations.submissions.create', $form) }}">
            <i class="fa-solid fa-arrow-right"></i> تغيير الجلسة
        </a>
    </x-page-header>

    <form method="POST" action="{{ route('evaluations.submissions.store', $form) }}">
        @csrf
        <input type="hidden" name="session_id" value="{{ $session->id }}">

        @if ($form->evaluationSections->isEmpty())
            <x-card>
                <x-empty text="هذا النموذج لا يحتوي على بنود تقييم بعد." />
            </x-card>
        @else
            @foreach ($form->evaluationSections as $section)
                <x-card :title="$section->title" style="margin-block-end:18px">
                    @if ($section->evaluationItems->isEmpty())
                        <p class="muted" style="margin:0">لا توجد بنود في هذا القسم.</p>
                    @else
                        <div class="repeat-list">
                            @foreach ($section->evaluationItems as $item)
                                @php
                                    $row = old('answers.'.$item->id, []);
                                @endphp
                                <div class="repeat-row" data-repeat-row style="grid-template-columns:1fr">
                                    <div class="field">
                                        <label>
                                            {{ $item->item_text }}
                                            @if ($item->is_required)<span class="req">*</span>@endif
                                        </label>

                                        @if ($item->answer_type?->value === 'scale_1_5')
                                            <div style="display:flex;gap:14px;flex-wrap:wrap;margin-block-start:6px">
                                                @for ($score = 1; $score <= 5; $score++)
                                                    <label style="display:flex;gap:6px;align-items:center;font-weight:500">
                                                        <input type="radio" name="answers[{{ $item->id }}][score_value]"
                                                               value="{{ $score }}"
                                                               @checked((string) ($row['score_value'] ?? '') === (string) $score)
                                                               @required($item->is_required)>
                                                        {{ $score }}
                                                    </label>
                                                @endfor
                                            </div>
                                        @else
                                            <textarea name="answers[{{ $item->id }}][text_value]" class="control"
                                                      rows="3" maxlength="2000" style="margin-block-start:6px"
                                                      @required($item->is_required)>{{ $row['text_value'] ?? '' }}</textarea>
                                        @endif

                                        @error('answers.'.$item->id)<div class="error">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>
            @endforeach

            <div class="actions" style="justify-content:flex-end">
                <a class="btn btn-ghost" href="{{ route('evaluations.index') }}">إلغاء</a>
                <button type="submit" class="btn">
                    <i class="fa-solid fa-paper-plane"></i> إرسال التقييم
                </button>
            </div>
        @endif
    </form>
@endsection
