@php
    $editing = isset($course) && $course && $course->exists;

    $moduleRows = old('modules');
    if ($moduleRows === null) {
        $moduleRows = $editing
            ? $course->courseModules->map(fn ($module) => [
                'id' => $module->id,
                'title' => $module->title,
                'description' => $module->description,
                'hours' => $module->hours,
                'sort_order' => $module->sort_order,
            ])->values()->all()
            : [];
    }

    $criteriaRows = old('criteria');
    if ($criteriaRows === null) {
        $criteriaRows = $editing
            ? $course->courseCriteria->map(fn ($criterion) => [
                'criteria_type_id' => $criterion->criteria_type_id,
                'operator' => $criterion->operator?->value,
                'value' => $criterion->value,
                'is_mandatory' => $criterion->is_mandatory,
            ])->values()->all()
            : [];
    }

    $selectedPrerequisites = array_map(
        'intval',
        (array) old('prerequisites', $editing ? $course->prerequisites->pluck('id')->all() : [])
    );
@endphp

<form method="POST"
      action="{{ $editing ? route('courses.update', $course) : route('courses.store') }}">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <div class="grid">
        <x-card title="البيانات الأساسية">
            <div class="form-grid">
                <x-form.input name="code" label="كود الدورة" :value="$editing ? $course->code : null" required
                              hint="حروف لاتينية وأرقام وشرطات فقط، مثال: CRS-101" />
                <x-form.input name="title" label="اسم الدورة" :value="$editing ? $course->title : null" required />

                <x-form.select name="category_id" label="التصنيف" :options="$categories" placeholder="بدون تصنيف"
                               :selected="$editing ? $course->category_id : null" />
                <x-form.select name="course_type_id" label="نوع الدورة" :options="$types" placeholder="بدون نوع"
                               :selected="$editing ? $course->course_type_id : null" />

                <x-form.select name="level" label="المستوى" :options="$levels" placeholder="بدون مستوى"
                               :selected="$editing ? $course->level?->value : null" />
                <x-form.select name="default_delivery_mode" label="طريقة التنفيذ" :options="$deliveryModes" placeholder="بدون تحديد"
                               :selected="$editing ? $course->default_delivery_mode?->value : null" />

                <x-form.input name="total_hours" type="number" step="0.01" min="0" label="إجمالي الساعات"
                              :value="$editing ? $course->total_hours : null" />
                <x-form.input name="days_count" type="number" min="1" label="عدد الأيام"
                              :value="$editing ? $course->days_count : null" />

                <x-form.input name="price" type="number" step="0.01" min="0" label="السعر (ج.م)"
                              :value="$editing ? $course->price : null" />
                <x-form.input name="max_trainees" type="number" min="1" label="الحد الأقصى للمتدربين"
                              :value="$editing ? $course->max_trainees : null" />

                <x-form.input name="min_trainees" type="number" min="1" label="الحد الأدنى للمتدربين"
                              :value="$editing ? $course->min_trainees : null" />
                <x-form.input name="min_attendance_pct" type="number" step="0.01" min="0" max="100"
                              label="نسبة الحضور المطلوبة %" :value="$editing ? $course->min_attendance_pct : 75" />

                <x-form.input name="pass_score_pct" type="number" step="0.01" min="0" max="100"
                              label="درجة النجاح %" :value="$editing ? $course->pass_score_pct : 60" />

                <x-form.checkbox name="is_active" label="الدورة مفعّلة ومتاحة"
                                 :checked="$editing ? $course->is_active : true" />

                <x-form.textarea name="target_audience" label="الفئة المستهدفة" wrapper="full"
                                 :value="$editing ? $course->target_audience : null" />
                <x-form.textarea name="description" label="وصف الدورة" wrapper="full"
                                 :value="$editing ? $course->description : null" />
                <x-form.textarea name="objectives" label="أهداف الدورة" wrapper="full"
                                 :value="$editing ? $course->objectives : null" />
                <x-form.textarea name="content_outline" label="محتوى الدورة" wrapper="full"
                                 :value="$editing ? $course->content_outline : null" />
            </div>
        </x-card>

        <x-card title="الوحدات التدريبية">
            <p class="muted" style="margin-block-start:0">
                قسّم الدورة إلى وحدات مرتبة. أضف صفًا لكل وحدة، واحذف أي صف لا تحتاجه قبل الحفظ.
            </p>

            <div data-repeat-group>
                <div class="repeat-list" data-repeat>
                    @foreach ($moduleRows as $i => $row)
                        <div class="repeat-row repeat-row--module" data-repeat-row>
                            <input type="hidden" name="modules[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">

                            <div class="field">
                                <label for="module-title-{{ $i }}">عنوان الوحدة <span class="req">*</span></label>
                                <input id="module-title-{{ $i }}" name="modules[{{ $i }}][title]" class="control @error("modules.$i.title") is-invalid @enderror"
                                       value="{{ $row['title'] ?? '' }}" required>
                                @error("modules.$i.title")<div class="error">{{ $message }}</div>@enderror
                            </div>

                            <div class="field">
                                <label for="module-hours-{{ $i }}">الساعات</label>
                                <input id="module-hours-{{ $i }}" name="modules[{{ $i }}][hours]" type="number" step="0.01" min="0" class="control"
                                       value="{{ $row['hours'] ?? '' }}">
                            </div>

                            <div class="field">
                                <label for="module-order-{{ $i }}">الترتيب</label>
                                <input id="module-order-{{ $i }}" name="modules[{{ $i }}][sort_order]" type="number" min="0" class="control"
                                       value="{{ $row['sort_order'] ?? '' }}">
                            </div>

                            <div class="field">
                                <label>&nbsp;</label>
                                <button type="button" class="btn btn-ghost btn-sm" data-repeat-remove>
                                    <i class="fa-solid fa-trash"></i> حذف
                                </button>
                            </div>

                            <div class="field full">
                                <label for="module-description-{{ $i }}">وصف الوحدة</label>
                                <textarea id="module-description-{{ $i }}" name="modules[{{ $i }}][description]" class="control">{{ $row['description'] ?? '' }}</textarea>
                            </div>
                        </div>
                    @endforeach

                    <template data-repeat-template>
                        <div class="repeat-row repeat-row--module" data-repeat-row>
                            <input type="hidden" name="modules[__INDEX__][id]" value="">

                            <div class="field">
                                <label for="module-title-__INDEX__">عنوان الوحدة <span class="req">*</span></label>
                                <input id="module-title-__INDEX__" name="modules[__INDEX__][title]" class="control" required>
                            </div>

                            <div class="field">
                                <label for="module-hours-__INDEX__">الساعات</label>
                                <input id="module-hours-__INDEX__" name="modules[__INDEX__][hours]" type="number" step="0.01" min="0" class="control">
                            </div>

                            <div class="field">
                                <label for="module-order-__INDEX__">الترتيب</label>
                                <input id="module-order-__INDEX__" name="modules[__INDEX__][sort_order]" type="number" min="0" class="control">
                            </div>

                            <div class="field">
                                <label>&nbsp;</label>
                                <button type="button" class="btn btn-ghost btn-sm" data-repeat-remove>
                                    <i class="fa-solid fa-trash"></i> حذف
                                </button>
                            </div>

                            <div class="field full">
                                <label for="module-description-__INDEX__">وصف الوحدة</label>
                                <textarea id="module-description-__INDEX__" name="modules[__INDEX__][description]" class="control"></textarea>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="actions" style="margin-block-start:10px">
                    <button type="button" class="btn btn-outline btn-sm" data-repeat-add>
                        <i class="fa-solid fa-plus"></i> إضافة وحدة
                    </button>
                </div>
            </div>
        </x-card>

        <x-card title="شروط الالتحاق (المعايير)">
            <p class="muted" style="margin-block-start:0">
                تُستخدم هذه الشروط لاحقًا في تقييم أهلية المتدرب عند الترشيح. يمكن ترك القسم فارغًا.
            </p>

            <div data-repeat-group>
                <div class="repeat-list" data-repeat>
                    @foreach ($criteriaRows as $i => $row)
                        <div class="repeat-row repeat-row--criteria" data-repeat-row>
                            <div class="field">
                                <label for="criteria-type-{{ $i }}">نوع المعيار <span class="req">*</span></label>
                                <select id="criteria-type-{{ $i }}" name="criteria[{{ $i }}][criteria_type_id]" class="control">
                                    <option value="">اختر...</option>
                                    @foreach ($criteriaTypes as $typeId => $typeName)
                                        <option value="{{ $typeId }}" @selected((string) ($row['criteria_type_id'] ?? '') === (string) $typeId)>{{ $typeName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="field">
                                <label for="criteria-operator-{{ $i }}">العملية</label>
                                <select id="criteria-operator-{{ $i }}" name="criteria[{{ $i }}][operator]" class="control">
                                    @foreach ($operators as $operatorValue => $operatorLabel)
                                        <option value="{{ $operatorValue }}" @selected((string) ($row['operator'] ?? 'eq') === (string) $operatorValue)>{{ $operatorLabel }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="field">
                                <label for="criteria-value-{{ $i }}">القيمة</label>
                                <input id="criteria-value-{{ $i }}" name="criteria[{{ $i }}][value]" class="control"
                                       value="{{ $row['value'] ?? '' }}">
                            </div>

                            <div class="field">
                                <label>إلزامي</label>
                                <label class="check" style="font-weight:400;padding-block:10px">
                                    <input type="hidden" name="criteria[{{ $i }}][is_mandatory]" value="0">
                                    <input type="checkbox" name="criteria[{{ $i }}][is_mandatory]" value="1"
                                           @checked((bool) ($row['is_mandatory'] ?? true))>
                                    نعم
                                </label>
                            </div>

                            <div class="field">
                                <label>&nbsp;</label>
                                <button type="button" class="btn btn-ghost btn-sm" data-repeat-remove>
                                    <i class="fa-solid fa-trash"></i> حذف
                                </button>
                            </div>
                        </div>
                    @endforeach

                    <template data-repeat-template>
                        <div class="repeat-row repeat-row--criteria" data-repeat-row>
                            <div class="field">
                                <label for="criteria-type-__INDEX__">نوع المعيار <span class="req">*</span></label>
                                <select id="criteria-type-__INDEX__" name="criteria[__INDEX__][criteria_type_id]" class="control">
                                    <option value="">اختر...</option>
                                    @foreach ($criteriaTypes as $typeId => $typeName)
                                        <option value="{{ $typeId }}">{{ $typeName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="field">
                                <label for="criteria-operator-__INDEX__">العملية</label>
                                <select id="criteria-operator-__INDEX__" name="criteria[__INDEX__][operator]" class="control">
                                    @foreach ($operators as $operatorValue => $operatorLabel)
                                        <option value="{{ $operatorValue }}">{{ $operatorLabel }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="field">
                                <label for="criteria-value-__INDEX__">القيمة</label>
                                <input id="criteria-value-__INDEX__" name="criteria[__INDEX__][value]" class="control">
                            </div>

                            <div class="field">
                                <label>إلزامي</label>
                                <label class="check" style="font-weight:400;padding-block:10px">
                                    <input type="hidden" name="criteria[__INDEX__][is_mandatory]" value="0">
                                    <input type="checkbox" name="criteria[__INDEX__][is_mandatory]" value="1" checked>
                                    نعم
                                </label>
                            </div>

                            <div class="field">
                                <label>&nbsp;</label>
                                <button type="button" class="btn btn-ghost btn-sm" data-repeat-remove>
                                    <i class="fa-solid fa-trash"></i> حذف
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="actions" style="margin-block-start:10px">
                    <button type="button" class="btn btn-outline btn-sm" data-repeat-add>
                        <i class="fa-solid fa-plus"></i> إضافة معيار
                    </button>
                </div>
            </div>
        </x-card>

        <x-card title="المتطلبات السابقة">
            <div class="field">
                <label for="prerequisites">الدورات التي يجب اجتيازها قبل هذه الدورة</label>
                <select id="prerequisites" name="prerequisites[]" multiple class="control" style="min-height:160px">
                    @forelse ($allCourses as $option)
                        <option value="{{ $option->id }}" @selected(in_array((int) $option->id, $selectedPrerequisites, true))>
                            {{ $option->code }} — {{ $option->title }}
                        </option>
                    @empty
                        <option value="" disabled>لا توجد دورات أخرى متاحة بعد.</option>
                    @endforelse
                </select>
                <div class="hint">اختر متطلبًا واحدًا أو أكثر (Ctrl/Cmd للاختيار المتعدد). يمكن تركها فارغة.</div>
                @error('prerequisites')<div class="error">{{ $message }}</div>@enderror
                @error('prerequisites.*')<div class="error">{{ $message }}</div>@enderror
            </div>
        </x-card>

        <div class="actions" style="justify-content:flex-end">
            <a class="btn btn-ghost" href="{{ $editing ? route('courses.show', $course) : route('courses.index') }}">إلغاء</a>
            <button type="submit" class="btn">
                <i class="fa-solid fa-floppy-disk"></i>
                {{ $editing ? 'حفظ التعديلات' : 'إضافة الدورة' }}
            </button>
        </div>
    </div>
</form>
