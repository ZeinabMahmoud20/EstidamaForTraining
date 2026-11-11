@php
    $editing = isset($session) && $session && $session->exists;

    $dayRows = old('days');
    if ($dayRows === null) {
        $dayRows = $editing
            ? $session->sessionDays->map(fn ($day) => [
                'id' => $day->id,
                'day_no' => $day->day_no,
                'day_date' => $day->day_date?->format('Y-m-d'),
                'start_time' => substr((string) $day->start_time, 0, 5),
                'end_time' => substr((string) $day->end_time, 0, 5),
                'hall_id' => $day->hall_id,
                'online_url' => $day->online_url,
            ])->values()->all()
            : [];
    }

    $trainerRows = old('trainers');
    if ($trainerRows === null) {
        $trainerRows = $editing
            ? $session->sessionTrainers->map(fn ($sessionTrainer) => [
                'trainer_id' => $sessionTrainer->trainer_id,
                'planned_role' => $sessionTrainer->planned_role?->value,
            ])->values()->all()
            : [];
    }
@endphp

<form method="POST" action="{{ $editing ? route('sessions.update', $session) : route('sessions.store') }}">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <div class="grid">
        <x-card title="بيانات الجلسة">
            <div class="form-grid">
                <x-form.select name="course_id" label="الدورة" :options="$courses" required
                               :selected="$editing ? $session->course_id : null"
                               hint="الدورة التي ستنفذها هذه الجلسة." />

                <x-form.input name="session_code" label="كود الجلسة" :value="$editing ? $session->session_code : null" required
                              hint="كود فريد للجلسة، مثال: SES-2026-001." />

                <x-form.select name="provider_id" label="جهة التدريب" :options="$providers" placeholder="بدون جهة"
                               :selected="$editing ? $session->provider_id : null" />
                <x-form.select name="branch_id" label="الفرع" :options="$branches" placeholder="بدون فرع"
                               :selected="$editing ? $session->branch_id : null" />
                <x-form.select name="accreditation_id" label="الاعتماد" :options="$accreditations" placeholder="بدون اعتماد"
                               :selected="$editing ? $session->accreditation_id : null" />
                <x-form.select name="delivery_mode" label="طريقة التنفيذ" :options="$deliveryModes" required
                               :selected="$editing ? $session->delivery_mode?->value : 'onsite'" />

                <x-form.input name="online_url" type="url" label="رابط الجلسة" wrapper="full"
                              :value="$editing ? $session->online_url : null"
                              hint="مطلوب للجلسات عن بعد، ويمكن استخدامه أيضًا للجلسات المدمجة." />

                <x-form.input name="start_date" type="date" label="تاريخ البداية" required
                              :value="$editing ? $session->start_date?->format('Y-m-d') : null" />
                <x-form.input name="end_date" type="date" label="تاريخ النهاية" required
                              :value="$editing ? $session->end_date?->format('Y-m-d') : null" />

                <x-form.input name="registration_open" type="date" label="بداية التسجيل"
                              :value="$editing ? $session->registration_open?->format('Y-m-d') : null" />
                <x-form.input name="registration_close" type="date" label="نهاية التسجيل"
                              :value="$editing ? $session->registration_close?->format('Y-m-d') : null" />

                <x-form.input name="max_trainees" type="number" min="1" label="الحد الأقصى للمتدربين"
                              :value="$editing ? $session->max_trainees : null"
                              hint="اتركه فارغًا لاستخدام حد الدورة." />
                <x-form.input name="price" type="number" step="0.01" min="0" label="سعر الجلسة (ج.م)"
                              :value="$editing ? $session->price : null"
                              hint="اتركه فارغًا لاستخدام سعر الدورة." />
            </div>
        </x-card>

        <x-card title="أيام الجلسة والقاعات">
            <p class="muted" style="margin-block-start:0">
                حدّد لكل يوم تاريخه ووقته والقاعة. يمنع النظام تعارض القاعة مع جلسة أخرى أو فترة حجب إتاحة.
                اترك رقم اليوم فارغًا ليُرقّم تلقائيًا.
            </p>

            @if (empty($hallOptions))
                <p class="muted">لا توجد قاعات مسجّلة بعد. أضف القاعات من موديول «القاعات» أولًا.</p>
            @endif

            <div data-repeat-group>
                <div class="repeat-list" data-repeat>
                    @foreach ($dayRows as $i => $row)
                        <div class="repeat-row repeat-row--session-day" data-repeat-row>
                            <input type="hidden" name="days[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">

                            <div class="field">
                                <label for="day-no-{{ $i }}">رقم اليوم</label>
                                <input id="day-no-{{ $i }}" name="days[{{ $i }}][day_no]" type="number" min="1" max="60"
                                       class="control @error("days.$i.day_no") is-invalid @enderror"
                                       value="{{ $row['day_no'] ?? '' }}" placeholder="تلقائي">
                                @error("days.$i.day_no")<div class="error">{{ $message }}</div>@enderror
                            </div>

                            <div class="field">
                                <label for="day-date-{{ $i }}">التاريخ <span class="req">*</span></label>
                                <input id="day-date-{{ $i }}" name="days[{{ $i }}][day_date]" type="date"
                                       class="control @error("days.$i.day_date") is-invalid @enderror"
                                       value="{{ $row['day_date'] ?? '' }}" required>
                                @error("days.$i.day_date")<div class="error">{{ $message }}</div>@enderror
                            </div>

                            <div class="field">
                                <label for="day-start-{{ $i }}">من <span class="req">*</span></label>
                                <input id="day-start-{{ $i }}" name="days[{{ $i }}][start_time]" type="time"
                                       class="control @error("days.$i.start_time") is-invalid @enderror"
                                       value="{{ $row['start_time'] ?? '' }}" required>
                                @error("days.$i.start_time")<div class="error">{{ $message }}</div>@enderror
                            </div>

                            <div class="field">
                                <label for="day-end-{{ $i }}">إلى <span class="req">*</span></label>
                                <input id="day-end-{{ $i }}" name="days[{{ $i }}][end_time]" type="time"
                                       class="control @error("days.$i.end_time") is-invalid @enderror"
                                       value="{{ $row['end_time'] ?? '' }}" required>
                                @error("days.$i.end_time")<div class="error">{{ $message }}</div>@enderror
                            </div>

                            <div class="field">
                                <label for="day-hall-{{ $i }}">القاعة</label>
                                <select id="day-hall-{{ $i }}" name="days[{{ $i }}][hall_id]"
                                        class="control @error("days.$i.hall_id") is-invalid @enderror">
                                    <option value="">بدون قاعة</option>
                                    @foreach ($hallOptions as $hallId => $hallName)
                                        <option value="{{ $hallId }}" @selected((string) ($row['hall_id'] ?? '') === (string) $hallId)>
                                            {{ $hallName }}
                                        </option>
                                    @endforeach
                                </select>
                                @error("days.$i.hall_id")<div class="error">{{ $message }}</div>@enderror
                            </div>

                            <div class="field">
                                <label for="day-url-{{ $i }}">رابط اليوم</label>
                                <input id="day-url-{{ $i }}" name="days[{{ $i }}][online_url]" type="url"
                                       class="control @error("days.$i.online_url") is-invalid @enderror"
                                       value="{{ $row['online_url'] ?? '' }}" placeholder="اختياري">
                                @error("days.$i.online_url")<div class="error">{{ $message }}</div>@enderror
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
                        <div class="repeat-row repeat-row--session-day" data-repeat-row>
                            <input type="hidden" name="days[__INDEX__][id]" value="">

                            <div class="field">
                                <label for="day-no-__INDEX__">رقم اليوم</label>
                                <input id="day-no-__INDEX__" name="days[__INDEX__][day_no]" type="number" min="1" max="60"
                                       class="control" placeholder="تلقائي">
                            </div>

                            <div class="field">
                                <label for="day-date-__INDEX__">التاريخ <span class="req">*</span></label>
                                <input id="day-date-__INDEX__" name="days[__INDEX__][day_date]" type="date" class="control" required>
                            </div>

                            <div class="field">
                                <label for="day-start-__INDEX__">من <span class="req">*</span></label>
                                <input id="day-start-__INDEX__" name="days[__INDEX__][start_time]" type="time" class="control" required>
                            </div>

                            <div class="field">
                                <label for="day-end-__INDEX__">إلى <span class="req">*</span></label>
                                <input id="day-end-__INDEX__" name="days[__INDEX__][end_time]" type="time" class="control" required>
                            </div>

                            <div class="field">
                                <label for="day-hall-__INDEX__">القاعة</label>
                                <select id="day-hall-__INDEX__" name="days[__INDEX__][hall_id]" class="control">
                                    <option value="">بدون قاعة</option>
                                    @foreach ($hallOptions as $hallId => $hallName)
                                        <option value="{{ $hallId }}">{{ $hallName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="field">
                                <label for="day-url-__INDEX__">رابط اليوم</label>
                                <input id="day-url-__INDEX__" name="days[__INDEX__][online_url]" type="url" class="control" placeholder="اختياري">
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
                        <i class="fa-solid fa-plus"></i> إضافة يوم
                    </button>
                </div>
            </div>

            @error('days')<div class="error" style="margin-block-start:8px">{{ $message }}</div>@enderror
        </x-card>

        <x-card title="المدربون المخططون">
            <p class="muted" style="margin-block-start:0">
                حدّد المدربين المخططين للجلسة ودور كل مدرب. يمكن ترك القسم فارغًا.
            </p>

            @if (empty($trainerOptions))
                <p class="muted">لا يوجد مدربون مسجّلون بعد.</p>
            @endif

            <div data-repeat-group>
                <div class="repeat-list" data-repeat>
                    @foreach ($trainerRows as $i => $row)
                        <div class="repeat-row repeat-row--session-trainer" data-repeat-row>
                            <div class="field">
                                <label for="trainer-{{ $i }}">المدرب <span class="req">*</span></label>
                                <select id="trainer-{{ $i }}" name="trainers[{{ $i }}][trainer_id]"
                                        class="control @error("trainers.$i.trainer_id") is-invalid @enderror" required>
                                    <option value="">اختر المدرب...</option>
                                    @foreach ($trainerOptions as $trainerId => $trainerName)
                                        <option value="{{ $trainerId }}" @selected((string) ($row['trainer_id'] ?? '') === (string) $trainerId)>
                                            {{ $trainerName }}
                                        </option>
                                    @endforeach
                                </select>
                                @error("trainers.$i.trainer_id")<div class="error">{{ $message }}</div>@enderror
                            </div>

                            <div class="field">
                                <label for="trainer-role-{{ $i }}">الدور <span class="req">*</span></label>
                                <select id="trainer-role-{{ $i }}" name="trainers[{{ $i }}][planned_role]"
                                        class="control @error("trainers.$i.planned_role") is-invalid @enderror" required>
                                    <option value="">اختر...</option>
                                    @foreach ($trainerRoles as $roleValue => $roleLabel)
                                        <option value="{{ $roleValue }}" @selected((string) ($row['planned_role'] ?? '') === (string) $roleValue)>
                                            {{ $roleLabel }}
                                        </option>
                                    @endforeach
                                </select>
                                @error("trainers.$i.planned_role")<div class="error">{{ $message }}</div>@enderror
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
                        <div class="repeat-row repeat-row--session-trainer" data-repeat-row>
                            <div class="field">
                                <label for="trainer-__INDEX__">المدرب <span class="req">*</span></label>
                                <select id="trainer-__INDEX__" name="trainers[__INDEX__][trainer_id]" class="control" required>
                                    <option value="">اختر المدرب...</option>
                                    @foreach ($trainerOptions as $trainerId => $trainerName)
                                        <option value="{{ $trainerId }}">{{ $trainerName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="field">
                                <label for="trainer-role-__INDEX__">الدور <span class="req">*</span></label>
                                <select id="trainer-role-__INDEX__" name="trainers[__INDEX__][planned_role]" class="control" required>
                                    <option value="">اختر...</option>
                                    @foreach ($trainerRoles as $roleValue => $roleLabel)
                                        <option value="{{ $roleValue }}">{{ $roleLabel }}</option>
                                    @endforeach
                                </select>
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
                    <button type="button" class="btn btn-outline btn-sm" data-repeat-add @disabled(empty($trainerOptions))>
                        <i class="fa-solid fa-plus"></i> إضافة مدرب
                    </button>
                </div>
            </div>

            @error('trainers')<div class="error" style="margin-block-start:8px">{{ $message }}</div>@enderror
        </x-card>

        <div class="actions" style="justify-content:flex-end">
            <a class="btn btn-ghost" href="{{ $editing ? route('sessions.show', $session) : route('sessions.index') }}">إلغاء</a>
            <button type="submit" class="btn">
                <i class="fa-solid fa-floppy-disk"></i>
                {{ $editing ? 'حفظ التعديلات' : 'إضافة الجلسة' }}
            </button>
        </div>
    </div>
</form>
