@extends('layouts.app')

@section('title', 'الدورات')

@section('content')
    <x-page-header title="الدورات" subtitle="كتالوج البرامج التدريبية المعتمدة في المركز">
        @can('COURSES_MANAGE')
            <a class="btn" href="{{ route('courses.create') }}">
                <i class="fa-solid fa-plus"></i> دورة جديدة
            </a>
        @endcan
    </x-page-header>

    <x-card title="بحث وتصفية">
        <form method="GET" action="{{ route('courses.index') }}" class="filter-grid">
            <x-form.input name="q" label="بحث" :value="request('q')" placeholder="كود الدورة أو اسمها..." />
            <x-form.select name="category_id" label="التصنيف" :options="$categories" placeholder="كل التصنيفات"
                           :selected="request('category_id')" />
            <x-form.select name="course_type_id" label="النوع" :options="$types" placeholder="كل الأنواع"
                           :selected="request('course_type_id')" />
            <x-form.select name="level" label="المستوى" :options="$levels" placeholder="كل المستويات"
                           :selected="request('level')" />

            <div class="field">
                <label for="is_active">الحالة</label>
                <select id="is_active" name="is_active" class="control">
                    <option value="">الكل</option>
                    <option value="1" @selected(request('is_active') === '1')>مفعّلة</option>
                    <option value="0" @selected(request('is_active') === '0')>غير مفعّلة</option>
                </select>
            </div>

            <div class="actions full">
                <button class="btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i> تطبيق</button>
                <a class="btn btn-ghost" href="{{ route('courses.index') }}">إعادة ضبط</a>
            </div>
        </form>
    </x-card>

    <div style="margin-block-start:18px">
        <x-card :flush="true">
            @if ($courses->isEmpty())
                <x-empty text="لا توجد دورات مطابقة لبحثك." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>الكود</th>
                            <th>الدورة</th>
                            <th>المستوى</th>
                            <th>الساعات</th>
                            <th>السعر</th>
                            <th>الجلسات</th>
                            <th>الحالة</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($courses as $course)
                            <tr>
                                <td class="num">{{ $course->code }}</td>
                                <td>
                                    <a href="{{ route('courses.show', $course) }}"><strong>{{ $course->title }}</strong></a>
                                    <div class="muted" style="font-size:.8rem">
                                        {{ $course->category?->name ?? 'بدون تصنيف' }} · {{ $course->courseType?->name ?? 'بدون نوع' }}
                                    </div>
                                </td>
                                <td>
                                    @if ($course->level)
                                        <x-badge tone="brand">{{ $course->level->label() }}</x-badge>
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                                <td class="num">{{ $course->total_hours !== null ? (float) $course->total_hours : '—' }}</td>
                                <td class="num">{{ $course->price !== null ? number_format((float) $course->price, 2) : '—' }}</td>
                                <td>
                                    <x-badge :tone="$course->course_sessions_count > 0 ? 'ok' : 'neutral'">
                                        {{ $course->course_sessions_count }}
                                    </x-badge>
                                </td>
                                <td>
                                    @if ($course->is_active)
                                        <x-badge tone="ok">مفعّلة</x-badge>
                                    @else
                                        <x-badge>غير مفعّلة</x-badge>
                                    @endif
                                </td>
                                <td class="actions-cell">
                                    <div class="actions" style="justify-content:flex-end">
                                        <a class="btn btn-ghost btn-sm" href="{{ route('courses.show', $course) }}">تفاصيل</a>
                                        @can('COURSES_MANAGE')
                                            <a class="btn btn-outline btn-sm" href="{{ route('courses.edit', $course) }}">تعديل</a>
                                            <form method="POST" action="{{ route('courses.destroy', $course) }}"
                                                  data-confirm="هل تريد حذف الدورة «{{ $course->title }}»؟">
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
                    {{ $courses->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection
