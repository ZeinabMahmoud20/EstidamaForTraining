@extends('layouts.app')

@section('title', 'تعديل الترشيح')

@section('content')
    <x-page-header :title="'تعديل ترشيح: '.($nomination->trainee?->full_name ?? '')"
                   subtitle="تحديث بيانات الترشيح قبل اتخاذ القرار" />

    @include('nominations._form')
@endsection
