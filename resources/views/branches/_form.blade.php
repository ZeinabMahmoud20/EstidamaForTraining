@php($editing = isset($branch) && $branch && $branch->exists)

<form method="POST" action="{{ $editing ? route('branches.update', $branch) : route('branches.store') }}">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <div class="grid">
        <x-card title="بيانات الفرع">
            <div class="form-grid">
                <x-form.input name="code" label="كود الفرع" :value="$editing ? $branch->code : null" required
                              hint="حروف إنجليزية وأرقام وشرطات فقط." />
                <x-form.input name="name" label="اسم الفرع" :value="$editing ? $branch->name : null" required />
                <x-form.input name="city" label="المدينة" :value="$editing ? $branch->city : null" />
                <x-form.input name="phone" label="الهاتف" :value="$editing ? $branch->phone : null" />
                <x-form.textarea name="address" label="العنوان" wrapper="full" :value="$editing ? $branch->address : null" />
                <x-form.checkbox name="is_active" label="الفرع نشط ومتاح" :checked="$editing ? $branch->is_active : true" wrapper="full" />
            </div>
        </x-card>

        <div class="actions" style="justify-content:flex-end">
            <a class="btn btn-ghost" href="{{ $editing ? route('branches.show', $branch) : route('branches.index') }}">إلغاء</a>
            <button type="submit" class="btn">
                <i class="fa-solid fa-floppy-disk"></i>
                {{ $editing ? 'حفظ التعديلات' : 'إضافة الفرع' }}
            </button>
        </div>
    </div>
</form>
