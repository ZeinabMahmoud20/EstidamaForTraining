@extends('layouts.app')

@section('title', 'تعديل التسجيل')

@section('content')
    <x-page-header :title="'تعديل تسجيل: '.($enrollment->trainee?->full_name ?? '')"
                   :subtitle="($enrollment->session?->session_code ?? '').' · '.($enrollment->session?->course?->title ?? '')" />

    @include('enrollments._form')
@endsection
