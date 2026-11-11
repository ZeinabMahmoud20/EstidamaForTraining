@php
    $editing = isset($form) && $form && $form->exists;

    $sectionRows = old('sections');
    if ($sectionRows === null) {
        $sectionRows = $editing
            ? $form->evaluationSections->map(fn ($section) => [
                'id' => $section->id,
                'title' => $section->title,
                'weight' => $section->weight,
                'items' => $section->evaluationItems->values()->map(fn ($item) => [
                    'id' => $item->id,
                    'item_text' => $item->item_text,
                    'answer_type' => $item->answer_type?->value,
                    'weight' => $item->weight,
                    'is_required' => $item->is_required,
                ])->all(),
            ])->values()->all()
            : [];
    }
@endphp

<form method="POST" action="{{ $editing ? route('evaluations.update', $form) : route('evaluations.store') }}">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <div class="grid">
        <x-card title="بيانات النموذج">
            <div class="form-grid">
                <x-form.input name="code" label="كود النموذج" required
                              :value="$editing ? $form->code : null"
                              placeholder="مثال: EVAL-TRAINER-01" />
                <x-form.input name="title" label="عنوان النموذج" required
                              :value="$editing ? $form->title : null" />

                <x-form.select name="target_type" label="هدف التقييم" :options="$targets" required
                               :selected="$editing ? $form->target_type?->value : null" />
                <x-form.select name="evaluator_type" label="نوع المقيّم" :options="$evaluators" required
                               :selected="$editing ? $form->evaluator_type?->value : null" />

                <x-form.input name="version" type="number" min="1" label="الإصدار"
                              :value="$editing ? $form->version : 1" />

                <div class="field">
                    <label>&nbsp;</label>
                    <input type="hidden" name="is_anonymous" value="0">
                    <label style="font-weight:600;display:flex;gap:8px;align-items:center">
                        <input type="checkbox" name="is_anonymous" value="1"
                               @checked((bool) old('is_anonymous', $editing ? $form->is_anonymous : true))>
                        تقييم مجهول الهوية
                    </label>
                </div>

                <div class="field">
                    <label>&nbsp;</label>
                    <input type="hidden" name="is_active" value="0">
                    <label style="font-weight:600;display:flex;gap:8px;align-items:center">
                        <input type="checkbox" name="is_active" value="1"
                               @checked((bool) old('is_active', $editing ? $form->is_active : true))>
                        نموذج مفعّل
                    </label>
                </div>
            </div>
        </x-card>

        <x-card title="الأقسام والبنود">
            <p class="muted" style="margin-block-start:0">
                أضف الأقسام، ثم أدخل حتى خمسة بنود في كل قسم. يمكن ترك البنود الزائدة فارغة.
                بنود المقياس من 1 إلى 5 تُحتسب في الدرجة النهائية، والبنود النصية تُحفظ كملاحظات.
            </p>

            <div data-repeat-group>
                <div class="repeat-list" data-repeat>
                    @foreach ($sectionRows as $i => $section)
                        <div class="repeat-row" data-repeat-row style="grid-template-columns:1fr">
                            <input type="hidden" name="sections[{{ $i }}][id]" value="{{ $section['id'] ?? '' }}">

                            <div class="form-grid">
                                <div class="field">
                                    <label for="sec-title-{{ $i }}">عنوان القسم</label>
                                    <input id="sec-title-{{ $i }}" name="sections[{{ $i }}][title]" type="text"
                                           class="control" value="{{ $section['title'] ?? '' }}" placeholder="مثال: محتوى البرنامج">
                                </div>
                                <div class="field">
                                    <label for="sec-weight-{{ $i }}">وزن القسم %</label>
                                    <input id="sec-weight-{{ $i }}" name="sections[{{ $i }}][weight]" type="number"
                                           step="0.01" min="0" max="100" class="control"
                                           value="{{ $section['weight'] ?? '' }}" placeholder="اختياري">
                                </div>
                            </div>

                            <div class="repeat-list" style="margin-block-start:10px">
                                @for ($j = 0; $j < 5; $j++)
                                    @php($item = $section['items'][$j] ?? [])
                                    <div class="form-grid" style="gap:10px">
                                        <input type="hidden" name="sections[{{ $i }}][items][{{ $j }}][id]" value="{{ $item['id'] ?? '' }}">

                                        <div class="field full">
                                            <label for="item-text-{{ $i }}-{{ $j }}">البند {{ $j + 1 }}</label>
                                            <input id="item-text-{{ $i }}-{{ $j }}"
                                                   name="sections[{{ $i }}][items][{{ $j }}][item_text]"
                                                   type="text" class="control" maxlength="500"
                                                   value="{{ $item['item_text'] ?? '' }}"
                                                   placeholder="اتركه فارغًا إذا لم يكن مطلوبًا">
                                        </div>

                                        <div class="field">
                                            <label for="item-type-{{ $i }}-{{ $j }}">نوع الإجابة</label>
                                            <select id="item-type-{{ $i }}-{{ $j }}"
                                                    name="sections[{{ $i }}][items][{{ $j }}][answer_type]"
                                                    class="control">
                                                @foreach ($answerTypes as $value => $label)
                                                    <option value="{{ $value }}" @selected(($item['answer_type'] ?? 'scale_1_5') === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="field">
                                            <label for="item-weight-{{ $i }}-{{ $j }}">الوزن</label>
                                            <input id="item-weight-{{ $i }}-{{ $j }}"
                                                   name="sections[{{ $i }}][items][{{ $j }}][weight]"
                                                   type="number" step="0.01" min="0" max="100" class="control"
                                                   value="{{ $item['weight'] ?? 1 }}">
                                        </div>

                                        <div class="field">
                                            <label>&nbsp;</label>
                                            <input type="hidden" name="sections[{{ $i }}][items][{{ $j }}][is_required]" value="0">
                                            <label style="font-weight:600;display:flex;gap:8px;align-items:center">
                                                <input type="checkbox" name="sections[{{ $i }}][items][{{ $j }}][is_required]" value="1"
                                                       @checked((bool) ($item['is_required'] ?? true))>
                                                إلزامي
                                            </label>
                                        </div>
                                    </div>
                                @endfor
                            </div>

                            <div class="actions" style="margin-block-start:8px">
                                <button type="button" class="btn btn-ghost btn-sm" data-repeat-remove>
                                    <i class="fa-solid fa-trash"></i> حذف القسم
                                </button>
                            </div>
                        </div>
                    @endforeach

                    <template data-repeat-template>
                        <div class="repeat-row" data-repeat-row style="grid-template-columns:1fr">
                            <input type="hidden" name="sections[__INDEX__][id]" value="">

                            <div class="form-grid">
                                <div class="field">
                                    <label for="sec-title-__INDEX__">عنوان القسم</label>
                                    <input id="sec-title-__INDEX__" name="sections[__INDEX__][title]" type="text" class="control">
                                </div>
                                <div class="field">
                                    <label for="sec-weight-__INDEX__">وزن القسم %</label>
                                    <input id="sec-weight-__INDEX__" name="sections[__INDEX__][weight]" type="number"
                                           step="0.01" min="0" max="100" class="control">
                                </div>
                            </div>

                            <div class="repeat-list" style="margin-block-start:10px">
                                @for ($j = 0; $j < 5; $j++)
                                    <div class="form-grid" style="gap:10px">
                                        <input type="hidden" name="sections[__INDEX__][items][{{ $j }}][id]" value="">

                                        <div class="field full">
                                            <label for="item-text-__INDEX__-{{ $j }}">البند {{ $j + 1 }}</label>
                                            <input id="item-text-__INDEX__-{{ $j }}"
                                                   name="sections[__INDEX__][items][{{ $j }}][item_text]"
                                                   type="text" class="control" maxlength="500">
                                        </div>

                                        <div class="field">
                                            <label for="item-type-__INDEX__-{{ $j }}">نوع الإجابة</label>
                                            <select id="item-type-__INDEX__-{{ $j }}"
                                                    name="sections[__INDEX__][items][{{ $j }}][answer_type]" class="control">
                                                @foreach ($answerTypes as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="field">
                                            <label for="item-weight-__INDEX__-{{ $j }}">الوزن</label>
                                            <input id="item-weight-__INDEX__-{{ $j }}"
                                                   name="sections[__INDEX__][items][{{ $j }}][weight]"
                                                   type="number" step="0.01" min="0" max="100" class="control" value="1">
                                        </div>

                                        <div class="field">
                                            <label>&nbsp;</label>
                                            <input type="hidden" name="sections[__INDEX__][items][{{ $j }}][is_required]" value="0">
                                            <label style="font-weight:600;display:flex;gap:8px;align-items:center">
                                                <input type="checkbox" name="sections[__INDEX__][items][{{ $j }}][is_required]" value="1" checked>
                                                إلزامي
                                            </label>
                                        </div>
                                    </div>
                                @endfor
                            </div>

                            <div class="actions" style="margin-block-start:8px">
                                <button type="button" class="btn btn-ghost btn-sm" data-repeat-remove>
                                    <i class="fa-solid fa-trash"></i> حذف القسم
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="actions" style="margin-block-start:10px">
                    <button type="button" class="btn btn-outline btn-sm" data-repeat-add>
                        <i class="fa-solid fa-plus"></i> إضافة قسم
                    </button>
                </div>
            </div>
        </x-card>

        <div class="actions" style="justify-content:flex-end">
            <a class="btn btn-ghost" href="{{ route('evaluations.index') }}">إلغاء</a>
            <button type="submit" class="btn">
                <i class="fa-solid fa-floppy-disk"></i> {{ $editing ? 'حفظ التعديلات' : 'إنشاء النموذج' }}
            </button>
        </div>
    </div>
</form>
