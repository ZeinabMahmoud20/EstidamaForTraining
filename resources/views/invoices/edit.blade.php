@extends('layouts.app')

@section('title', 'تعديل الفاتورة')

@section('content')
    <x-page-header :title="'تعديل '.$invoice->invoice_no" subtitle="حدّث بيانات الفاتورة وقيمها">
        <a class="btn btn-ghost" href="{{ route('invoices.show', $invoice) }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع
        </a>
    </x-page-header>

    @include('invoices._form', ['invoice' => $invoice])
@endsection
