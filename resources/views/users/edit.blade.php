@extends('layouts.app')

@section('title', 'تعديل المستخدم')

@section('content')
    <x-page-header :title="'تعديل '.($user->full_name ?: $user->username)" subtitle="حدّث بيانات الحساب والأدوار">
        <a class="btn btn-ghost" href="{{ route('users.show', $user) }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع
        </a>
    </x-page-header>

    @include('users._form', ['user' => $user])
@endsection
