@extends('layouts.app')

@section('title', 'الفروع')

@section('content')
    <x-page-header title="الفروع" subtitle="فروع المركز واعتماداتها الإدارية">
        <a class="btn btn-ghost" href="{{ route('accreditations.index') }}">
            <i class="fa-solid fa-certificate"></i> الاعتمادات
        </a>
        <a class="btn" href="{{ route('branches.create') }}">
            <i class="fa-solid fa-plus"></i> فرع جديد
        </a>
    </x-page-header>

    <x-card title="بحث وتصفية">
        <form method="GET" action="{{ route('branches.index') }}" class="filter-grid">
            <x-form.input name="q" label="بحث" :value="request('q')" placeholder="الكود أو الاسم أو المدينة..." />

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
                <a class="btn btn-ghost" href="{{ route('branches.index') }}">إعادة ضبط</a>
            </div>
        </form>
    </x-card>

    <div style="margin-block-start:18px">
        <x-card :flush="true">
            @if ($branches->isEmpty())
                <x-empty text="لا توجد فروع مطابقة لبحثك." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>الفرع</th>
                            <th>المدينة</th>
                            <th>الهاتف</th>
                            <th>المستخدمون</th>
                            <th>القاعات</th>
                            <th>الجلسات</th>
                            <th>الاعتمادات</th>
                            <th>الحالة</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($branches as $branch)
                            <tr>
                                <td>
                                    <a href="{{ route('branches.show', $branch) }}"><strong>{{ $branch->name }}</strong></a>
                                    <div class="muted num" style="font-size:.78rem">{{ $branch->code }}</div>
                                </td>
                                <td>{{ $branch->city ?: '—' }}</td>
                                <td class="num">{{ $branch->phone ?: '—' }}</td>
                                <td class="num">{{ $branch->users_count }}</td>
                                <td class="num">{{ $branch->halls_count }}</td>
                                <td class="num">{{ $branch->course_sessions_count }}</td>
                                <td class="num">{{ $branch->branch_accreditations_count }}</td>
                                <td>
                                    @if ($branch->is_active)
                                        <x-badge tone="ok">نشط</x-badge>
                                    @else
                                        <x-badge>غير نشط</x-badge>
                                    @endif
                                </td>
                                <td class="actions-cell">
                                    <div class="actions" style="justify-content:flex-end">
                                        <a class="btn btn-ghost btn-sm" href="{{ route('branches.show', $branch) }}">تفاصيل</a>
                                        <a class="btn btn-outline btn-sm" href="{{ route('branches.edit', $branch) }}">تعديل</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>

                <div style="padding:0 18px 14px">{{ $branches->links() }}</div>
            @endif
        </x-card>
    </div>
@endsection
