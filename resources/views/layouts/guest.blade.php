<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    @include('partials.head')
    <title>@yield('title', 'تسجيل الدخول') | استدامة للتدريب والتطوير</title>
</head>
<body>
    <main class="guest">
        <div class="guest__card">
            <img class="guest__logo" src="{{ asset('images/logo.png') }}" alt="استدامة للتدريب والتطوير">
            @yield('content')
        </div>
    </main>
</body>
</html>
