@extends('layouts.app')

@section('title', 'القاعات')

@section('content')
    <x-page-header title="القاعات والتجهيزات" subtitle="إدارة قاعات المركز وتجهيزاتها وفترات حجب الإتاحة">
        @can('HALLS_MANAGE')
            @if ($tab === 'equipment')
                <a class="btn btn-ghost" href="{{ route('halls.index') }}">
                    <i class="fa-solid fa-door-open"></i> القاعات
                </a>
            @else
                <a class="btn" href="{{ route('halls.create') }}">
                    <i class="fa-solid fa-plus"></i> قاعة جديدة
                </a>
            @endif
        @endcan
    </x-page-header>

    <div class="tabs">
        <a class="tabs__link {{ $tab === 'halls' ? 'is-active' : '' }}" href="{{ route('halls.index') }}">
            <i class="fa-solid fa-door-open"></i> القاعات
        </a>
        @can('HALLS_MANAGE')
            <a class="tabs__link {{ $tab === 'equipment' ? 'is-active' : '' }}" href="{{ route('halls.index', ['tab' => 'equipment']) }}">
                <i class="fa-solid fa-toolbox"></i> التجهيزات
            </a>
        @endcan
    </div>

    @if ($tab === 'equipment')
        @include('halls.tabs.equipment')
    @else
        @include('halls.tabs.halls')
    @endif
@endsection
