@extends('layouts.app')

@section('title', 'قاعة جديدة')

@section('content')
    <x-page-header title="قاعة جديدة" subtitle="إضافة قاعة وتحديد سعتها وتجهيزاتها" />

    @include('halls._form', ['hall' => null])
@endsection
