@extends('layouts.app')

@section('title', $trainee->full_name)

@section('content')
    <x-page-header :title="$trainee->full_name"
                   :subtitle="($trainee->national_id ? 'رقم قومي: '.$trainee->national_id : 'بدون رقم قومي').($trainee->job_title ? ' · '.$trainee->job_title : '')">
        <a class="btn btn-ghost" href="{{ route('trainees.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع للمتدربين
        </a>
        @can('TRAINEES_MANAGE')
            <a class="btn" href="{{ route('trainees.edit', $trainee) }}">
                <i class="fa-solid fa-pen"></i> تعديل
            </a>
            <form method="POST" action="{{ route('trainees.destroy', $trainee) }}"
                  data-confirm="هل تريد حذف المتدرب «{{ $trainee->full_name }}»؟">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit"><i class="fa-solid fa-trash"></i> حذف</button>
            </form>
        @endcan
    </x-page-header>

    <div class="grid grid-4" style="margin-block-end:18px">
        <x-stat label="جهات التدريب" :value="$trainee->trainee_providers_count" icon="fa-building-columns" />
        <x-stat label="الإنذارات" :value="$trainee->trainee_warnings_count" icon="fa-triangle-exclamation" tone="warn" />
        <x-stat label="الترشيحات" :value="$trainee->nominations_count" icon="fa-user-check" />
        <x-stat label="التسجيلات" :value="$trainee->enrollments_count" icon="fa-clipboard-list" tone="ok" />
    </div>

    <div class="grid grid-2">
        <x-card title="البيانات الأساسية">
            <dl class="details">
                <div>
                    <dt>الحالة</dt>
                    <dd>
                        @if ($trainee->is_active)
                            <x-badge tone="ok">نشط</x-badge>
                        @else
                            <x-badge>غير نشط</x-badge>
                        @endif
                    </dd>
                </div>
                <div><dt>الرقم القومي</dt><dd class="num">{{ $trainee->national_id ?: '—' }}</dd></div>
                <div><dt>النوع</dt><dd>{{ $trainee->gender?->label() ?? '—' }}</dd></div>
                <div><dt>تاريخ الميلاد</dt><dd class="num">{{ $trainee->birth_date?->format('Y-m-d') ?? '—' }}</dd></div>
                <div><dt>الهاتف</dt><dd class="num">{{ $trainee->phone ?: '—' }}</dd></div>
                <div><dt>البريد الإلكتروني</dt><dd>{{ $trainee->email ?: '—' }}</dd></div>
                <div><dt>العنوان</dt><dd>{{ $trainee->address ?: '—' }}</dd></div>
            </dl>
        </x-card>

        <x-card title="المؤهل والعمل">
            <dl class="details">
                <div><dt>المستوى التعليمي</dt><dd>{{ $trainee->educationLevel?->name ?? '—' }}</dd></div>
                <div><dt>المؤهل</dt><dd>{{ $trainee->qualification ?: '—' }}</dd></div>
                <div><dt>سنوات الخبرة</dt><dd class="num">{{ $trainee->years_experience !== null ? $trainee->years_experience.' سنة' : '—' }}</dd></div>
                <div><dt>المسمى الوظيفي</dt><dd>{{ $trainee->job_title ?: '—' }}</dd></div>
                <div><dt>الدرجة الوظيفية</dt><dd>{{ $trainee->jobGrade?->name ?? '—' }}</dd></div>
                <div><dt>تاريخ الإضافة</dt><dd class="num">{{ $trainee->created_at?->format('Y-m-d') ?? '—' }}</dd></div>
            </dl>

            @if ($trainee->user)
                <hr style="border:0;border-block-start:1px solid var(--line);margin-block:14px">
                <dl class="details">
                    <div><dt>حساب المستخدم</dt><dd class="num">{{ $trainee->user->username }}</dd></div>
                    <div><dt>حالة الحساب</dt><dd>{{ $trainee->user->is_active ? 'نشط' : 'غير نشط' }}</dd></div>
                </dl>
            @endif
        </x-card>
    </div>

    <div style="margin-block-start:18px">
        <x-card title="جهات التدريب المرتبطة" :flush="true">
            @if ($trainee->traineeProviders->isEmpty())
                <x-empty text="لا توجد جهات تدريب مرتبطة بهذا المتدرب." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>جهة التدريب</th>
                            <th>المسمى الوظيفي بالجهة</th>
                            <th>من تاريخ</th>
                            <th>إلى تاريخ</th>
                            <th>الارتباط</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($trainee->traineeProviders as $affiliation)
                            <tr>
                                <td>
                                    @if ($affiliation->provider)
                                        <a href="{{ route('providers.show', $affiliation->provider) }}"><strong>{{ $affiliation->provider->name }}</strong></a>
                                    @else
                                        <strong>—</strong>
                                    @endif
                                </td>
                                <td>{{ $affiliation->job_title_at_provider ?: '—' }}</td>
                                <td class="num">{{ $affiliation->from_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="num">{{ $affiliation->to_date?->format('Y-m-d') ?? '—' }}</td>
                                <td>
                                    @if ($affiliation->is_primary)
                                        <x-badge tone="brand">أساسية</x-badge>
                                    @else
                                        <x-badge>ثانوية</x-badge>
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
        <x-card title="الإنذارات والملاحظات" :flush="true">
            @if ($trainee->traineeWarnings->isEmpty())
                <x-empty text="لا توجد إنذارات أو ملاحظات مسجّلة لهذا المتدرب." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>النوع</th>
                            <th>الجلسة</th>
                            <th>الوصف</th>
                            <th>صادر بواسطة</th>
                            <th>تاريخ الإصدار</th>
                            <th>الحالة</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($trainee->traineeWarnings as $warning)
                            <tr>
                                <td>
                                    <x-badge :tone="$warning->warning_type?->tone() ?? 'neutral'">
                                        {{ $warning->warning_type?->label() ?? '—' }}
                                    </x-badge>
                                </td>
                                <td>
                                    @if ($warning->session)
                                        <div class="num">{{ $warning->session->session_code }}</div>
                                        <div class="muted" style="font-size:.8rem">{{ $warning->session->course?->title ?? '—' }}</div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="muted">{{ $warning->description ?: '—' }}</td>
                                <td>{{ $warning->issuedBy?->full_name ?? $warning->issuedBy?->username ?? '—' }}</td>
                                <td class="num">{{ $warning->issued_at?->format('Y-m-d') ?? '—' }}</td>
                                <td>
                                    @if ($warning->is_active)
                                        <x-badge tone="warn">ساري</x-badge>
                                    @else
                                        <x-badge>غير ساري</x-badge>
                                    @endif
                                </td>
                                <td class="actions-cell">
                                    @can('WARNINGS_MANAGE')
                                        <form method="POST" action="{{ route('trainees.warnings.destroy', [$trainee, $warning]) }}"
                                              data-confirm="هل تريد حذف سجل الإنذار؟">
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

    @can('WARNINGS_MANAGE')
        <div style="margin-block-start:18px">
            <x-card title="تسجيل إنذار أو ملاحظة">
                <form method="POST" action="{{ route('trainees.warnings.store', $trainee) }}">
                    @csrf
                    <div class="form-grid">
                        <x-form.select name="warning_type" label="النوع" :options="$warningTypes" required />
                        <x-form.select name="session_id" label="الجلسة المرتبطة" :options="$sessions"
                                       placeholder="بدون جلسة" />
                        <x-form.input name="issued_at" type="date" label="تاريخ الإصدار"
                                      :value="now()->toDateString()" />
                        <x-form.checkbox name="is_active" label="الإنذار ساري" :checked="true" />
                        <x-form.textarea name="description" label="الوصف" wrapper="full" />
                    </div>

                    <div class="actions" style="margin-block-start:12px;justify-content:flex-end">
                        <button type="submit" class="btn">
                            <i class="fa-solid fa-triangle-exclamation"></i> تسجيل
                        </button>
                    </div>
                </form>
            </x-card>
        </div>
    @endcan

    <div style="margin-block-start:18px">
        <x-card title="الترشيحات" :flush="true">
            @if ($trainee->nominations->isEmpty())
                <x-empty text="لا توجد ترشيحات لهذا المتدرب بعد." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>الجلسة</th>
                            <th>الدورة</th>
                            <th>المصدر</th>
                            <th>الحالة</th>
                            <th>تاريخ التقديم</th>
                            <th>الترتيب</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($trainee->nominations as $nomination)
                            <tr>
                                <td class="num">{{ $nomination->session?->session_code ?? '—' }}</td>
                                <td>{{ $nomination->session?->course?->title ?? '—' }}</td>
                                <td>{{ $nomination->source?->label() ?? '—' }}</td>
                                <td><x-badge>{{ $nomination->status?->label() ?? '—' }}</x-badge></td>
                                <td class="num">{{ $nomination->application_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="num">{{ $nomination->rank_no ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>
    </div>

    <div style="margin-block-start:18px">
        <x-card title="التسجيلات" :flush="true">
            @if ($trainee->enrollments->isEmpty())
                <x-empty text="لا توجد تسجيلات لهذا المتدرب بعد." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>الجلسة</th>
                            <th>الدورة</th>
                            <th>الحالة</th>
                            <th>نسبة الحضور</th>
                            <th>الدرجة النهائية</th>
                            <th>النتيجة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($trainee->enrollments as $enrollment)
                            <tr>
                                <td class="num">{{ $enrollment->session?->session_code ?? '—' }}</td>
                                <td>{{ $enrollment->session?->course?->title ?? '—' }}</td>
                                <td><x-badge>{{ $enrollment->status?->label() ?? '—' }}</x-badge></td>
                                <td class="num">{{ $enrollment->attendance_pct !== null ? $enrollment->attendance_pct.'%' : '—' }}</td>
                                <td class="num">{{ $enrollment->final_score_pct !== null ? $enrollment->final_score_pct.'%' : '—' }}</td>
                                <td>
                                    @if ($enrollment->is_passed === null)
                                        —
                                    @elseif ($enrollment->is_passed)
                                        <x-badge tone="ok">ناجح</x-badge>
                                    @else
                                        <x-badge tone="danger">راسب</x-badge>
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
        <x-card title="الفواتير" :flush="true">
            @if ($trainee->invoices->isEmpty())
                <x-empty text="لا توجد فواتير مرتبطة بهذا المتدرب." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>رقم الفاتورة</th>
                            <th>الجلسة</th>
                            <th>الإجمالي</th>
                            <th>الحالة</th>
                            <th>تاريخ الإصدار</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($trainee->invoices as $invoice)
                            <tr>
                                <td class="num">{{ $invoice->invoice_no }}</td>
                                <td>{{ $invoice->session?->session_code ?? '—' }}</td>
                                <td class="num">{{ number_format((float) $invoice->total_amount, 2) }} ج.م</td>
                                <td><x-badge>{{ $invoice->status?->label() ?? '—' }}</x-badge></td>
                                <td class="num">{{ $invoice->issue_date?->format('Y-m-d') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>
    </div>
@endsection
