@extends('layouts.app')

@section('title', 'سجل التدقيق')

@php
    $actionLabels = config('audit.actions', []);
@endphp

@section('content')
    <x-page-header title="سجل التدقيق" subtitle="سجل التغييرات الإدارية على المستخدمين والأدوار والفروع والاعتمادات" />

    <x-card title="بحث وتصفية">
        <form method="GET" action="{{ route('audit.index') }}" class="filter-grid">
            <x-form.input name="q" label="بحث" :value="request('q')" placeholder="نوع العملية أو الكيان..." />
            <x-form.select name="user_id" label="المستخدم" :options="$users" placeholder="كل المستخدمين"
                           :selected="request('user_id')" />
            <x-form.select name="entity_type" label="الكيان"
                           :options="$entityTypes->mapWithKeys(fn ($type) => [$type => $entityLabels[$type] ?? class_basename($type)])"
                           placeholder="كل الكيانات" :selected="request('entity_type')" />
            <x-form.input name="date_from" type="date" label="من تاريخ" :value="request('date_from')" />
            <x-form.input name="date_to" type="date" label="إلى تاريخ" :value="request('date_to')" />
            <div class="actions full">
                <button class="btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i> تطبيق</button>
                <a class="btn btn-ghost" href="{{ route('audit.index') }}">إعادة ضبط</a>
            </div>
        </form>
    </x-card>

    <div style="margin-block-start:18px">
        <x-card :flush="true">
            @if ($logs->isEmpty())
                <x-empty text="لا توجد عمليات مسجلة مطابقة لبحثك." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>المستخدم</th>
                            <th>العملية</th>
                            <th>الكيان</th>
                            <th>التغييرات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td class="num">{{ $log->created_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
                                <td>
                                    @if ($log->user)
                                        <a href="{{ route('users.show', $log->user) }}">{{ $log->user->full_name ?: $log->user->username }}</a>
                                    @else
                                        <span class="muted">النظام</span>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $actionLabels[$log->action] ?? ($log->action ?: '—') }}</div>
                                    <div class="muted num" style="font-size:.75rem">{{ $log->action }}</div>
                                </td>
                                <td>
                                    @if ($log->entity_type)
                                        {{ $entityLabels[$log->entity_type] ?? class_basename($log->entity_type) }}
                                        <span class="muted num" style="font-size:.75rem">#{{ $log->entity_id }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if ($log->old_value || $log->new_value)
                                        <details>
                                            <summary style="cursor:pointer">عرض التفاصيل</summary>
                                            @if ($log->old_value)
                                                <div style="margin-block-start:8px">
                                                    <strong style="font-size:.8rem">قبل</strong>
                                                    <pre class="num" style="white-space:pre-wrap;font-size:.72rem;margin:4px 0">{{ json_encode($log->old_value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                                                </div>
                                            @endif
                                            @if ($log->new_value)
                                                <div style="margin-block-start:8px">
                                                    <strong style="font-size:.8rem">بعد</strong>
                                                    <pre class="num" style="white-space:pre-wrap;font-size:.72rem;margin:4px 0">{{ json_encode($log->new_value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                                                </div>
                                            @endif
                                        </details>
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>

                <div style="padding:0 18px 14px">{{ $logs->links() }}</div>
            @endif
        </x-card>
    </div>
@endsection
