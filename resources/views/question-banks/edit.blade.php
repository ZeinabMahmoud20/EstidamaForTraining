@extends('layouts.app')

@section('title', 'تعديل بنك الأسئلة')

@section('content')
    <x-page-header :title="'تعديل '.($bank->name ?: 'بنك #'.$bank->id)" subtitle="حدّث بيانات البنك والأسئلة والخيارات">
        <a class="btn btn-ghost" href="{{ route('question-banks.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع
        </a>
    </x-page-header>

    @include('question-banks._form', ['bank' => $bank])
@endsection
