@php($editing = isset($accreditation) && $accreditation && $accreditation->exists)

<form method="POST" action="{{ $editing ? route('accreditations.update', $accreditation) : route('accreditations.store') }}">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <div class="grid">
        <x-card title="بيانات الاعتماد">
            <div class="form-grid">
                <x-form.input name="name" label="اسم الاعتماد" :value="$editing ? $accreditation->name : null" required />
                <x-form.input name="issuing_body" label="الجهة المانحة" :value="$editing ? $accreditation->issuing_body : null" />
                <x-form.textarea name="description" label="الوصف" wrapper="full"
                                 :value="$editing ? $accreditation->description : null" />
            </div>
        </x-card>

        <div class="actions" style="justify-content:flex-end">
            <a class="btn btn-ghost" href="{{ route('accreditations.index') }}">إلغاء</a>
            <button type="submit" class="btn">
                <i class="fa-solid fa-floppy-disk"></i>
                {{ $editing ? 'حفظ التعديلات' : 'إضافة الاعتماد' }}
            </button>
        </div>
    </div>
</form>
