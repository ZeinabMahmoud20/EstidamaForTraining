@extends('layouts.app')

@section('title', 'تعديل الفرع')

@section('content')
    <x-page-header :title="'تعديل '.$branch->name" subtitle="حدّث بيانات الفرع">
        <a class="btn btn-ghost" href="{{ route('branches.show', $branch) }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع
        </a>
    </x-page-header>

    @include('branches._form', ['branch' => $branch])
@endsection
