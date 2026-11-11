@extends('layouts.app')

@section('title', 'قالب شهادة جديد')

@section('content')
    <x-page-header title="قالب شهادة جديد" subtitle="أضف قالب HTML لطباعة الشهادات">
        <a class="btn btn-ghost" href="{{ route('certificates.templates.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع
        </a>
    </x-page-header>

    @include('certificate-templates._form', ['template' => null])
@endsection
