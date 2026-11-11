@php
    $editing = isset($bank) && $bank && $bank->exists;

    $questionRows = old('questions');
    if ($questionRows === null) {
        $questionRows = $editing
            ? $bank->questions->map(fn ($question) => [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'question_type' => $question->question_type?->value,
                'difficulty' => $question->difficulty,
                'default_points' => $question->default_points,
                'is_active' => $question->is_active,
                'options' => $question->questionOptions
                    ->sortBy('sort_order')
                    ->values()
                    ->map(fn ($option) => [
                        'option_text' => $option->option_text,
                        'is_correct' => $option->is_correct,
                    ])->all(),
            ])->values()->all()
            : [];
    }
@endphp

<form method="POST" action="{{ $editing ? route('question-banks.update', $bank) : route('question-banks.store') }}">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <div class="grid">
        <x-card title="بيانات البنك">
            <div class="form-grid">
                <x-form.select name="course_id" label="الدورة" :options="$courses" required
                               :selected="$editing ? $bank->course_id : null" />
                <x-form.select name="module_id" label="الوحدة" :options="$modules" placeholder="بدون وحدة"
                               :selected="$editing ? $bank->module_id : null" />
                <x-form.input name="name" label="اسم بنك الأسئلة" wrapper="full"
                              :value="$editing ? $bank->name : null"
                              placeholder="مثال: أسئلة اختبار الذكاء الاصطناعي" />
            </div>
        </x-card>

        <x-card title="الأسئلة والخيارات">
            <p class="muted" style="margin-block-start:0">
                لكل سؤال اختيار من متعدد أو صح/خطأ، أدخل الخيارات وحدّد خيارًا صحيحًا واحدًا.
                الأسئلة النصية لا تحتاج خيارات.
            </p>

            <div data-repeat-group>
                <div class="repeat-list" data-repeat>
                    @foreach ($questionRows as $i => $row)
                        <div class="repeat-row" data-repeat-row style="grid-template-columns:1fr">
                            <input type="hidden" name="questions[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">

                            <div class="form-grid">
                                <div class="field full">
                                    <label for="q-text-{{ $i }}">نص السؤال <span class="req">*</span></label>
                                    <textarea id="q-text-{{ $i }}" name="questions[{{ $i }}][question_text]"
                                              class="control @error("questions.$i.question_text") is-invalid @enderror"
                                              required>{{ $row['question_text'] ?? '' }}</textarea>
                                    @error("questions.$i.question_text")<div class="error">{{ $message }}</div>@enderror
                                </div>

                                <div class="field">
                                    <label for="q-type-{{ $i }}">نوع السؤال</label>
                                    <select id="q-type-{{ $i }}" name="questions[{{ $i }}][question_type]" class="control" required>
                                        @foreach ($questionTypes as $value => $label)
                                            <option value="{{ $value }}" @selected(($row['question_type'] ?? 'mcq') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="field">
                                    <label for="q-points-{{ $i }}">الدرجة الافتراضية</label>
                                    <input id="q-points-{{ $i }}" name="questions[{{ $i }}][default_points]" type="number"
                                           step="0.01" min="0.01" class="control"
                                           value="{{ $row['default_points'] ?? 1 }}" required>
                                </div>

                                <div class="field">
                                    <label for="q-diff-{{ $i }}">مستوى الصعوبة (1-5)</label>
                                    <input id="q-diff-{{ $i }}" name="questions[{{ $i }}][difficulty]" type="number"
                                           min="1" max="5" class="control" value="{{ $row['difficulty'] ?? '' }}">
                                </div>

                                <div class="field">
                                    <label>&nbsp;</label>
                                    <input type="hidden" name="questions[{{ $i }}][is_active]" value="0">
                                    <label style="font-weight:600;display:flex;gap:8px;align-items:center">
                                        <input type="checkbox" name="questions[{{ $i }}][is_active]" value="1"
                                               @checked((bool) ($row['is_active'] ?? true))>
                                        سؤال مفعّل
                                    </label>
                                </div>
                            </div>

                            <div class="form-grid" style="margin-block-start:10px">
                                @for ($j = 0; $j < 4; $j++)
                                    @php($option = $row['options'][$j] ?? [])
                                    <div class="field">
                                        <label for="q-opt-{{ $i }}-{{ $j }}">الخيار {{ $j + 1 }}</label>
                                        <div style="display:flex;gap:8px;align-items:center">
                                            <input id="q-opt-{{ $i }}-{{ $j }}"
                                                   name="questions[{{ $i }}][options][{{ $j }}][option_text]"
                                                   type="text" class="control" maxlength="500"
                                                   value="{{ $option['option_text'] ?? '' }}" placeholder="اتركه فارغًا إذا غير مطلوب">
                                            <input type="hidden" name="questions[{{ $i }}][options][{{ $j }}][is_correct]" value="0">
                                            <label style="display:flex;gap:6px;align-items:center;white-space:nowrap;font-size:.85rem">
                                                <input type="checkbox" name="questions[{{ $i }}][options][{{ $j }}][is_correct]" value="1"
                                                       @checked((bool) ($option['is_correct'] ?? false))>
                                                صحيح
                                            </label>
                                        </div>
                                    </div>
                                @endfor
                            </div>

                            @error("questions.$i.options")<div class="error" style="margin-block-start:8px">{{ $message }}</div>@enderror
                            @error("questions.$i.question_type")<div class="error" style="margin-block-start:8px">{{ $message }}</div>@enderror

                            <div class="actions" style="margin-block-start:8px">
                                <button type="button" class="btn btn-ghost btn-sm" data-repeat-remove>
                                    <i class="fa-solid fa-trash"></i> حذف السؤال
                                </button>
                            </div>
                        </div>
                    @endforeach

                    <template data-repeat-template>
                        <div class="repeat-row" data-repeat-row style="grid-template-columns:1fr">
                            <input type="hidden" name="questions[__INDEX__][id]" value="">

                            <div class="form-grid">
                                <div class="field full">
                                    <label for="q-text-__INDEX__">نص السؤال <span class="req">*</span></label>
                                    <textarea id="q-text-__INDEX__" name="questions[__INDEX__][question_text]" class="control" required></textarea>
                                </div>

                                <div class="field">
                                    <label for="q-type-__INDEX__">نوع السؤال</label>
                                    <select id="q-type-__INDEX__" name="questions[__INDEX__][question_type]" class="control" required>
                                        @foreach ($questionTypes as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="field">
                                    <label for="q-points-__INDEX__">الدرجة الافتراضية</label>
                                    <input id="q-points-__INDEX__" name="questions[__INDEX__][default_points]" type="number"
                                           step="0.01" min="0.01" class="control" value="1" required>
                                </div>

                                <div class="field">
                                    <label for="q-diff-__INDEX__">مستوى الصعوبة (1-5)</label>
                                    <input id="q-diff-__INDEX__" name="questions[__INDEX__][difficulty]" type="number" min="1" max="5" class="control">
                                </div>

                                <div class="field">
                                    <label>&nbsp;</label>
                                    <input type="hidden" name="questions[__INDEX__][is_active]" value="0">
                                    <label style="font-weight:600;display:flex;gap:8px;align-items:center">
                                        <input type="checkbox" name="questions[__INDEX__][is_active]" value="1" checked>
                                        سؤال مفعّل
                                    </label>
                                </div>
                            </div>

                            <div class="form-grid" style="margin-block-start:10px">
                                @for ($j = 0; $j < 4; $j++)
                                    <div class="field">
                                        <label for="q-opt-__INDEX__-{{ $j }}">الخيار {{ $j + 1 }}</label>
                                        <div style="display:flex;gap:8px;align-items:center">
                                            <input id="q-opt-__INDEX__-{{ $j }}"
                                                   name="questions[__INDEX__][options][{{ $j }}][option_text]"
                                                   type="text" class="control" maxlength="500">
                                            <input type="hidden" name="questions[__INDEX__][options][{{ $j }}][is_correct]" value="0">
                                            <label style="display:flex;gap:6px;align-items:center;white-space:nowrap;font-size:.85rem">
                                                <input type="checkbox" name="questions[__INDEX__][options][{{ $j }}][is_correct]" value="1">
                                                صحيح
                                            </label>
                                        </div>
                                    </div>
                                @endfor
                            </div>

                            <div class="actions" style="margin-block-start:8px">
                                <button type="button" class="btn btn-ghost btn-sm" data-repeat-remove>
                                    <i class="fa-solid fa-trash"></i> حذف السؤال
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="actions" style="margin-block-start:10px">
                    <button type="button" class="btn btn-outline btn-sm" data-repeat-add>
                        <i class="fa-solid fa-plus"></i> إضافة سؤال
                    </button>
                </div>
            </div>
        </x-card>

        <div class="actions" style="justify-content:flex-end">
            <a class="btn btn-ghost" href="{{ route('question-banks.index') }}">إلغاء</a>
            <button type="submit" class="btn">
                <i class="fa-solid fa-floppy-disk"></i> {{ $editing ? 'حفظ التعديلات' : 'إنشاء البنك' }}
            </button>
        </div>
    </div>
</form>
