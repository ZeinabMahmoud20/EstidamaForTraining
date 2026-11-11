@extends('layouts.app')

@section('title', 'اختبار جديد')

@section('content')
    <x-page-header title="اختبار جديد" subtitle="اربط الاختبار بجلسة وحدد أسئلته ودرجاته">
        <a class="btn btn-ghost" href="{{ route('exams.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع
        </a>
    </x-page-header>

    @include('exams._form', ['exam' => null])
@endsection
