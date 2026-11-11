@extends('layouts.app')

@section('title', 'تعديل القاعة')

@section('content')
    <x-page-header :title="'تعديل: '.$hall->name" subtitle="تحديث بيانات القاعة وتجهيزاتها" />

    @include('halls._form')
@endsection
