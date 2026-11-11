@extends('layouts.app')

@section('title', 'تعديل الاختبار')

@section('content')
    <x-page-header :title="'تعديل '.($exam->title ?: 'اختبار #'.$exam->id)" subtitle="حدّث بيانات الاختبار والأسئلة المختارة">
        <a class="btn btn-ghost" href="{{ route('exams.show', $exam) }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع
        </a>
    </x-page-header>

    @include('exams._form', ['exam' => $exam])
@endsection
