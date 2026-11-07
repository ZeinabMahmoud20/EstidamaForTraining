<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    @include('partials.head')
    <title>@yield('title', 'لوحة التحكم') | استدامة للتدريب والتطوير</title>
    @stack('styles')
</head>
<body>
    @include('partials.sidebar')
    <div class="backdrop" id="backdrop"></div>

    <div class="main">
        @include('partials.topbar')

        <main class="content" id="content">
            @foreach (['success', 'error', 'warning', 'info'] as $type)
                @if (session($type))
                    <x-alert :type="$type">{{ session($type) }}</x-alert>
                @endif
            @endforeach

            @if ($errors->any())
                <x-alert type="error">يوجد أخطاء في البيانات المدخلة، برجاء مراجعة الحقول المظلّلة.</x-alert>
            @endif

            @yield('content')
        </main>
    </div>

    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
    @stack('scripts')
</body>
</html>
