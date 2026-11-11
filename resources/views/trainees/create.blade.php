@extends('layouts.app')

@section('title', 'متدرب جديد')

@section('content')
    <x-page-header title="متدرب جديد" subtitle="إضافة ملف متدرب مع بياناته التعليمية والوظيفية وجهات التدريب المرتبطة به" />

    @include('trainees._form', ['trainee' => null])
@endsection
