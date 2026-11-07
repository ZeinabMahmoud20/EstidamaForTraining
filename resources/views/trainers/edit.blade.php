@extends('layouts.app')

@section('title', 'تعديل المدرب')

@section('content')
    <x-page-header :title="'تعديل: '.$trainer->full_name" subtitle="تحديث بيانات المدرب وتخصصاته وشهاداته وخبراته" />

    @include('trainers._form')
@endsection
