@extends('layouts.app')

@section('title', 'جلسة جديدة')

@section('content')
    <x-page-header title="جلسة جديدة" subtitle="جدولة تنفيذ دورة وتحديد أيامها وقاعاتها ومدربيها" />

    @include('sessions._form', ['session' => null])
@endsection
