@php
    $editing = isset($nomination) && $nomination && $nomination->exists;
@endphp

<form method="POST" action="{{ $editing ? route('nominations.update', $nomination) : route('nominations.store') }}">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <div class="grid grid-2">
        <x-card title="بيانات الترشيح">
            <div class="form-grid">
                <x-form.select name="session_id" label="الجلسة" :options="$sessions" required
                               :selected="$editing ? $nomination->session_id : ($selectedSession ?? null)" />

                <x-form.select name="trainee_id" label="المتدرب" :options="$trainees" required
                               :selected="$editing ? $nomination->trainee_id : ($selectedTrainee ?? null)" />

                <x-form.select name="source" label="مصدر الترشيح" :options="$sources" required
                               :selected="$editing ? $nomination->source?->value : null" />

                <x-form.select name="nominated_by_provider_id" label="جهة الترشيح" :options="$providers"
                               placeholder="بدون جهة"
                               :selected="$editing ? $nomination->nominated_by_provider_id : null"
                               hint="مطلوب عند اختيار المصدر «ترشيح جهة»." />

                <x-form.textarea name="notes" label="ملاحظات" wrapper="full"
                                 :value="$editing ? ($nomination->application_data['notes'] ?? null) : null" />
            </div>
        </x-card>

        <x-card title="معايير الالتحاق">
            <p class="muted" style="margin-block-start:0">
                يتم فحص معايير الدورة تلقائيًا عند حفظ الترشيح. إذا لم يستوفِ المتدرب معيارًا إلزاميًا فلن يُحفظ الترشيح إلا بتجاوز معايير الالتحاق.
            </p>

            @can('OVERRIDE_CRITERIA')
                <x-form.checkbox name="override" label="تجاوز معايير الالتحاق غير المستوفاة"
                                 :checked="$editing ? (bool) ($nomination->application_data['criteria_override'] ?? false) : false" />
            @endcan

            <div class="alert alert-info" style="margin-block-start:10px">
                <i class="fa-solid fa-circle-info"></i>
                يمكنك مراجعة نتيجة فحص المعايير تفصيليًا في صفحة الترشيح بعد الحفظ.
            </div>
        </x-card>
    </div>

    <div class="actions" style="justify-content:flex-end;margin-block-start:18px">
        <a class="btn btn-ghost" href="{{ $editing ? route('nominations.show', $nomination) : route('nominations.index') }}">إلغاء</a>
        <button type="submit" class="btn">
            <i class="fa-solid fa-floppy-disk"></i>
            {{ $editing ? 'حفظ التعديلات' : 'إنشاء الترشيح' }}
        </button>
    </div>
</form>
