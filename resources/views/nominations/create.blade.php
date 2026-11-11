@extends('layouts.app')

@section('title', 'ترشيح جديد')

@section('content')
    <x-page-header title="ترشيح جديد" subtitle="ترشيح متدرب لجلسة تدريبية مع فحص معايير الالتحاق" />

    @include('nominations._form', ['nomination' => null])
@endsection
