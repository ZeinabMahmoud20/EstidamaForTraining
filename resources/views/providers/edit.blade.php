@extends('layouts.app')

@section('title', 'تعديل جهة التدريب')

@section('content')
    <x-page-header :title="'تعديل: '.$provider->name" subtitle="تحديث بيانات جهة التدريب وبيانات التواصل" />

    @include('providers._form')
@endsection
