@extends('layouts.app')

@section('title', 'بنوك الأسئلة')

@section('content')
    <x-page-header title="بنوك الأسئلة" subtitle="إدارة بنوك أسئلة الاختبارات وتصنيفها حسب الدورة والوحدة">
        @can('QUESTION_BANK_MANAGE')
            <a class="btn" href="{{ route('question-banks.create') }}">
                <i class="fa-solid fa-plus"></i> بنك أسئلة جديد
            </a>
        @endcan
    </x-page-header>

    <x-card title="بحث وتصفية">
        <form method="GET" action="{{ route('question-banks.index') }}" class="filter-grid">
            <x-form.input name="q" label="بحث" :value="request('q')" placeholder="اسم البنك أو الدورة..." />
            <x-form.select name="course_id" label="الدورة" :options="$courses" placeholder="كل الدورات"
                           :selected="request('course_id')" />
            <div class="actions full">
                <button class="btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i> تطبيق</button>
                <a class="btn btn-ghost" href="{{ route('question-banks.index') }}">إعادة ضبط</a>
            </div>
        </form>
    </x-card>

    <div style="margin-block-start:18px">
        <x-card :flush="true">
            @if ($banks->isEmpty())
                <x-empty text="لا توجد بنوك أسئلة مطابقة لبحثك." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>البنك</th>
                            <th>الدورة</th>
                            <th>الوحدة</th>
                            <th>عدد الأسئلة</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($banks as $bank)
                            <tr>
                                <td><strong>{{ $bank->name ?: 'بنك #'.$bank->id }}</strong></td>
                                <td>{{ $bank->course?->title ?? '—' }}</td>
                                <td class="muted">{{ $bank->module?->title ?? '—' }}</td>
                                <td><x-badge :tone="$bank->questions_count > 0 ? 'brand' : 'neutral'">{{ $bank->questions_count }}</x-badge></td>
                                <td class="actions-cell">
                                    <div class="actions" style="justify-content:flex-end">
                                        @can('QUESTION_BANK_MANAGE')
                                            <a class="btn btn-outline btn-sm" href="{{ route('question-banks.edit', $bank) }}">تعديل</a>
                                            <form method="POST" action="{{ route('question-banks.destroy', $bank) }}"
                                                  data-confirm="هل تريد حذف بنك الأسئلة «{{ $bank->name ?: '#'.$bank->id }}»؟">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-danger btn-sm" type="submit">حذف</button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>

                <div style="padding:0 18px 14px">{{ $banks->links() }}</div>
            @endif
        </x-card>
    </div>
@endsection
