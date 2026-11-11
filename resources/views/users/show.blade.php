@extends('layouts.app')

@section('title', $user->full_name ?: $user->username)

@section('content')
    <x-page-header :title="$user->full_name ?: $user->username"
                   subtitle="حساب المستخدم · {{ $user->username }}">
        <a class="btn btn-ghost" href="{{ route('users.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع للمستخدمين
        </a>
        @can('USERS_MANAGE')
            <a class="btn" href="{{ route('users.edit', $user) }}">
                <i class="fa-solid fa-pen"></i> تعديل
            </a>
            @if (! auth()->user()->is($user))
                <form method="POST" action="{{ route('users.destroy', $user) }}"
                      data-confirm="هل تريد حذف المستخدم «{{ $user->username }}»؟">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger" type="submit"><i class="fa-solid fa-trash"></i> حذف</button>
                </form>
            @endif
        @endcan
    </x-page-header>

    <div class="grid grid-2">
        <x-card title="بيانات الحساب">
            <dl class="details">
                <div><dt>اسم المستخدم</dt><dd class="num">{{ $user->username }}</dd></div>
                <div><dt>الاسم الكامل</dt><dd>{{ $user->full_name ?: '—' }}</dd></div>
                <div><dt>البريد الإلكتروني</dt><dd>{{ $user->email ?: '—' }}</dd></div>
                <div>
                    <dt>الحالة</dt>
                    <dd>
                        @if ($user->is_active)
                            <x-badge tone="ok">نشط</x-badge>
                        @else
                            <x-badge>غير نشط</x-badge>
                        @endif
                    </dd>
                </div>
                <div><dt>الفرع</dt><dd>{{ $user->branch?->name ?? '—' }}</dd></div>
                <div><dt>آخر دخول</dt><dd class="num">{{ $user->last_login_at?->format('Y-m-d H:i') ?? '—' }}</dd></div>
                <div><dt>تاريخ الإنشاء</dt><dd class="num">{{ $user->created_at?->format('Y-m-d H:i') ?? '—' }}</dd></div>
            </dl>
        </x-card>

        <x-card title="الأدوار والارتباطات">
            <dl class="details">
                <div>
                    <dt>الأدوار</dt>
                    <dd>
                        @forelse ($user->roles as $role)
                            <x-badge tone="brand">{{ $roleLabels[$role->name] ?? $role->name }}</x-badge>
                        @empty
                            <span class="muted">بدون دور</span>
                        @endforelse
                    </dd>
                </div>
                <div>
                    <dt>ملف المدرب</dt>
                    <dd>
                        @if ($user->trainer)
                            <a href="{{ route('trainers.show', $user->trainer) }}">{{ $user->trainer->full_name }}</a>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt>ملف المتدرب</dt>
                    <dd>
                        @if ($user->trainee)
                            <a href="{{ route('trainees.show', $user->trainee) }}">{{ $user->trainee->full_name }}</a>
                        @else
                            —
                        @endif
                    </dd>
                </div>
            </dl>
        </x-card>
    </div>
@endsection
