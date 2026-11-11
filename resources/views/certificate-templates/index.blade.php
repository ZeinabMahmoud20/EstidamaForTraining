@extends('layouts.app')

@section('title', 'قوالب الشهادات')

@section('content')
    <x-page-header title="قوالب الشهادات" subtitle="إدارة قوالب طباعة الشهادات ومتغيراتها">
        <a class="btn btn-ghost" href="{{ route('certificates.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع للشهادات
        </a>
        <a class="btn" href="{{ route('certificates.templates.create') }}">
            <i class="fa-solid fa-plus"></i> قالب جديد
        </a>
    </x-page-header>

    <x-card title="بحث">
        <form method="GET" action="{{ route('certificates.templates.index') }}" class="filter-grid">
            <x-form.input name="q" label="بحث" :value="request('q')" placeholder="اسم القالب..." />
            <div class="actions full">
                <button class="btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i> تطبيق</button>
                <a class="btn btn-ghost" href="{{ route('certificates.templates.index') }}">إعادة ضبط</a>
            </div>
        </form>
    </x-card>

    <div style="margin-block-start:18px">
        <x-card :flush="true">
            @if ($templates->isEmpty())
                <x-empty text="لا توجد قوالب شهادات بعد." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>القالب</th>
                            <th>عدد الشهادات</th>
                            <th>الحالة</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($templates as $template)
                            <tr>
                                <td>
                                    <strong>{{ $template->name }}</strong>
                                    @if ($template->file_path)
                                        <div class="muted" style="font-size:.8rem">{{ $template->file_path }}</div>
                                    @endif
                                </td>
                                <td><x-badge :tone="$template->certificates_count > 0 ? 'brand' : 'neutral'">{{ $template->certificates_count }}</x-badge></td>
                                <td><x-badge :tone="$template->is_active ? 'ok' : 'neutral'">{{ $template->is_active ? 'مفعّل' : 'موقوف' }}</x-badge></td>
                                <td class="actions-cell">
                                    <div class="actions" style="justify-content:flex-end">
                                        <a class="btn btn-outline btn-sm" href="{{ route('certificates.templates.edit', $template) }}">تعديل</a>
                                        <form method="POST" action="{{ route('certificates.templates.destroy', $template) }}"
                                              data-confirm="هل تريد حذف القالب؟">
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

                <div style="padding:0 18px 14px">{{ $templates->links() }}</div>
            @endif
        </x-card>
    </div>
@endsection
