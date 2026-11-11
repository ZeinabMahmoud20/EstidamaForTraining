@extends('layouts.app')

@section('title', $session->session_code)

@section('content')
    <x-page-header :title="$session->session_code" :subtitle="($session->course?->title ?? 'دورة محذوفة').' · '.($session->start_date?->format('Y-m-d') ?? '—').' إلى '.($session->end_date?->format('Y-m-d') ?? '—')">
        <a class="btn btn-ghost" href="{{ route('sessions.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع للجلسات
        </a>
        @can('SESSIONS_MANAGE')
            <a class="btn" href="{{ route('sessions.edit', $session) }}">
                <i class="fa-solid fa-pen"></i> تعديل
            </a>
            <form method="POST" action="{{ route('sessions.destroy', $session) }}"
                  data-confirm="هل تريد حذف الجلسة «{{ $session->session_code }}»؟">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit"><i class="fa-solid fa-trash"></i> حذف</button>
            </form>
        @endcan
    </x-page-header>

    <div class="grid grid-4" style="margin-block-end:18px">
        <x-stat label="أيام الجلسة" :value="$session->sessionDays->count()" icon="fa-calendar-days" />
        <x-stat label="المدربون" :value="$session->sessionTrainers->count()" icon="fa-chalkboard-user" />
        <x-stat label="التسجيلات" :value="$session->enrollments_count" icon="fa-clipboard-list" tone="ok" />
        <x-stat label="الترشيحات" :value="$session->nominations_count" icon="fa-user-check" />
    </div>

    <div class="grid grid-2">
        <x-card title="بيانات الجلسة">
            <dl class="details">
                <div><dt>الكود</dt><dd class="num">{{ $session->session_code }}</dd></div>
                <div><dt>الدورة</dt><dd>{{ $session->course?->title ?? '—' }}</dd></div>
                <div><dt>جهة التدريب</dt><dd>{{ $session->provider?->name ?? '—' }}</dd></div>
                <div><dt>الفرع</dt><dd>{{ $session->branch?->name ?? '—' }}</dd></div>
                <div><dt>الاعتماد</dt><dd>{{ $session->accreditation?->name ?? '—' }}</dd></div>
                <div><dt>طريقة التنفيذ</dt><dd>{{ $session->delivery_mode?->label() ?? '—' }}</dd></div>
                <div><dt>تاريخ البداية</dt><dd class="num">{{ $session->start_date?->format('Y-m-d') ?? '—' }}</dd></div>
                <div><dt>تاريخ النهاية</dt><dd class="num">{{ $session->end_date?->format('Y-m-d') ?? '—' }}</dd></div>
                <div><dt>بداية التسجيل</dt><dd class="num">{{ $session->registration_open?->format('Y-m-d') ?? '—' }}</dd></div>
                <div><dt>نهاية التسجيل</dt><dd class="num">{{ $session->registration_close?->format('Y-m-d') ?? '—' }}</dd></div>
                <div><dt>الحد الأقصى للمتدربين</dt><dd class="num">{{ $session->max_trainees ?? '—' }}</dd></div>
                <div><dt>السعر</dt><dd class="num">{{ $session->price !== null ? number_format((float) $session->price, 2).' ج.م' : '—' }}</dd></div>
                <div><dt>أنشئت بواسطة</dt><dd>{{ $session->createdBy?->full_name ?? '—' }}</dd></div>
                <div><dt>تاريخ الإنشاء</dt><dd class="num">{{ $session->created_at?->format('Y-m-d') ?? '—' }}</dd></div>
            </dl>

            @if ($session->online_url)
                <div class="field" style="margin-block-start:14px">
                    <label>رابط الجلسة</label>
                    <a href="{{ $session->online_url }}" target="_blank" rel="noopener">{{ $session->online_url }}</a>
                </div>
            @endif
        </x-card>

        <x-card title="حالة الجلسة">
            <div class="field">
                <label>الحالة الحالية</label>
                <x-badge :tone="$session->status->tone()">{{ $session->status->label() }}</x-badge>
            </div>

            @can('SESSIONS_PUBLISH')
                @if (! empty($nextStatuses))
                    <form method="POST" action="{{ route('sessions.status.update', $session) }}">
                        @csrf
                        @method('PATCH')
                        <div class="form-grid">
                            <x-form.select name="status" label="الحالة الجديدة" :options="$nextStatuses"
                                           :placeholder="false" required />
                        </div>
                        <div class="actions" style="margin-block-start:12px">
                            <button type="submit" class="btn">
                                <i class="fa-solid fa-arrows-rotate"></i> تحديث الحالة
                            </button>
                        </div>
                    </form>
                @else
                    <p class="muted" style="margin-block-end:0">لا توجد انتقالات حالة متاحة لهذه الجلسة.</p>
                @endif
            @else
                <p class="muted" style="margin-block-end:0">تغيير حالة الجلسة يتطلب صلاحية نشر الجلسات.</p>
            @endcan
        </x-card>
    </div>

    <div style="margin-block-start:18px">
        <x-card title="أيام الجلسة" :flush="true">
            @if ($session->sessionDays->isEmpty())
                <x-empty text="لم تُضف أيام لهذه الجلسة بعد." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>التاريخ</th>
                            <th>من</th>
                            <th>إلى</th>
                            <th>القاعة</th>
                            <th>رابط اليوم</th>
                            <th>آخر تغيير قاعة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($session->sessionDays as $day)
                            @php
                                $latestChange = $day->hallChangeLog->sortByDesc('changed_at')->first();
                            @endphp
                            <tr>
                                <td class="num">{{ $day->day_no }}</td>
                                <td class="num">{{ $day->day_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="num">{{ substr((string) $day->start_time, 0, 5) }}</td>
                                <td class="num">{{ substr((string) $day->end_time, 0, 5) }}</td>
                                <td>
                                    {{ $day->hall?->name ?? 'بدون قاعة' }}
                                    @if ($day->hall?->branch)
                                        <div class="muted" style="font-size:.8rem">{{ $day->hall->branch->name }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if ($day->online_url)
                                        <a href="{{ $day->online_url }}" target="_blank" rel="noopener">فتح الرابط</a>
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                                <td class="muted">
                                    @if ($latestChange)
                                        {{ $latestChange->newHall?->name ?? 'بدون قاعة' }}
                                        <div style="font-size:.78rem">{{ $latestChange->changed_at?->format('Y-m-d H:i') }}</div>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>
    </div>

    @can('CHANGE_HALL')
        <div style="margin-block-start:18px">
            <x-card title="تغيير قاعة يوم">
                @if ($session->sessionDays->isEmpty())
                    <p class="muted" style="margin:0">أضف أيامًا للجلسة أولًا حتى يمكن تغيير قاعتها.</p>
                @else
                    @php
                        $dayOptions = $session->sessionDays->mapWithKeys(fn ($day) => [
                            $day->id => 'اليوم '.$day->day_no.' — '.$day->day_date?->format('Y-m-d')
                                .' — '.($day->hall?->name ?? 'بدون قاعة'),
                        ])->all();
                    @endphp

                    <form method="POST" action="{{ route('sessions.hall.update', $session) }}">
                        @csrf
                        @method('PATCH')
                        <div class="form-grid">
                            <x-form.select name="day_id" label="اليوم" :options="$dayOptions" :placeholder="false" required />
                            <x-form.select name="hall_id" label="القاعة الجديدة" :options="$hallOptions"
                                           placeholder="بدون قاعة" />
                            <x-form.input name="reason" label="سبب التغيير" wrapper="full"
                                          placeholder="مثال: تعارض طارئ أو صيانة..." />
                        </div>

                        <div class="actions" style="margin-block-start:12px;justify-content:flex-end">
                            <button type="submit" class="btn">
                                <i class="fa-solid fa-right-left"></i> تغيير القاعة
                            </button>
                        </div>
                    </form>
                @endif
            </x-card>
        </div>
    @endcan

    <div style="margin-block-start:18px">
        <x-card title="سجل تغيير القاعات" :flush="true">
            @php
                $changeLogs = $session->sessionDays
                    ->flatMap(fn ($day) => $day->hallChangeLog->map(fn ($log) => ['day' => $day, 'log' => $log]))
                    ->sortByDesc(fn ($row) => $row['log']->changed_at);
            @endphp

            @if ($changeLogs->isEmpty())
                <x-empty text="لا توجد تغييرات قاعات مسجّلة لهذه الجلسة." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>اليوم</th>
                            <th>من قاعة</th>
                            <th>إلى قاعة</th>
                            <th>السبب</th>
                            <th>بواسطة</th>
                            <th>وقت التغيير</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($changeLogs as $row)
                            <tr>
                                <td class="num">اليوم {{ $row['day']->day_no }} — {{ $row['day']->day_date?->format('Y-m-d') }}</td>
                                <td>{{ $row['log']->oldHall?->name ?? 'بدون قاعة' }}</td>
                                <td>{{ $row['log']->newHall?->name ?? 'بدون قاعة' }}</td>
                                <td class="muted">{{ $row['log']->reason ?: '—' }}</td>
                                <td>{{ $row['log']->changedBy?->full_name ?? '—' }}</td>
                                <td class="num">{{ $row['log']->changed_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>
    </div>

    <div style="margin-block-start:18px">
        <x-card title="المدربون المخططون" :flush="true">
            @if ($session->sessionTrainers->isEmpty())
                <x-empty text="لم يُحدد مدربون لهذه الجلسة بعد." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>المدرب</th>
                            <th>النوع</th>
                            <th>جهة التدريب</th>
                            <th>الدور</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($session->sessionTrainers as $sessionTrainer)
                            <tr>
                                <td><strong>{{ $sessionTrainer->trainer?->full_name ?? 'مدرب محذوف' }}</strong></td>
                                <td>{{ $sessionTrainer->trainer?->trainer_type?->label() ?? '—' }}</td>
                                <td>{{ $sessionTrainer->trainer?->provider?->name ?? '—' }}</td>
                                <td>{{ $sessionTrainer->planned_role?->label() ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>
    </div>
@endsection
