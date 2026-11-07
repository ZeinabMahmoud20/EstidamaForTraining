@extends('layouts.app')

@section('title', 'جهة تدريب جديدة')

@section('content')
    <x-page-header title="جهة تدريب جديدة" subtitle="إضافة جهة أو مركز خارجي للتعاون في تنفيذ البرامج" />

    @include('providers._form', ['provider' => null])
@endsection
