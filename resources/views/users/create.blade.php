@extends('layouts.app')

@section('title', 'مستخدم جديد')

@section('content')
    <x-page-header title="مستخدم جديد" subtitle="أنشئ حساب دخول واربطه بدور وفرع">
        <a class="btn btn-ghost" href="{{ route('users.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع
        </a>
    </x-page-header>

    @include('users._form', ['user' => null])
@endsection
