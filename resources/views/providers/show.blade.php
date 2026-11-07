@extends('layouts.app')

@section('title', $provider->name)

@section('content')
    <x-page-header :title="$provider->name" subtitle="جهة تدريب خارجية">
        <a class="btn btn-ghost" href="{{ route('providers.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع لجهات التدريب
        </a>
        @can('PROVIDERS_MANAGE')
            <a class="btn" href="{{ route('providers.edit', $provider) }}">
                <i class="fa-solid fa-pen"></i> تعديل
            </a>
            <form method="POST" action="{{ route('providers.destroy', $provider) }}"
                  data-confirm="هل تريد حذف جهة التدريب «{{ $provider->name }}»؟">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit"><i class="fa-solid fa-trash"></i> حذف</button>
            </form>
        @endcan
    </x-page-header>

    <div class="grid grid-4" style="margin-block-end:18px">
        <x-stat label="المدربون" :value="$provider->trainers->count()" icon="fa-chalkboard-user" />
        <x-stat label="الجلسات" :value="$provider->courseSessions->count()" icon="fa-calendar-days" />
        <x-stat label="المتدربون المرتبطون" :value="$provider->trainee_providers_count" icon="fa-users" tone="ok" />
        <x-stat label="الفواتير" :value="$provider->invoices_count" icon="fa-file-invoice-dollar" />
    </div>

    <div class="grid grid-2">
        <x-card title="البيانات الأساسية">
            <dl class="details">
                <div>
                    <dt>الحالة</dt>
                    <dd>
                        @if ($provider->is_active)
                            <x-badge tone="ok">نشطة</x-badge>
                        @else
                            <x-badge>غير نشطة</x-badge>
                        @endif
                    </dd>
                </div>
                <div><dt>مسؤول التواصل</dt><dd>{{ $provider->contact_person ?: '—' }}</dd></div>
                <div><dt>الهاتف</dt><dd class="num">{{ $provider->phone ?: '—' }}</dd></div>
                <div><dt>البريد الإلكتروني</dt><dd>{{ $provider->email ?: '—' }}</dd></div>
                <div><dt>العنوان</dt><dd>{{ $provider->address ?: '—' }}</dd></div>
                <div><dt>تاريخ الإضافة</dt><dd class="num">{{ $provider->created_at?->format('Y-m-d') ?? '—' }}</dd></div>
            </dl>
        </x-card>

        <x-card title="ملخص الارتباطات">
            <dl class="details">
                <div><dt>عدد المدربين</dt><dd class="num">{{ $provider->trainers->count() }}</dd></div>
                <div><dt>عدد الجلسات</dt><dd class="num">{{ $provider->courseSessions->count() }}</dd></div>
                <div><dt>عدد المتدربين المرتبطين</dt><dd class="num">{{ $provider->trainee_providers_count }}</dd></div>
                <div><dt>عدد الترشيحات</dt><dd class="num">{{ $provider->nominations_count }}</dd></div>
                <div><dt>عدد الفواتير</dt><dd class="num">{{ $provider->invoices_count }}</dd></div>
            </dl>
        </x-card>
    </div>

    <div style="margin-block-start:18px">
        <x-card title="مدربو الجهة" :flush="true">
            @if ($provider->trainers->isEmpty())
                <x-empty text="لا يوجد مدربون مرتبطون بهذه الجهة." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>المدرب</th>
                            <th>النوع</th>
                            <th>الهاتف</th>
                            <th>البريد الإلكتروني</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($provider->trainers as $trainer)
                            <tr>
                                <td>
                                    <a href="{{ route('trainers.show', $trainer) }}"><strong>{{ $trainer->full_name }}</strong></a>
                                </td>
                                <td>{{ $trainer->trainer_type?->label() ?? '—' }}</td>
                                <td class="num">{{ $trainer->phone ?: '—' }}</td>
                                <td>{{ $trainer->email ?: '—' }}</td>
                                <td>
                                    @if ($trainer->is_active)
                                        <x-badge tone="ok">نشط</x-badge>
                                    @else
                                        <x-badge>غير نشط</x-badge>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>
    </div>

    <div style="margin-block-start:18px">
        <x-card title="جلسات الجهة" :flush="true">
            @if ($provider->courseSessions->isEmpty())
                <x-empty text="لا توجد جلسات مرتبطة بهذه الجهة." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>كود الجلسة</th>
                            <th>الدورة</th>
                            <th>من تاريخ</th>
                            <th>إلى تاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($provider->courseSessions as $session)
                            <tr>
                                <td class="num">{{ $session->session_code }}</td>
                                <td>{{ $session->course?->title ?? '—' }}</td>
                                <td class="num">{{ $session->start_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="num">{{ $session->end_date?->format('Y-m-d') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>
    </div>
@endsection
