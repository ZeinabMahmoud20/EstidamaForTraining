@extends('layouts.app')

@section('title', 'تعديل الاعتماد')

@section('content')
    <x-page-header :title="'تعديل '.$accreditation->name" subtitle="حدّث بيانات الاعتماد">
        <a class="btn btn-ghost" href="{{ route('accreditations.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع
        </a>
    </x-page-header>

    @include('accreditations._form', ['accreditation' => $accreditation])
@endsection
