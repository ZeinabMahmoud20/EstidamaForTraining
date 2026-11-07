@extends('layouts.app')

@section('title', $trainer->full_name)

@section('content')
    <x-page-header :title="$trainer->full_name" :subtitle="($trainer->trainer_type?->label() ?? 'مدرب').' · '.($trainer->provider?->name ?? 'بدون جهة تدريب')">
        <a class="btn btn-ghost" href="{{ route('trainers.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع للمدربين
        </a>
        @can('TRAINERS_MANAGE')
            <a class="btn" href="{{ route('trainers.edit', $trainer) }}">
                <i class="fa-solid fa-pen"></i> تعديل
            </a>
            <form method="POST" action="{{ route('trainers.destroy', $trainer) }}"
                  data-confirm="هل تريد حذف المدرب «{{ $trainer->full_name }}»؟">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit"><i class="fa-solid fa-trash"></i> حذف</button>
            </form>
        @endcan
    </x-page-header>

    <div class="grid grid-4" style="margin-block-end:18px">
        <x-stat label="الجلسات المخططة" :value="$trainer->session_trainers_count" icon="fa-calendar-days" />
        <x-stat label="التخصصات" :value="$trainer->course_categories_count" icon="fa-tags" />
        <x-stat label="الشهادات" :value="$trainer->trainer_certifications_count" icon="fa-certificate" tone="ok" />
        <x-stat label="سنوات الخبرة" :value="$trainer->years_experience ?? '—'" icon="fa-briefcase" />
    </div>

    <div class="grid grid-2">
        <x-card title="البيانات الأساسية">
            <dl class="details">
                <div>
                    <dt>الحالة</dt>
                    <dd>
                        @if ($trainer->is_active)
                            <x-badge tone="ok">نشط</x-badge>
                        @else
                            <x-badge>غير نشط</x-badge>
                        @endif
                    </dd>
                </div>
                <div><dt>نوع المدرب</dt><dd>{{ $trainer->trainer_type?->label() ?? '—' }}</dd></div>
                <div><dt>جهة التدريب</dt><dd>{{ $trainer->provider?->name ?? '—' }}</dd></div>
                <div><dt>الفرع</dt><dd>{{ $trainer->branch?->name ?? '—' }}</dd></div>
                <div><dt>الرقم القومي</dt><dd class="num">{{ $trainer->national_id ?: '—' }}</dd></div>
                <div><dt>النوع</dt><dd>{{ $trainer->gender?->label() ?? '—' }}</dd></div>
                <div><dt>تاريخ الميلاد</dt><dd class="num">{{ $trainer->birth_date?->format('Y-m-d') ?? '—' }}</dd></div>
                <div><dt>الهاتف</dt><dd class="num">{{ $trainer->phone ?: '—' }}</dd></div>
                <div><dt>البريد الإلكتروني</dt><dd>{{ $trainer->email ?: '—' }}</dd></div>
                <div><dt>أعلى مؤهل</dt><dd>{{ $trainer->highest_qualification ?: '—' }}</dd></div>
                <div><dt>سنوات الخبرة</dt><dd class="num">{{ $trainer->years_experience !== null ? $trainer->years_experience.' سنة' : '—' }}</dd></div>
                <div><dt>سعر الساعة</dt><dd class="num">{{ $trainer->hourly_rate !== null ? number_format((float) $trainer->hourly_rate, 2).' ج.م' : '—' }}</dd></div>
                <div><dt>الحساب البنكي</dt><dd class="num">{{ $trainer->bank_account ?: '—' }}</dd></div>
                <div><dt>العنوان</dt><dd>{{ $trainer->address ?: '—' }}</dd></div>
            </dl>
        </x-card>

        <x-card title="التخصصات والنبذة">
            <div class="field">
                <label>التخصصات التدريبية</label>
                @if ($trainer->courseCategories->isEmpty())
                    <p class="muted" style="margin:0">لم تُحدد تخصصات لهذا المدرب بعد.</p>
                @else
                    <div style="display:flex;flex-wrap:wrap;gap:6px">
                        @foreach ($trainer->courseCategories as $category)
                            <x-badge tone="brand">{{ $category->name }}</x-badge>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="field">
                <label>نبذة عن المدرب</label>
                <div>{!! $trainer->bio ? nl2br(e($trainer->bio)) : '<span class="muted">—</span>' !!}</div>
            </div>
        </x-card>
    </div>

    <div style="margin-block-start:18px">
        <x-card title="الشهادات المهنية" :flush="true">
            @if ($trainer->trainerCertifications->isEmpty())
                <x-empty text="لم تُسجّل شهادات لهذا المدرب بعد." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>الشهادة</th>
                            <th>الجهة المانحة</th>
                            <th>تاريخ الإصدار</th>
                            <th>تاريخ الانتهاء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($trainer->trainerCertifications as $certification)
                            <tr>
                                <td><strong>{{ $certification->title }}</strong></td>
                                <td>{{ $certification->issuer ?: '—' }}</td>
                                <td class="num">{{ $certification->issue_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="num">{{ $certification->expiry_date?->format('Y-m-d') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>
    </div>

    <div style="margin-block-start:18px">
        <x-card title="الخبرات العملية" :flush="true">
            @if ($trainer->trainerExperiences->isEmpty())
                <x-empty text="لم تُسجّل خبرات عملية لهذا المدرب بعد." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>جهة العمل</th>
                            <th>المسمى الوظيفي</th>
                            <th>من</th>
                            <th>إلى</th>
                            <th>الوصف</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($trainer->trainerExperiences as $experience)
                            <tr>
                                <td><strong>{{ $experience->organization ?: '—' }}</strong></td>
                                <td>{{ $experience->position ?: '—' }}</td>
                                <td class="num">{{ $experience->from_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="num">{{ $experience->to_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="muted">{{ $experience->description ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>
    </div>

    <div style="margin-block-start:18px">
        <x-card title="اعتمادات تدريس الدورات">
            @php($approvals = $trainer->trainerCourseApprovals->sortByDesc('created_at'))
            @php($courseOptions = $courses->mapWithKeys(fn ($course) => [$course->id => $course->code.' — '.$course->title]))

            @if ($approvals->isEmpty())
                <p class="muted" style="margin-block-start:0">لا توجد اعتمادات دورات لهذا المدرب بعد.</p>
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>الدورة</th>
                            <th>الحالة</th>
                            <th>اعتمد بواسطة</th>
                            <th>تاريخ الاعتماد</th>
                            <th>ملاحظات</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($approvals as $approval)
                            <tr>
                                <td>
                                    <strong>{{ $approval->course?->title ?? 'دورة محذوفة' }}</strong>
                                    @if ($approval->course)<div class="muted num" style="font-size:.8rem">{{ $approval->course->code }}</div>@endif
                                </td>
                                <td>
                                    @if ($approval->is_approved)
                                        <x-badge tone="ok">معتمد</x-badge>
                                    @else
                                        <x-badge tone="warn">غير معتمد</x-badge>
                                    @endif
                                </td>
                                <td>{{ $approval->approvedBy?->full_name ?? '—' }}</td>
                                <td class="num">{{ $approval->approved_at?->format('Y-m-d') ?? '—' }}</td>
                                <td class="muted">{{ $approval->notes ?: '—' }}</td>
                                <td class="actions-cell">
                                    @can('TRAINER_APPROVE_COURSE')
                                        <form method="POST" action="{{ route('trainers.approvals.destroy', [$trainer, $approval]) }}"
                                              data-confirm="هل تريد حذف سجل اعتماد هذه الدورة؟">
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

            @can('TRAINER_APPROVE_COURSE')
                <hr style="border:0;border-block-start:1px solid var(--line);margin-block:18px">

                <form method="POST" action="{{ route('trainers.approvals.store', $trainer) }}">
                    @csrf
                    <div class="form-grid">
                        <x-form.select name="course_id" label="الدورة" :options="$courseOptions" placeholder="اختر الدورة..." required
                                       :hint="$courses->isEmpty() ? 'لا توجد دورات مسجّلة بعد.' : null" />
                        <x-form.select name="is_approved" label="حالة الاعتماد" :options="[1 => 'معتمد', 0 => 'غير معتمد']"
                                       :selected="1" :placeholder="false" required />
                        <x-form.input name="notes" label="ملاحظات" wrapper="full" />
                    </div>

                    <div class="actions" style="margin-block-start:12px;justify-content:flex-end">
                        <button type="submit" class="btn">
                            <i class="fa-solid fa-check"></i> حفظ الاعتماد
                        </button>
                    </div>
                </form>
            @endcan
        </x-card>
    </div>
@endsection
