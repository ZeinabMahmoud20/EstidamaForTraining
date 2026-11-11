@extends('layouts.app')

@section('title', 'فاتورة جديدة')

@section('content')
    <x-page-header title="فاتورة جديدة" subtitle="أصدر فاتورة تدريب لمتدرب أو جهة تدريب">
        <a class="btn btn-ghost" href="{{ route('invoices.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع
        </a>
    </x-page-header>

    @include('invoices._form', ['invoice' => null])
@endsection
