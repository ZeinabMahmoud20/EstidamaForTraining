@extends('layouts.app')

@section('title', 'إصدار شهادة')

@section('content')
    <x-page-header title="إصدار شهادة" subtitle="اختر التسجيل والقالب ونوع الشهادة">
        <a class="btn btn-ghost" href="{{ route('certificates.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع
        </a>
    </x-page-header>

    <form method="POST" action="{{ route('certificates.store') }}">
        @csrf

        <x-card title="بيانات الشهادة">
            @if (empty($enrollments))
                <x-empty text="لا توجد تسجيلات متاحة لإصدار شهادات لها. تأكد من وجود تسجيلات نشطة بدون شهادة." />
            @else
                <div class="form-grid">
                    <x-form.select name="enrollment_id" label="التسجيل" :options="$enrollments" required
                                   placeholder="اختر التسجيل..." />
                    <x-form.select name="template_id" label="القالب" :options="$templates" placeholder="قالب افتراضي" />
                    <x-form.select name="certificate_type" label="نوع الشهادة" :options="$types" required />
                    <x-form.input name="issue_date" type="date" label="تاريخ الإصدار" required
                                  :value="now()->toDateString()" />
                    <x-form.input name="expiry_date" type="date" label="تاريخ الانتهاء"
                                  hint="اتركه فارغًا لشهادة بدون تاريخ انتهاء." />
                </div>

                <div class="actions" style="margin-block-start:16px;justify-content:flex-end">
                    <button type="submit" class="btn">
                        <i class="fa-solid fa-certificate"></i> إصدار الشهادة
                    </button>
                </div>
            @endif
        </x-card>
    </form>
@endsection
