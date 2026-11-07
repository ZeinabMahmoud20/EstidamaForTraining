@extends('layouts.app')

@section('title', 'مدرب جديد')

@section('content')
    <x-page-header title="مدرب جديد" subtitle="إضافة ملف مدرب داخلي أو خارجي مع تخصصاته وشهاداته وخبراته" />

    @include('trainers._form', ['trainer' => null])
@endsection
