@extends('layouts.app')

@section('title', 'تعديل قالب الشهادة')

@section('content')
    <x-page-header :title="'تعديل '.$template->name" subtitle="حدّث محتوى القالب وإعداداته">
        <a class="btn btn-ghost" href="{{ route('certificates.templates.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع
        </a>
    </x-page-header>

    @include('certificate-templates._form', ['template' => $template])
@endsection
