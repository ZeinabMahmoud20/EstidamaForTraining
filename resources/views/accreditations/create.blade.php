@extends('layouts.app')

@section('title', 'اعتماد جديد')

@section('content')
    <x-page-header title="اعتماد جديد" subtitle="أضف جهة اعتماد جديدة">
        <a class="btn btn-ghost" href="{{ route('accreditations.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع
        </a>
    </x-page-header>

    @include('accreditations._form', ['accreditation' => null])
@endsection
