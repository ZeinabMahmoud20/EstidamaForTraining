@extends('layouts.app')

@section('title', 'دور جديد')

@section('content')
    <x-page-header title="دور جديد" subtitle="أنشئ دورًا وحدد الصلاحيات الممنوحة له">
        <a class="btn btn-ghost" href="{{ route('roles.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع
        </a>
    </x-page-header>

    @include('roles._form', ['role' => null])
@endsection
