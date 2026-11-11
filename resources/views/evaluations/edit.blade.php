@extends('layouts.app')

@section('title', 'تعديل نموذج التقييم')

@section('content')
    <x-page-header :title="'تعديل '.$form->title" subtitle="حدّث بيانات النموذج وأقسامه وبنوده">
        <a class="btn btn-ghost" href="{{ route('evaluations.show', $form) }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع
        </a>
    </x-page-header>

    @include('evaluations._form', ['form' => $form])
@endsection
