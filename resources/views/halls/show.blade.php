@extends('layouts.app')

@section('title', $hall->name)

@section('content')
    <x-page-header :title="$hall->name" :subtitle="'كود القاعة: '.$hall->code.' · '.($hall->branch?->name ?? 'بدون فرع')">
        <a class="btn btn-ghost" href="{{ route('halls.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع للقاعات
        </a>
        @can('HALLS_MANAGE')
            <a class="btn" href="{{ route('halls.edit', $hall) }}">
                <i class="fa-solid fa-pen"></i> تعديل
            </a>
            <form method="POST" action="{{ route('halls.destroy', $hall) }}"
                  data-confirm="هل تريد حذف القاعة «{{ $hall->name }}»؟">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit"><i class="fa-solid fa-trash"></i> حذف</button>
            </form>
        @endcan
    </x-page-header>

    <div class="grid grid-4" style="margin-block-end:18px">
        <x-stat label="السعة" :value="$hall->capacity" icon="fa-users" />
        <x-stat label="التجهيزات" :value="$hall->equipment->count()" icon="fa-toolbox" />
        <x-stat label="فترات الحجب" :value="$hall->hallBlocks->count()" icon="fa-ban" tone="ok" />
        <x-stat label="أيام الجلسات" :value="$hall->sessionDays->count()" icon="fa-calendar-days" />
    </div>

    <div class="grid grid-2">
        <x-card title="بيانات القاعة">
            <dl class="details">
                <div>
                    <dt>الحالة</dt>
                    <dd>
                        @if ($hall->is_active)
                            <x-badge tone="ok">نشطة</x-badge>
                        @else
                            <x-badge>غير نشطة</x-badge>
                        @endif
                    </dd>
                </div>
                <div><dt>الكود</dt><dd class="num">{{ $hall->code }}</dd></div>
                <div><dt>الفرع</dt><dd>{{ $hall->branch?->name ?? '—' }}</dd></div>
                <div><dt>السعة</dt><dd class="num">{{ $hall->capacity }} متدربًا</dd></div>
                <div><dt>الدور</dt><dd>{{ $hall->floor ?: '—' }}</dd></div>
                <div><dt>وصف الموقع</dt><dd>{{ $hall->location_description ?: '—' }}</dd></div>
                <div><dt>تاريخ الإضافة</dt><dd class="num">{{ $hall->created_at?->format('Y-m-d') ?? '—' }}</dd></div>
            </dl>
        </x-card>

        <x-card title="التجهيزات" :flush="true">
            @if ($hall->equipment->isEmpty())
                <x-empty text="لم تُضف تجهيزات لهذه القاعة." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>التجهيز</th>
                            <th>الكمية</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($hall->equipment as $item)
                            <tr>
                                <td>{{ $item->name }}</td>
                                <td class="num">{{ $item->pivot->quantity }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>
    </div>

    <div style="margin-block-start:18px">
        <x-card title="حجب إتاحة القاعة">
            @can('HALL_BLOCK')
                <form method="POST" action="{{ route('halls.blocks.store', $hall) }}">
                    @csrf
                    <div class="form-grid">
                        <x-form.input name="start_at" type="datetime-local" label="من" required />
                        <x-form.input name="end_at" type="datetime-local" label="إلى" required />
                        <x-form.select name="reason" label="السبب" :options="$blockReasons" placeholder="بدون سبب" />
                        <x-form.input name="notes" label="ملاحظات" wrapper="full" />
                    </div>

                    <div class="actions" style="margin-block-start:12px;justify-content:flex-end">
                        <button type="submit" class="btn">
                            <i class="fa-solid fa-ban"></i> حجب الإتاحة
                        </button>
                    </div>
                </form>

                @if ($hall->hallBlocks->isNotEmpty())
                    <hr style="border:0;border-block-start:1px solid var(--line);margin-block:18px">
                @endif
            @endcan

            @if ($hall->hallBlocks->isEmpty())
                <p class="muted" style="margin:0">لا توجد فترات حجب إتاحة مسجّلة لهذه القاعة.</p>
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>من</th>
                            <th>إلى</th>
                            <th>السبب</th>
                            <th>ملاحظات</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($hall->hallBlocks as $block)
                            <tr>
                                <td class="num">{{ $block->start_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td class="num">{{ $block->end_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td>{{ $block->reason?->label() ?? '—' }}</td>
                                <td class="muted">{{ $block->notes ?: '—' }}</td>
                                <td class="actions-cell">
                                    @can('HALL_BLOCK')
                                        <form method="POST" action="{{ route('halls.blocks.destroy', [$hall, $block]) }}"
                                              data-confirm="هل تريد حذف فترة حجب الإتاحة؟">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger btn-sm" type="submit">حذف</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>
    </div>

    <div style="margin-block-start:18px">
        <x-card title="أيام الجلسات في القاعة" :flush="true">
            @if ($hall->sessionDays->isEmpty())
                <x-empty text="لا توجد أيام جلسات مجدولة في هذه القاعة." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>من</th>
                            <th>إلى</th>
                            <th>الجلسة</th>
                            <th>الدورة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($hall->sessionDays as $sessionDay)
                            <tr>
                                <td class="num">{{ $sessionDay->day_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="num">{{ substr((string) $sessionDay->start_time, 0, 5) }}</td>
                                <td class="num">{{ substr((string) $sessionDay->end_time, 0, 5) }}</td>
                                <td class="num">{{ $sessionDay->session?->session_code ?? '—' }}</td>
                                <td>{{ $sessionDay->session?->course?->title ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>
    </div>
@endsection
