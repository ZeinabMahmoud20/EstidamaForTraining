@extends('layouts.app')

@section('title', 'المدربون')

@section('content')
    <x-page-header title="المدربون" subtitle="ملفات المدربين الداخليين والخارجيين وتخصصاتهم وشهاداتهم">
        @can('TRAINERS_MANAGE')
            <a class="btn" href="{{ route('trainers.create') }}">
                <i class="fa-solid fa-plus"></i> مدرب جديد
            </a>
        @endcan
    </x-page-header>

    <x-card title="بحث وتصفية">
        <form method="GET" action="{{ route('trainers.index') }}" class="filter-grid">
            <x-form.input name="q" label="بحث" :value="request('q')" placeholder="الاسم أو الرقم القومي أو الهاتف..." />
            <x-form.select name="trainer_type" label="نوع المدرب" :options="$trainerTypes" placeholder="كل الأنواع"
                           :selected="request('trainer_type')" />
            <x-form.select name="provider_id" label="جهة التدريب" :options="$providers" placeholder="كل الجهات"
                           :selected="request('provider_id')" />
            <x-form.select name="category_id" label="التخصص" :options="$categories" placeholder="كل التخصصات"
                           :selected="request('category_id')" />

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
                <a class="btn btn-ghost" href="{{ route('trainers.index') }}">إعادة ضبط</a>
            </div>
        </form>
    </x-card>

    <div style="margin-block-start:18px">
        <x-card :flush="true">
            @if ($trainers->isEmpty())
                <x-empty text="لا يوجد مدربون مطابقون لبحثك." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>المدرب</th>
                            <th>النوع</th>
                            <th>جهة التدريب</th>
                            <th>التواصل</th>
                            <th>التخصصات</th>
                            <th>الخبرة</th>
                            <th>سعر الساعة</th>
                            <th>الحالة</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($trainers as $trainer)
                            <tr>
                                <td>
                                    <a href="{{ route('trainers.show', $trainer) }}"><strong>{{ $trainer->full_name }}</strong></a>
                                    <div class="muted" style="font-size:.8rem">
                                        {{ $trainer->national_id ? 'رقم قومي: '.$trainer->national_id : 'بدون رقم قومي' }}
                                        @if ($trainer->branch) · {{ $trainer->branch->name }} @endif
                                    </div>
                                </td>
                                <td>
                                    <x-badge :tone="$trainer->trainer_type?->value === 'internal' ? 'brand' : 'neutral'">
                                        {{ $trainer->trainer_type?->label() ?? '—' }}
                                    </x-badge>
                                </td>
                                <td>{{ $trainer->provider?->name ?? '—' }}</td>
                                <td>
                                    <div>{{ $trainer->phone ?: '—' }}</div>
                                    <div class="muted" style="font-size:.8rem">{{ $trainer->email ?: 'بدون بريد' }}</div>
                                </td>
                                <td>
                                    @if ($trainer->courseCategories->isEmpty())
                                        <span class="muted">—</span>
                                    @else
                                        <div style="display:flex;flex-wrap:wrap;gap:4px">
                                            @foreach ($trainer->courseCategories->take(2) as $category)
                                                <x-badge tone="brand">{{ $category->name }}</x-badge>
                                            @endforeach
                                            @if ($trainer->course_categories_count > 2)
                                                <x-badge>+{{ $trainer->course_categories_count - 2 }}</x-badge>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="num">{{ $trainer->years_experience !== null ? $trainer->years_experience.' سنة' : '—' }}</td>
                                <td class="num">{{ $trainer->hourly_rate !== null ? number_format((float) $trainer->hourly_rate, 2) : '—' }}</td>
                                <td>
                                    @if ($trainer->is_active)
                                        <x-badge tone="ok">نشط</x-badge>
                                    @else
                                        <x-badge>غير نشط</x-badge>
                                    @endif
                                </td>
                                <td class="actions-cell">
                                    <div class="actions" style="justify-content:flex-end">
                                        <a class="btn btn-ghost btn-sm" href="{{ route('trainers.show', $trainer) }}">تفاصيل</a>
                                        @can('TRAINERS_MANAGE')
                                            <a class="btn btn-outline btn-sm" href="{{ route('trainers.edit', $trainer) }}">تعديل</a>
                                            <form method="POST" action="{{ route('trainers.destroy', $trainer) }}"
                                                  data-confirm="هل تريد حذف المدرب «{{ $trainer->full_name }}»؟">
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

                <div style="padding:0 18px 14px">
                    {{ $trainers->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection
