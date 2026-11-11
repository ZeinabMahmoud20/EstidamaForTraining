@extends('layouts.app')

@section('title', 'الاعتمادات')

@section('content')
    <x-page-header title="الاعتمادات" subtitle="جهات الاعتماد والشهادات التي تعتمد برامج المركز">
        <a class="btn btn-ghost" href="{{ route('branches.index') }}">
            <i class="fa-solid fa-sitemap"></i> الفروع
        </a>
        <a class="btn" href="{{ route('accreditations.create') }}">
            <i class="fa-solid fa-plus"></i> اعتماد جديد
        </a>
    </x-page-header>

    <x-card title="بحث">
        <form method="GET" action="{{ route('accreditations.index') }}" class="filter-grid">
            <x-form.input name="q" label="بحث" :value="request('q')" placeholder="اسم الاعتماد أو الجهة المانحة..." />
            <div class="actions full">
                <button class="btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i> تطبيق</button>
                <a class="btn btn-ghost" href="{{ route('accreditations.index') }}">إعادة ضبط</a>
            </div>
        </form>
    </x-card>

    <div style="margin-block-start:18px">
        <x-card :flush="true">
            @if ($accreditations->isEmpty())
                <x-empty text="لا توجد اعتمادات مطابقة لبحثك." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>الاعتماد</th>
                            <th>الجهة المانحة</th>
                            <th>الفروع المرتبطة</th>
                            <th>الجلسات</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($accreditations as $accreditation)
                            <tr>
                                <td>
                                    <strong>{{ $accreditation->name }}</strong>
                                    @if ($accreditation->description)
                                        <div class="muted" style="font-size:.8rem">{{ $accreditation->description }}</div>
                                    @endif
                                </td>
                                <td>{{ $accreditation->issuing_body ?: '—' }}</td>
                                <td class="num">{{ $accreditation->branch_accreditations_count }}</td>
                                <td class="num">{{ $accreditation->course_sessions_count }}</td>
                                <td class="actions-cell">
                                    <div class="actions" style="justify-content:flex-end">
                                        <a class="btn btn-outline btn-sm" href="{{ route('accreditations.edit', $accreditation) }}">تعديل</a>
                                        <form method="POST" action="{{ route('accreditations.destroy', $accreditation) }}"
                                              data-confirm="هل تريد حذف الاعتماد «{{ $accreditation->name }}»؟">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger btn-sm" type="submit">حذف</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>

                <div style="padding:0 18px 14px">{{ $accreditations->links() }}</div>
            @endif
        </x-card>
    </div>
@endsection
