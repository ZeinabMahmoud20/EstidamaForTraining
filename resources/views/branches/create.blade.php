@extends('layouts.app')

@section('title', 'فرع جديد')

@section('content')
    <x-page-header title="فرع جديد" subtitle="أضف فرعًا جديدًا للمركز">
        <a class="btn btn-ghost" href="{{ route('branches.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع
        </a>
    </x-page-header>

    @include('branches._form', ['branch' => null])
@endsection
