@extends('layouts.app')

@section('title', 'الجلسات المجدولة')

@section('content')
    <x-page-header title="الجلسات المجدولة" subtitle="جدولة تنفيذ الدورات وتوزيع الأيام على القاعات والمدربين">
        @can('SESSIONS_MANAGE')
            <a class="btn" href="{{ route('sessions.create') }}">
                <i class="fa-solid fa-plus"></i> جلسة جديدة
            </a>
        @endcan
    </x-page-header>

    <x-card title="بحث وتصفية">
        <form method="GET" action="{{ route('sessions.index') }}" class="filter-grid">
            <x-form.input name="q" label="بحث" :value="request('q')" placeholder="كود الجلسة أو الدورة..." />
            <x-form.select name="course_id" label="الدورة" :options="$courses" placeholder="كل الدورات"
                           :selected="request('course_id')" />
            <x-form.select name="branch_id" label="الفرع" :options="$branches" placeholder="كل الفروع"
                           :selected="request('branch_id')" />
            <x-form.select name="provider_id" label="جهة التدريب" :options="$providers" placeholder="كل الجهات"
                           :selected="request('provider_id')" />
            <x-form.select name="status" label="الحالة" :options="$statuses" placeholder="كل الحالات"
                           :selected="request('status')" />
            <x-form.select name="delivery_mode" label="طريقة التنفيذ" :options="$deliveryModes" placeholder="الكل"
                           :selected="request('delivery_mode')" />

            <div class="actions full">
                <button class="btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i> تطبيق</button>
                <a class="btn btn-ghost" href="{{ route('sessions.index') }}">إعادة ضبط</a>
            </div>
        </form>
    </x-card>

    <div style="margin-block-start:18px">
        <x-card :flush="true">
            @if ($sessions->isEmpty())
                <x-empty text="لا توجد جلسات مطابقة لبحثك." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>الجلسة</th>
                            <th>الفترة</th>
                            <th>التنفيذ</th>
                            <th>الفرع / الجهة</th>
                            <th>الأيام</th>
                            <th>التسجيلات</th>
                            <th>الحالة</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sessions as $session)
                            <tr>
                                <td>
                                    <a href="{{ route('sessions.show', $session) }}"><strong>{{ $session->session_code }}</strong></a>
                                    <div class="muted" style="font-size:.8rem">{{ $session->course?->title ?? 'دورة محذوفة' }}</div>
                                </td>
                                <td class="num">
                                    {{ $session->start_date?->format('Y-m-d') ?? '—' }}
                                    <span class="muted">—</span>
                                    {{ $session->end_date?->format('Y-m-d') ?? '—' }}
                                </td>
                                <td>{{ $session->delivery_mode?->label() ?? '—' }}</td>
                                <td>
                                    {{ $session->branch?->name ?? '—' }}
                                    @if ($session->provider)
                                        <div class="muted" style="font-size:.8rem">{{ $session->provider->name }}</div>
                                    @endif
                                </td>
                                <td>
                                    <x-badge :tone="$session->session_days_count > 0 ? 'brand' : 'neutral'">
                                        {{ $session->session_days_count }}
                                    </x-badge>
                                </td>
                                <td>
                                    <x-badge :tone="$session->enrollments_count > 0 ? 'ok' : 'neutral'">
                                        {{ $session->enrollments_count }}
                                    </x-badge>
                                </td>
                                <td>
                                    <x-badge :tone="$session->status->tone()">{{ $session->status->label() }}</x-badge>
                                </td>
                                <td class="actions-cell">
                                    <div class="actions" style="justify-content:flex-end">
                                        <a class="btn btn-ghost btn-sm" href="{{ route('sessions.show', $session) }}">تفاصيل</a>
                                        @can('SESSIONS_MANAGE')
                                            <a class="btn btn-outline btn-sm" href="{{ route('sessions.edit', $session) }}">تعديل</a>
                                            <form method="POST" action="{{ route('sessions.destroy', $session) }}"
                                                  data-confirm="هل تريد حذف الجلسة «{{ $session->session_code }}»؟">
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
                    {{ $sessions->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection
