@php($editing = isset($role) && $role && $role->exists)

<form method="POST" action="{{ $editing ? route('roles.update', $role) : route('roles.store') }}">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <div class="grid">
        <x-card title="بيانات الدور">
            <div class="form-grid">
                <x-form.input name="name" label="اسم الدور البرمجي" :value="$editing ? $role->name : null" required
                              hint="استخدم حروفًا إنجليزية صغيرة وشرطات، مثل quality_officer." wrapper="full" />
            </div>
        </x-card>

        <x-card title="الصلاحيات">
            @foreach ($permissionGroups as $group => $permissions)
                <div style="margin-block-end:16px">
                    <h4 style="margin-block-end:8px">{{ $group }}</h4>
                    <div class="form-grid">
                        @foreach ($permissions as $code => $label)
                            <div class="field">
                                <label class="check" for="permission-{{ $code }}" style="font-weight:400">
                                    <input type="checkbox" id="permission-{{ $code }}" name="permissions[]"
                                           value="{{ $code }}" @checked(in_array($code, $selectedPermissions, true))>
                                    {{ $label }}
                                    <span class="muted num" style="font-size:.72rem">{{ $code }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </x-card>

        <div class="actions" style="justify-content:flex-end">
            <a class="btn btn-ghost" href="{{ route('roles.index') }}">إلغاء</a>
            <button type="submit" class="btn">
                <i class="fa-solid fa-floppy-disk"></i>
                {{ $editing ? 'حفظ التعديلات' : 'إضافة الدور' }}
            </button>
        </div>
    </div>
</form>
