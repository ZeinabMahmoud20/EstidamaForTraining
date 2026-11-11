@extends('layouts.app')

@section('title', 'تسجيل جديد')

@section('content')
    <x-page-header title="تسجيل جديد" subtitle="إنشاء تسجيل من ترشيح معتمد" />

    <x-card title="بيانات التسجيل">
        @if (empty($nominations))
            <x-empty text="لا توجد ترشيحات معتمدة غير مسجلة. اعتمد ترشيحًا أولًا من شاشة الترشيحات." />
            <div class="actions" style="margin-block-start:12px">
                <a class="btn" href="{{ route('nominations.index') }}">الذهاب إلى الترشيحات</a>
            </div>
        @else
            <form method="POST" action="{{ route('enrollments.store') }}">
                @csrf
                <div class="form-grid">
                    <x-form.select name="nomination_id" label="الترشيح المعتمد" :options="$nominations" required wrapper="full" />
                    <x-form.select name="status" label="حالة التسجيل" :options="$statuses" required />
                    <x-form.input name="attendance_pct" type="number" min="0" max="100" step="0.01" label="نسبة الحضور %" />
                    <x-form.input name="final_score_pct" type="number" min="0" max="100" step="0.01" label="الدرجة النهائية %" />
                    <x-form.input name="completed_at" type="date" label="تاريخ الإتمام" />
                </div>

                <div class="actions" style="justify-content:flex-end;margin-block-start:16px">
                    <a class="btn btn-ghost" href="{{ route('enrollments.index') }}">إلغاء</a>
                    <button type="submit" class="btn"><i class="fa-solid fa-plus"></i> إنشاء التسجيل</button>
                </div>
            </form>
        @endif
    </x-card>
@endsection
