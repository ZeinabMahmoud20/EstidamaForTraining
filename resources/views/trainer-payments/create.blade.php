@extends('layouts.app')

@section('title', 'مستحق مدرب جديد')

@section('content')
    <x-page-header title="مستحق مدرب جديد" subtitle="سجّل ساعات المدرب وسعر الساعة أو المبلغ الإجمالي">
        <a class="btn btn-ghost" href="{{ route('trainer-payments.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع
        </a>
    </x-page-header>

    <form method="POST" action="{{ route('trainer-payments.store') }}">
        @csrf

        <x-card title="بيانات المستحق">
            <div class="form-grid">
                <x-form.select name="trainer_id" label="المدرب" :options="$trainers" required />
                <x-form.select name="session_id" label="الجلسة" :options="$sessions" required />
                <x-form.input name="total_hours" type="number" step="0.01" min="0" label="عدد الساعات" />
                <x-form.input name="hourly_rate" type="number" step="0.01" min="0" label="سعر الساعة" />
                <x-form.input name="amount" type="number" step="0.01" min="0" label="المبلغ الإجمالي"
                              hint="إذا تُرك فارغًا يُحسب المبلغ = الساعات × سعر الساعة." />
            </div>

            <div class="actions" style="margin-block-start:16px;justify-content:flex-end">
                <a class="btn btn-ghost" href="{{ route('trainer-payments.index') }}">إلغاء</a>
                <button type="submit" class="btn">
                    <i class="fa-solid fa-floppy-disk"></i> حفظ المستحق
                </button>
            </div>
        </x-card>
    </form>
@endsection
