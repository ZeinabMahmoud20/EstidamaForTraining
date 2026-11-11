@extends('layouts.app')

@section('title', 'تعديل المتدرب')

@section('content')
    <x-page-header :title="'تعديل: '.$trainee->full_name" subtitle="تحديث بيانات المتدرب وجهات التدريب المرتبطة به" />

    @include('trainees._form')
@endsection
