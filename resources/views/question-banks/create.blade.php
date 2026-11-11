@extends('layouts.app')

@section('title', 'بنك أسئلة جديد')

@section('content')
    <x-page-header title="بنك أسئلة جديد" subtitle="أنشئ بنكًا وأضف إليه الأسئلة والخيارات">
        <a class="btn btn-ghost" href="{{ route('question-banks.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع
        </a>
    </x-page-header>

    @include('question-banks._form', ['bank' => null])
@endsection
