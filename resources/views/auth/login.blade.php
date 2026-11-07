@extends('layouts.guest')

@section('title', 'تسجيل الدخول')

@section('content')
    <h1>مرحبًا بك</h1>
    <p class="sub">سجّل الدخول للوصول إلى نظام إدارة التدريب</p>

    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <x-form.input name="username" label="اسم المستخدم" required autofocus autocomplete="username" />
        <x-form.input name="password" label="كلمة المرور" type="password" required autocomplete="current-password" />
        <x-form.checkbox name="remember" label="تذكّرني على هذا الجهاز" />
        <button class="btn" type="submit">دخول <i class="fa-solid fa-arrow-left"></i></button>
    </form>
@endsection
