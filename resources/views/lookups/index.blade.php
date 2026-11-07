@extends('layouts.app')

@section('title', 'التصنيفات والأنواع')

@section('content')
    <x-page-header title="التصنيفات والأنواع"
                   subtitle="إدارة القوائم المرجعية المستخدمة في الدورات والمتدربين" />

    <nav class="tabs" aria-label="أقسام القوائم المرجعية">
        @foreach ($tabs as $key => $label)
            <a class="tabs__link {{ $tab === $key ? 'is-active' : '' }}"
               href="{{ route('lookups.index', ['tab' => $key]) }}">{{ $label }}</a>
        @endforeach
    </nav>

    @include('lookups.tabs.'.$tab)
@endsection
