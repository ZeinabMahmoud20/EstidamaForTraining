@extends('layouts.app')

@section('title', 'دورة جديدة')

@section('content')
    <x-page-header title="دورة جديدة" subtitle="إضافة برنامج تدريبي جديد إلى كتالوج الدورات" />

    @include('courses._form', ['course' => null])
@endsection
