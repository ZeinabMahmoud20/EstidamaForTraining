@extends('layouts.app')

@section('title', 'تعديل الدورة')

@section('content')
    <x-page-header :title="'تعديل: '.$course->title" subtitle="تحديث بيانات الدورة ووحداتها وشروط الالتحاق بها" />

    @include('courses._form')
@endsection
