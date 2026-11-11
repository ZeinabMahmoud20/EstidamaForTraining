@extends('layouts.app')

@section('title', 'نموذج تقييم جديد')

@section('content')
    <x-page-header title="نموذج تقييم جديد" subtitle="أنشئ الأقسام والبنود التي ستُستخدم في التقييم">
        <a class="btn btn-ghost" href="{{ route('evaluations.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع
        </a>
    </x-page-header>

    @include('evaluations._form', ['form' => null])
@endsection
