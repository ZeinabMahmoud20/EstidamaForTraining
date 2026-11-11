@extends('layouts.app')

@section('title', 'الأدوار والصلاحيات')

@section('content')
    <x-page-header title="الأدوار والصلاحيات" subtitle="حدد صلاحيات كل دور داخل النظام">
        <a class="btn" href="{{ route('roles.create') }}">
            <i class="fa-solid fa-plus"></i> دور جديد
        </a>
    </x-page-header>

    <x-card :flush="true">
        @if ($roles->isEmpty())
            <x-empty text="لا توجد أدوار معرفة بعد." />
        @else
            <x-table>
                <thead>
                    <tr>
                        <th>الدور</th>
                        <th>الاسم البرمجي</th>
                        <th>الصلاحيات</th>
                        <th>المستخدمون</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr>
                            <td>
                                <strong>{{ $roleLabels[$role->name] ?? $role->name }}</strong>
                                @if ($role->name === 'super_admin')
                                    <x-badge tone="warn">نظامي</x-badge>
                                @endif
                            </td>
                            <td class="num">{{ $role->name }}</td>
                            <td class="num">{{ $role->permissions_count }}</td>
                            <td class="num">{{ $role->users_count }}</td>
                            <td class="actions-cell">
                                <div class="actions" style="justify-content:flex-end">
                                    @if ($role->name === 'super_admin')
                                        <span class="muted">جميع الصلاحيات</span>
                                    @else
                                        <a class="btn btn-outline btn-sm" href="{{ route('roles.edit', $role) }}">تعديل</a>
                                        <form method="POST" action="{{ route('roles.destroy', $role) }}"
                                              data-confirm="هل تريد حذف الدور «{{ $role->name }}»؟">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger btn-sm" type="submit">حذف</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        @endif
    </x-card>
@endsection
