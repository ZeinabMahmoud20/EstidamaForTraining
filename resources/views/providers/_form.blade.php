@php($editing = isset($provider) && $provider && $provider->exists)

<form method="POST" action="{{ $editing ? route('providers.update', $provider) : route('providers.store') }}">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <div class="grid">
        <x-card title="بيانات جهة التدريب">
            <div class="form-grid">
                <x-form.input name="name" label="اسم جهة التدريب" :value="$editing ? $provider->name : null" required />
                <x-form.input name="contact_person" label="مسؤول التواصل"
                              :value="$editing ? $provider->contact_person : null" />

                <x-form.input name="phone" label="الهاتف" :value="$editing ? $provider->phone : null" />
                <x-form.input name="email" type="email" label="البريد الإلكتروني"
                              :value="$editing ? $provider->email : null" />

                <x-form.textarea name="address" label="العنوان" wrapper="full"
                                 :value="$editing ? $provider->address : null" />

                <x-form.checkbox name="is_active" label="جهة التدريب نشطة ومتاحة"
                                 :checked="$editing ? $provider->is_active : true" />
            </div>
        </x-card>

        <div class="actions" style="justify-content:flex-end">
            <a class="btn btn-ghost" href="{{ $editing ? route('providers.show', $provider) : route('providers.index') }}">إلغاء</a>
            <button type="submit" class="btn">
                <i class="fa-solid fa-floppy-disk"></i>
                {{ $editing ? 'حفظ التعديلات' : 'إضافة جهة التدريب' }}
            </button>
        </div>
    </div>
</form>
