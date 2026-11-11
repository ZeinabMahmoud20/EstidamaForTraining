@php
    $editing = isset($exam) && $exam && $exam->exists;

    $selectedIds = old('question_ids');
    if ($selectedIds === null) {
        $selectedIds = $editing ? $exam->questions->pluck('id')->map(fn ($id) => (string) $id)->all() : [];
    }
    $selectedIds = array_map('strval', (array) $selectedIds);

    $selectedPoints = old('points');
    if ($selectedPoints === null) {
        $selectedPoints = $editing
            ? $exam->questions->mapWithKeys(fn ($question) => [$question->id => $question->pivot->points])->all()
            : [];
    }
@endphp

<form method="POST" action="{{ $editing ? route('exams.update', $exam) : route('exams.store') }}">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <div class="grid">
        <x-card title="بيانات الاختبار">
            <div class="form-grid">
                <x-form.select name="session_id" label="الجلسة" :options="$sessions" required
                               :selected="$editing ? $exam->session_id : null" />
                <x-form.select name="exam_type" label="نوع الاختبار" :options="$examTypes" required
                               :selected="$editing ? $exam->exam_type?->value : null" />
                <x-form.input name="title" label="عنوان الاختبار" wrapper="full"
                              :value="$editing ? $exam->title : null"
                              placeholder="مثال: الاختبار النهائي — الذكاء الاصطناعي" />

                <x-form.input name="scheduled_start" type="datetime-local" label="موعد الاختبار"
                              :value="$editing ? $exam->scheduled_start?->format('Y-m-d\TH:i') : null" />
                <x-form.input name="duration_minutes" type="number" min="1" label="المدة (دقائق)"
                              :value="$editing ? $exam->duration_minutes : null" />

                <x-form.input name="total_score" type="number" step="0.01" min="0.01" label="الدرجة الكلية" required
                              :value="$editing ? $exam->total_score : null" />
                <x-form.input name="pass_score" type="number" step="0.01" min="0" label="درجة النجاح"
                              :value="$editing ? $exam->pass_score : null"
                              hint="إذا تُركت فارغة تُعد النجاح عند 50%." />
                <x-form.input name="weight_pct" type="number" step="0.01" min="0" max="100" label="وزن الاختبار %"
                              :value="$editing ? $exam->weight_pct : null" />
            </div>
        </x-card>

        <x-card title="أسئلة الاختبار" :flush="true">
            @if ($questions->isEmpty())
                <x-empty text="لا توجد أسئلة مفعّلة. أضف أسئلة من بنوك الأسئلة أولًا." />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>اختيار</th>
                            <th>السؤال</th>
                            <th>البنك / الدورة</th>
                            <th>النوع</th>
                            <th>الدرجة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($questions as $question)
                            @php($isSelected = in_array((string) $question->id, $selectedIds, true))
                            <tr>
                                <td style="width:70px">
                                    <input type="checkbox" name="question_ids[]" value="{{ $question->id }}"
                                           @checked($isSelected) aria-label="اختيار السؤال">
                                </td>
                                <td>
                                    <strong>{{ \Illuminate\Support\Str::limit($question->question_text, 140) }}</strong>
                                    <div class="muted" style="font-size:.8rem">الدرجة الافتراضية: {{ $question->default_points }}</div>
                                </td>
                                <td>
                                    {{ $question->bank?->name ?: 'بنك #'.$question->bank_id }}
                                    <div class="muted" style="font-size:.8rem">{{ $question->bank?->course?->title ?? '—' }}</div>
                                </td>
                                <td>{{ $question->question_type?->label() ?? '—' }}</td>
                                <td style="width:130px">
                                    <input type="number" step="0.01" min="0" class="control"
                                           name="points[{{ $question->id }}]"
                                           value="{{ $selectedPoints[$question->id] ?? $question->default_points }}"
                                           aria-label="درجة السؤال">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>

        <div class="actions" style="justify-content:flex-end">
            <a class="btn btn-ghost" href="{{ $editing ? route('exams.show', $exam) : route('exams.index') }}">إلغاء</a>
            <button type="submit" class="btn">
                <i class="fa-solid fa-floppy-disk"></i> {{ $editing ? 'حفظ التعديلات' : 'إنشاء الاختبار' }}
            </button>
        </div>
    </div>
</form>
