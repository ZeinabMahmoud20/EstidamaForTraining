@extends('layouts.app')

@section('title', 'المتدربون')

@section('content')
    <x-page-header title="المتدربون" subtitle="ملفات المتدربين وبياناتهم التعليمية والوظيفية وجهات التدريب المرتبطة بهم">
        @can('TRAINEES_MANAGE')
            <a class="btn" href="{{ route('trainees.create') }}">
                <i class="fa-solid fa-plus"></i> متدرب جديد
            </a>
        @endcan
    </x-page-header>

    <x-card title="بحث وتصفية">
        <form method="GET" action="{{ route('trainees.index') }}" class="filter-grid">
            <x-form.input name="q" label="بحث" :value="request('q')"
                          placeholder="الاسم أو الرقم القومي أو الهاتف..." />

            <x-form.select name="provider_id" label="جهة التدريب" :options="$providers" placeholder="كل الجهات"
                           :selected="request('provider_id')" />

            <x-form.select name="education_level_id" label="المستوى التعليمي" :options="$educationLevels"
                           placeholder="كل المستويات" :selected="request('education_level_id')" />

            <x-form.select name="job_grade_id" label="الدرجة الوظيفية" :options="$jobGrades"
                           placeholder="كل الدرجات" :selected="request('job_grade_id')" />

            <div class="field">
                <label for="is_active">الحالة</label>
                <select id="is_active" name="is_active" class="control">
                    <option value="">الكل</option>
                    <option value="1" @selected(request('is_active') === '1')>نشط</option>
                    <option value="0" @selected(request('is_active') === '0')>غير نشط</option>
                </select>
            </div>

            <div class="actions full">
                <button class="btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i> تطبيق</button>
                <a class="btn btn-ghost" href="{{ route('trainees.index') }}">إعادة ضبط</a>
            </div>
        </form>
    </x-card>

    <div style="margin-block-start:18px">
        <x-card :flush="true">
            @if ($trainees->isEmpty())
                <x-empty text="لا يوجد متدربون مطابقون لبحثك." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>المتدرب</th>
                            <th>التواصل</th>
                            <th>المؤهل والعمل</th>
                            <th>جهة التدريب</th>
                            <th>الإنذارات</th>
                            <th>التسجيلات</th>
                            <th>الحالة</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($trainees as $trainee)
                            @php($primaryProvider = $trainee->traineeProviders->firstWhere('is_primary', true) ?? $trainee->traineeProviders->first())
                            <tr>
                                <td>
                                    <a href="{{ route('trainees.show', $trainee) }}"><strong>{{ $trainee->full_name }}</strong></a>
                                    <div class="muted num" style="font-size:.8rem">
                                        {{ $trainee->national_id }}
                                    </div>
                                </td>
                                <td>
                                    <div class="num">{{ $trainee->phone ?: '—' }}</div>
                                    <div class="muted" style="font-size:.8rem">{{ $trainee->email ?: 'بدون بريد' }}</div>
                                </td>
                                <td>
                                    <div>{{ $trainee->educationLevel?->name ?? '—' }}</div>
                                    <div class="muted" style="font-size:.8rem">{{ $trainee->job_title ?: 'بدون مسمى وظيفي' }}</div>
                                </td>
                                <td>
                                    @if ($primaryProvider)
                                        {{ $primaryProvider->provider?->name ?? '—' }}
                                        @if ($primaryProvider->is_primary)
                                            <x-badge tone="brand">أساسية</x-badge>
                                        @endif
                                        @if ($trainee->trainee_providers_count > 1)
                                            <div class="muted" style="font-size:.8rem">+{{ $trainee->trainee_providers_count - 1 }} جهة أخرى</div>
                                        @endif
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <x-badge :tone="$trainee->trainee_warnings_count > 0 ? 'warn' : 'neutral'">
                                        {{ $trainee->trainee_warnings_count }}
                                    </x-badge>
                                </td>
                                <td>
                                    <x-badge :tone="$trainee->enrollments_count > 0 ? 'ok' : 'neutral'">
                                        {{ $trainee->enrollments_count }}
                                    </x-badge>
                                </td>
                                <td>
                                    @if ($trainee->is_active)
                                        <x-badge tone="ok">نشط</x-badge>
                                    @else
                                        <x-badge>غير نشط</x-badge>
                                    @endif
                                </td>
                                <td class="actions-cell">
                                    <div class="actions" style="justify-content:flex-end">
                                        <a class="btn btn-ghost btn-sm" href="{{ route('trainees.show', $trainee) }}">تفاصيل</a>
                                        @can('TRAINEES_MANAGE')
                                            <a class="btn btn-outline btn-sm" href="{{ route('trainees.edit', $trainee) }}">تعديل</a>
                                            <form method="POST" action="{{ route('trainees.destroy', $trainee) }}"
                                                  data-confirm="هل تريد حذف المتدرب «{{ $trainee->full_name }}»؟">
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
                    {{ $trainees->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection
