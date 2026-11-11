@extends('layouts.app')

@section('title', $branch->name)

@section('content')
    <x-page-header :title="$branch->name" :subtitle="'فرع · '.$branch->code">
        <a class="btn btn-ghost" href="{{ route('branches.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع للفروع
        </a>
        <a class="btn" href="{{ route('branches.edit', $branch) }}">
            <i class="fa-solid fa-pen"></i> تعديل
        </a>
        <form method="POST" action="{{ route('branches.destroy', $branch) }}"
              data-confirm="هل تريد حذف الفرع «{{ $branch->name }}»؟">
            @csrf
            @method('DELETE')
            <button class="btn btn-danger" type="submit"><i class="fa-solid fa-trash"></i> حذف</button>
        </form>
    </x-page-header>

    <div class="grid grid-4" style="margin-block-end:18px">
        <x-stat label="المستخدمون" :value="$branch->users_count" icon="fa-user-gear" />
        <x-stat label="المدربون" :value="$branch->trainers_count" icon="fa-chalkboard-user" />
        <x-stat label="القاعات" :value="$branch->halls_count" icon="fa-door-open" />
        <x-stat label="الجلسات" :value="$branch->course_sessions_count" icon="fa-calendar-days" tone="ok" />
    </div>

    <div class="grid grid-2">
        <x-card title="بيانات الفرع">
            <dl class="details">
                <div><dt>الكود</dt><dd class="num">{{ $branch->code }}</dd></div>
                <div>
                    <dt>الحالة</dt>
                    <dd>
                        @if ($branch->is_active)
                            <x-badge tone="ok">نشط</x-badge>
                        @else
                            <x-badge>غير نشط</x-badge>
                        @endif
                    </dd>
                </div>
                <div><dt>المدينة</dt><dd>{{ $branch->city ?: '—' }}</dd></div>
                <div><dt>الهاتف</dt><dd class="num">{{ $branch->phone ?: '—' }}</dd></div>
                <div><dt>العنوان</dt><dd>{{ $branch->address ?: '—' }}</dd></div>
                <div><dt>تاريخ الإنشاء</dt><dd class="num">{{ $branch->created_at?->format('Y-m-d') ?? '—' }}</dd></div>
            </dl>
        </x-card>

        <x-card title="ربط اعتماد جديد">
            @if ($accreditations->isEmpty())
                <x-alert type="info">
                    لا توجد اعتمادات معرفة. أضفها من صفحة
                    <a href="{{ route('accreditations.index') }}">الاعتمادات</a>.
                </x-alert>
            @else
                <form method="POST" action="{{ route('branches.accreditations.store', $branch) }}">
                    @csrf
                    <div class="form-grid">
                        <x-form.select name="accreditation_id" label="الاعتماد" :options="$accreditations" required />
                        <x-form.input name="issue_date" type="date" label="تاريخ الإصدار" />
                        <x-form.input name="expiry_date" type="date" label="تاريخ الانتهاء" />
                        <x-form.checkbox name="is_active" label="الاعتماد ساري" :checked="true" />
                    </div>
                    <div class="actions" style="margin-block-start:14px;justify-content:flex-end">
                        <button type="submit" class="btn"><i class="fa-solid fa-link"></i> ربط الاعتماد</button>
                    </div>
                </form>
            @endif
        </x-card>
    </div>

    <div style="margin-block-start:18px">
        <x-card title="اعتمادات الفرع" :flush="true">
            @if ($branch->branchAccreditations->isEmpty())
                <x-empty text="لا توجد اعتمادات مرتبطة بهذا الفرع." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>الاعتماد</th>
                            <th>الجهة المانحة</th>
                            <th>تاريخ الإصدار</th>
                            <th>تاريخ الانتهاء</th>
                            <th>الحالة</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($branch->branchAccreditations as $link)
                            <tr>
                                <td><strong>{{ $link->accreditation?->name ?? '—' }}</strong></td>
                                <td>{{ $link->accreditation?->issuing_body ?: '—' }}</td>
                                <td class="num">{{ $link->issue_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="num">{{ $link->expiry_date?->format('Y-m-d') ?? '—' }}</td>
                                <td>
                                    @if ($link->is_active)
                                        <x-badge tone="ok">ساري</x-badge>
                                    @else
                                        <x-badge>غير ساري</x-badge>
                                    @endif
                                </td>
                                <td class="actions-cell">
                                    <div class="actions" style="justify-content:flex-end">
                                        <details>
                                            <summary class="btn btn-outline btn-sm" style="cursor:pointer">تعديل</summary>
                                            <form method="POST"
                                                  action="{{ route('branches.accreditations.update', [$branch, $link]) }}"
                                                  style="margin-block-start:10px;min-width:260px">
                                                @csrf
                                                @method('PATCH')
                                                <div class="form-grid">
                                                    <input type="hidden" name="accreditation_id" value="{{ $link->accreditation_id }}">
                                                    <x-form.input name="issue_date" type="date" label="الإصدار"
                                                                  :value="$link->issue_date?->format('Y-m-d')" />
                                                    <x-form.input name="expiry_date" type="date" label="الانتهاء"
                                                                  :value="$link->expiry_date?->format('Y-m-d')" />
                                                    <x-form.checkbox name="is_active" label="ساري" :checked="$link->is_active" />
                                                </div>
                                                <button type="submit" class="btn btn-sm" style="margin-block-start:8px">حفظ</button>
                                            </form>
                                        </details>
                                        <form method="POST"
                                              action="{{ route('branches.accreditations.destroy', [$branch, $link]) }}"
                                              data-confirm="هل تريد إزالة هذا الاعتماد من الفرع؟">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger btn-sm" type="submit">إزالة</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>
    </div>

    <div style="margin-block-start:18px">
        <x-card title="جلسات الفرع" :flush="true">
            @if ($branch->courseSessions->isEmpty())
                <x-empty text="لا توجد جلسات مجدولة في هذا الفرع." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>كود الجلسة</th>
                            <th>الدورة</th>
                            <th>من</th>
                            <th>إلى</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($branch->courseSessions as $session)
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
