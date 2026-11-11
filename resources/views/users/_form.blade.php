@php($editing = isset($user) && $user && $user->exists)
@php($selectedRoles = old('roles', $editing ? $user->roles->pluck('name')->all() : []))

<form method="POST" action="{{ $editing ? route('users.update', $user) : route('users.store') }}">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <div class="grid">
        <x-card title="بيانات الحساب">
            <div class="form-grid">
                <x-form.input name="username" label="اسم المستخدم" :value="$editing ? $user->username : null" required
                              hint="حروف إنجليزية وأرقام ونقطة وشرطات فقط." />
                <x-form.input name="full_name" label="الاسم الكامل" :value="$editing ? $user->full_name : null" />
                <x-form.input name="email" type="email" label="البريد الإلكتروني" :value="$editing ? $user->email : null" />

                <x-form.input name="password" type="password" :label="$editing ? 'كلمة مرور جديدة' : 'كلمة المرور'"
                              :required="! $editing" autocomplete="new-password"
                              :hint="$editing ? 'اتركها فارغة للإبقاء على كلمة المرور الحالية.' : '8 أحرف على الأقل.'" />
                <x-form.input name="password_confirmation" type="password" label="تأكيد كلمة المرور"
                              :required="! $editing" autocomplete="new-password" />

                <x-form.select name="branch_id" label="الفرع" :options="$branches" placeholder="بدون فرع"
                               :selected="$editing ? $user->branch_id : null" />
                <x-form.select name="trainer_id" label="ملف المدرب" :options="$trainers" placeholder="غير مرتبط بمدرب"
                               :selected="$editing ? $user->trainer_id : null" />
                <x-form.select name="trainee_id" label="ملف المتدرب" :options="$trainees" placeholder="غير مرتبط بمتدرب"
                               :selected="$editing ? $user->trainee_id : null" />

                <x-form.checkbox name="is_active" label="الحساب نشط ويمكنه تسجيل الدخول"
                                 :checked="$editing ? $user->is_active : true" wrapper="full" />
            </div>
        </x-card>

        <x-card title="أدوار المستخدم">
            @can('ROLES_MANAGE')
                @if ($roles->isEmpty())
                    <x-alert type="info">لا توجد أدوار معرفة بعد.</x-alert>
                @else
                    <div class="form-grid">
                        @foreach ($roles as $role)
                            <div class="field">
                                <label class="check" for="role-{{ $role->id }}" style="font-weight:400">
                                    <input type="checkbox" id="role-{{ $role->id }}" name="roles[]" value="{{ $role->name }}"
                                           @checked(in_array($role->name, $selectedRoles, true))>
                                    {{ $roleLabels[$role->name] ?? $role->name }}
                                    <span class="muted num" style="font-size:.75rem">{{ $role->name }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                @endif
            @else
                <x-alert type="info">لا تملك صلاحية تعديل الأدوار، وسيتم الحفاظ على أدوار المستخدم الحالية.</x-alert>
            @endcan
        </x-card>

        <div class="actions" style="justify-content:flex-end">
            <a class="btn btn-ghost" href="{{ $editing ? route('users.show', $user) : route('users.index') }}">إلغاء</a>
            <button type="submit" class="btn">
                <i class="fa-solid fa-floppy-disk"></i>
                {{ $editing ? 'حفظ التعديلات' : 'إضافة المستخدم' }}
            </button>
        </div>
    </div>
</form>
