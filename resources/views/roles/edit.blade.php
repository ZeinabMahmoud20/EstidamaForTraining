@extends('layouts.app')

@section('title', 'تعديل الدور')

@section('content')
    <x-page-header :title="'تعديل دور '.$role->name" subtitle="حدّث اسم الدور والصلاحيات الممنوحة له">
        <a class="btn btn-ghost" href="{{ route('roles.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع
        </a>
    </x-page-header>

    @include('roles._form', ['role' => $role])
@endsection
