@extends('layouts.app')

@section('title', 'جهات التدريب')

@section('content')
    <x-page-header title="جهات التدريب" subtitle="الجهات والمراكز الخارجية المتعاونة في تنفيذ البرامج التدريبية">
        @can('PROVIDERS_MANAGE')
            <a class="btn" href="{{ route('providers.create') }}">
                <i class="fa-solid fa-plus"></i> جهة تدريب جديدة
            </a>
        @endcan
    </x-page-header>

    <x-card title="بحث وتصفية">
        <form method="GET" action="{{ route('providers.index') }}" class="filter-grid">
            <x-form.input name="q" label="بحث" :value="request('q')" placeholder="الاسم أو مسؤول التواصل أو الهاتف..." />

            <div class="field">
                <label for="is_active">الحالة</label>
                <select id="is_active" name="is_active" class="control">
                    <option value="">الكل</option>
                    <option value="1" @selected(request('is_active') === '1')>نشطة</option>
                    <option value="0" @selected(request('is_active') === '0')>غير نشطة</option>
                </select>
            </div>

            <div class="actions full">
                <button class="btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i> تطبيق</button>
                <a class="btn btn-ghost" href="{{ route('providers.index') }}">إعادة ضبط</a>
            </div>
        </form>
    </x-card>

    <div style="margin-block-start:18px">
        <x-card :flush="true">
            @if ($providers->isEmpty())
                <x-empty text="لا توجد جهات تدريب مطابقة لبحثك." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>جهة التدريب</th>
                            <th>مسؤول التواصل</th>
                            <th>بيانات التواصل</th>
                            <th>المدربون</th>
                            <th>الجلسات</th>
                            <th>الحالة</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($providers as $provider)
                            <tr>
                                <td>
                                    <a href="{{ route('providers.show', $provider) }}"><strong>{{ $provider->name }}</strong></a>
                                    @if ($provider->address)
                                        <div class="muted" style="font-size:.8rem">{{ $provider->address }}</div>
                                    @endif
                                </td>
                                <td>{{ $provider->contact_person ?: '—' }}</td>
                                <td>
                                    <div class="num">{{ $provider->phone ?: '—' }}</div>
                                    <div class="muted" style="font-size:.8rem">{{ $provider->email ?: 'بدون بريد' }}</div>
                                </td>
                                <td>
                                    <x-badge :tone="$provider->trainers_count > 0 ? 'brand' : 'neutral'">
                                        {{ $provider->trainers_count }}
                                    </x-badge>
                                </td>
                                <td>
                                    <x-badge :tone="$provider->course_sessions_count > 0 ? 'ok' : 'neutral'">
                                        {{ $provider->course_sessions_count }}
                                    </x-badge>
                                </td>
                                <td>
                                    @if ($provider->is_active)
                                        <x-badge tone="ok">نشطة</x-badge>
                                    @else
                                        <x-badge>غير نشطة</x-badge>
                                    @endif
                                </td>
                                <td class="actions-cell">
                                    <div class="actions" style="justify-content:flex-end">
                                        <a class="btn btn-ghost btn-sm" href="{{ route('providers.show', $provider) }}">تفاصيل</a>
                                        @can('PROVIDERS_MANAGE')
                                            <a class="btn btn-outline btn-sm" href="{{ route('providers.edit', $provider) }}">تعديل</a>
                                            <form method="POST" action="{{ route('providers.destroy', $provider) }}"
                                                  data-confirm="هل تريد حذف جهة التدريب «{{ $provider->name }}»؟">
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
                    {{ $providers->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection
