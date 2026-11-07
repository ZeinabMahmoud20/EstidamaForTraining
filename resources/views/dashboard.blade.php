@extends('layouts.app')

@section('title', 'لوحة التحكم')

@section('content')
    <x-page-header :title="'مرحبًا، ' . ($user->full_name ?: $user->username)" subtitle="نظرة سريعة على نشاط المركز" />

    @if (count($stats))
        <div class="grid grid-4" style="margin-block-end:22px">
            @foreach ($stats as $s)
                <x-stat :label="$s['label']" :value="$s['value']" :icon="$s['icon']" :tone="$s['tone']" />
            @endforeach
        </div>
    @endif

    <x-card title="ابدأ من هنا">
        <p class="muted" style="margin:0">استخدم القائمة الجانبية للوصول إلى وحدات النظام. الوحدات المعلّمة "قريبًا" سيتم تفعيلها تباعًا.</p>
    </x-card>
@endsection
