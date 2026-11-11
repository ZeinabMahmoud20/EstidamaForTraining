@extends('layouts.app')

@section('title', 'تعبئة التقييم')

@section('content')
    <x-page-header :title="$form->title" subtitle="اختر الجلسة التي تريد تقييمها">
        <a class="btn btn-ghost" href="{{ route('evaluations.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع
        </a>
    </x-page-header>

    <x-card title="اختيار الجلسة">
        @if (empty($sessions))
            <x-empty text="لا توجد جلسات متاحة للتقييم." />
        @else
            <form method="GET" action="{{ route('evaluations.submissions.create', $form) }}">
                <div class="form-grid">
                    <x-form.select name="session_id" label="الجلسة" :options="$sessions" required
                                   :selected="request('session_id')" />
                </div>
                <div class="actions" style="margin-block-start:16px">
                    <button type="submit" class="btn">
                        <i class="fa-solid fa-arrow-left"></i> بدء التقييم
                    </button>
                </div>
            </form>
        @endif
    </x-card>
@endsection
