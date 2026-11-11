@extends('layouts.app')

@section('title', 'تعديل الجلسة')

@section('content')
    <x-page-header :title="'تعديل: '.$session->session_code" subtitle="تحديث بيانات الجلسة وأيامها ومدربيها" />

    @include('sessions._form')
@endsection
