@extends('layouts.app')

@section('title', 'المستخدمون')

@section('content')
    <x-page-header title="المستخدمون" subtitle="حسابات الدخول وربطها بالفروع وملفات المدربين والمتدربين">
        @can('ROLES_MANAGE')
            <a class="btn btn-ghost" href="{{ route('roles.index') }}">
                <i class="fa-solid fa-shield-halved"></i> الأدوار والصلاحيات
            </a>
        @endcan
        @can('USERS_MANAGE')
            <a class="btn" href="{{ route('users.create') }}">
                <i class="fa-solid fa-plus"></i> مستخدم جديد
            </a>
        @endcan
    </x-page-header>

    <x-card title="بحث وتصفية">
        <form method="GET" action="{{ route('users.index') }}" class="filter-grid">
            <x-form.input name="q" label="بحث" :value="request('q')" placeholder="اسم المستخدم أو الاسم أو البريد..." />
            <x-form.select name="role" label="الدور" :options="$roles" placeholder="كل الأدوار" :selected="request('role')" />
            <x-form.select name="branch_id" label="الفرع" :options="$branches" placeholder="كل الفروع" :selected="request('branch_id')" />

            <div class="field">
                <label for="is_active">الحالة</label>
                <select id="is_active" name="is_active" class="control">
                    <option value="">الكل</option>
                    <option value="1" @selected(request('is_active') === '1')>نشط</option>
                    <option value="0" @selected(request('is_active') === '0')>غير نشط</option>
                </select>
            </div>

            <div class="actions full">
                <button class="btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i> تطبيق</button>
                <a class="btn btn-ghost" href="{{ route('users.index') }}">إعادة ضبط</a>
            </div>
        </form>
    </x-card>

    <div style="margin-block-start:18px">
        <x-card :flush="true">
            @if ($users->isEmpty())
                <x-empty text="لا توجد حسابات مطابقة لبحثك." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>المستخدم</th>
                            <th>الأدوار</th>
                            <th>الفرع</th>
                            <th>الملف المرتبط</th>
                            <th>الحالة</th>
                            <th>آخر دخول</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>
                                    <a href="{{ route('users.show', $user) }}"><strong>{{ $user->full_name ?: $user->username }}</strong></a>
                                    <div class="muted num" style="font-size:.8rem">{{ $user->username }}</div>
                                    @if ($user->email)
                                        <div class="muted" style="font-size:.78rem">{{ $user->email }}</div>
                                    @endif
                                </td>
                                <td>
                                    @forelse ($user->roles as $role)
                                        <x-badge tone="brand">{{ $roleLabels[$role->name] ?? $role->name }}</x-badge>
                                    @empty
                                        <span class="muted">بدون دور</span>
                                    @endforelse
                                </td>
                                <td>{{ $user->branch?->name ?? '—' }}</td>
                                <td>
                                    @if ($user->trainer)
                                        <a href="{{ route('trainers.show', $user->trainer) }}">مدرب: {{ $user->trainer->full_name }}</a>
                                    @elseif ($user->trainee)
                                        <a href="{{ route('trainees.show', $user->trainee) }}">متدرب: {{ $user->trainee->full_name }}</a>
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($user->is_active)
                                        <x-badge tone="ok">نشط</x-badge>
                                    @else
                                        <x-badge>غير نشط</x-badge>
                                    @endif
                                </td>
                                <td class="num">{{ $user->last_login_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td class="actions-cell">
                                    <div class="actions" style="justify-content:flex-end">
                                        <a class="btn btn-ghost btn-sm" href="{{ route('users.show', $user) }}">تفاصيل</a>
                                        @can('USERS_MANAGE')
                                            <a class="btn btn-outline btn-sm" href="{{ route('users.edit', $user) }}">تعديل</a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>

                <div style="padding:0 18px 14px">{{ $users->links() }}</div>
            @endif
        </x-card>
    </div>
@endsection
