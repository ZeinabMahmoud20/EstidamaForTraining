@extends('layouts.app')

@section('title', $course->title)

@section('content')
    <x-page-header :title="$course->title" :subtitle="'كود الدورة: '.$course->code">
        <a class="btn btn-ghost" href="{{ route('courses.index') }}">
            <i class="fa-solid fa-arrow-right"></i> رجوع للدورات
        </a>
        @can('COURSES_MANAGE')
            <a class="btn" href="{{ route('courses.edit', $course) }}">
                <i class="fa-solid fa-pen"></i> تعديل
            </a>
            <form method="POST" action="{{ route('courses.destroy', $course) }}"
                  data-confirm="هل تريد حذف الدورة «{{ $course->title }}»؟">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit"><i class="fa-solid fa-trash"></i> حذف</button>
            </form>
        @endcan
    </x-page-header>

    <div class="grid grid-4" style="margin-block-end:18px">
        <x-stat label="الجلسات المجدولة" :value="$course->course_sessions_count" icon="fa-calendar-days" />
        <x-stat label="إجمالي الساعات" :value="$course->total_hours !== null ? (float) $course->total_hours : '—'" icon="fa-clock" />
        <x-stat label="الوحدات التدريبية" :value="$course->courseModules->count()" icon="fa-layer-group" />
        <x-stat label="الحضور المطلوب" :value="($course->min_attendance_pct !== null ? (float) $course->min_attendance_pct : 0).'%'" icon="fa-user-check" tone="ok" />
    </div>

    <div class="grid grid-2">
        <x-card title="البيانات الأساسية">
            <dl class="details">
                <div><dt>الكود</dt><dd class="num">{{ $course->code }}</dd></div>
                <div>
                    <dt>الحالة</dt>
                    <dd>
                        @if ($course->is_active)
                            <x-badge tone="ok">مفعّلة</x-badge>
                        @else
                            <x-badge>غير مفعّلة</x-badge>
                        @endif
                    </dd>
                </div>
                <div><dt>التصنيف</dt><dd>{{ $course->category?->name ?? '—' }}</dd></div>
                <div><dt>النوع</dt><dd>{{ $course->courseType?->name ?? '—' }}</dd></div>
                <div><dt>المستوى</dt><dd>{{ $course->level?->label() ?? '—' }}</dd></div>
                <div><dt>طريقة التنفيذ</dt><dd>{{ $course->default_delivery_mode?->label() ?? '—' }}</dd></div>
                <div><dt>عدد الأيام</dt><dd>{{ $course->days_count ?? '—' }}</dd></div>
                <div><dt>السعر</dt><dd class="num">{{ $course->price !== null ? number_format((float) $course->price, 2).' ج.م' : '—' }}</dd></div>
                <div><dt>الحد الأدنى للمتدربين</dt><dd class="num">{{ $course->min_trainees ?? '—' }}</dd></div>
                <div><dt>الحد الأقصى للمتدربين</dt><dd class="num">{{ $course->max_trainees ?? '—' }}</dd></div>
                <div><dt>درجة النجاح</dt><dd class="num">{{ $course->pass_score_pct !== null ? (float) $course->pass_score_pct : 0 }}%</dd></div>
                <div><dt>نسبة الحضور المطلوبة</dt><dd class="num">{{ $course->min_attendance_pct !== null ? (float) $course->min_attendance_pct : 0 }}%</dd></div>
            </dl>
        </x-card>

        <x-card title="المتطلبات السابقة">
            @if ($course->prerequisites->isEmpty())
                <p class="muted" style="margin:0">لا توجد متطلبات سابقة لهذه الدورة.</p>
            @else
                <ul style="margin:0;padding-inline-start:20px">
                    @foreach ($course->prerequisites as $prerequisite)
                        <li>
                            <a href="{{ route('courses.show', $prerequisite) }}">
                                {{ $prerequisite->code }} — {{ $prerequisite->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($course->requiredFor->isNotEmpty())
                <hr style="border:0;border-block-start:1px solid var(--line);margin-block:16px">
                <p class="muted" style="margin-block:0 6px">دورات تعتمد على هذه الدورة كمتطلب سابق:</p>
                <ul style="margin:0;padding-inline-start:20px">
                    @foreach ($course->requiredFor as $dependent)
                        <li>
                            <a href="{{ route('courses.show', $dependent) }}">
                                {{ $dependent->code }} — {{ $dependent->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>

    <x-card title="الوصف والأهداف" style="margin-block-start:18px">
        <div class="grid grid-2">
            @foreach ([
                ['وصف الدورة', $course->description],
                ['أهداف الدورة', $course->objectives],
                ['محتوى الدورة', $course->content_outline],
                ['الفئة المستهدفة', $course->target_audience],
            ] as [$label, $text])
                <div>
                    <h4>{{ $label }}</h4>
                    <div>{!! $text ? nl2br(e($text)) : '<span class="muted">—</span>' !!}</div>
                </div>
            @endforeach
        </div>
    </x-card>

    <div style="margin-block-start:18px">
        <x-card title="الوحدات التدريبية" :flush="true">
            @if ($course->courseModules->isEmpty())
                <x-empty text="لم تُضف وحدات تدريبية بعد." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>عنوان الوحدة</th>
                            <th>الوصف</th>
                            <th>الساعات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($course->courseModules as $module)
                            <tr>
                                <td class="num">{{ $module->sort_order ?? $loop->iteration }}</td>
                                <td><strong>{{ $module->title }}</strong></td>
                                <td class="muted">{{ $module->description ?: '—' }}</td>
                                <td class="num">{{ $module->hours !== null ? (float) $module->hours : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>
    </div>

    <div style="margin-block-start:18px">
        <x-card title="شروط الالتحاق (المعايير)" :flush="true">
            @if ($course->courseCriteria->isEmpty())
                <x-empty text="لا توجد شروط التحاق مسجّلة لهذه الدورة." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>نوع المعيار</th>
                            <th>العملية</th>
                            <th>القيمة</th>
                            <th>إلزامي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($course->courseCriteria as $criterion)
                            <tr>
                                <td>{{ $criterion->criteriaType?->name ?? '—' }}</td>
                                <td>{{ $criterion->operator?->label() ?? '—' }}</td>
                                <td class="num">{{ $criterion->value ?: '—' }}</td>
                                <td>
                                    @if ($criterion->is_mandatory)
                                        <x-badge tone="brand">إلزامي</x-badge>
                                    @else
                                        <x-badge>اختياري</x-badge>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>
    </div>
@endsection
