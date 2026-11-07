@php
    $editing = isset($trainer) && $trainer && $trainer->exists;

    $certificationRows = old('certifications');
    if ($certificationRows === null) {
        $certificationRows = $editing
            ? $trainer->trainerCertifications->map(fn ($certification) => [
                'title' => $certification->title,
                'issuer' => $certification->issuer,
                'issue_date' => $certification->issue_date?->format('Y-m-d'),
                'expiry_date' => $certification->expiry_date?->format('Y-m-d'),
            ])->values()->all()
            : [];
    }

    $experienceRows = old('experiences');
    if ($experienceRows === null) {
        $experienceRows = $editing
            ? $trainer->trainerExperiences->map(fn ($experience) => [
                'organization' => $experience->organization,
                'position' => $experience->position,
                'from_date' => $experience->from_date?->format('Y-m-d'),
                'to_date' => $experience->to_date?->format('Y-m-d'),
                'description' => $experience->description,
            ])->values()->all()
            : [];
    }

    $selectedSpecialties = array_map(
        'intval',
        (array) old('specialties', $editing ? $trainer->courseCategories->pluck('id')->all() : [])
    );
@endphp

<form method="POST"
      action="{{ $editing ? route('trainers.update', $trainer) : route('trainers.store') }}">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <div class="grid">
        <x-card title="البيانات الأساسية">
            <div class="form-grid">
                <x-form.input name="full_name" label="اسم المدرب" :value="$editing ? $trainer->full_name : null" required />

                <x-form.select name="trainer_type" label="نوع المدرب" :options="$trainerTypes" required
                               :selected="$editing ? $trainer->trainer_type?->value : 'internal'" />

                <x-form.select name="provider_id" label="جهة التدريب" :options="$providers" placeholder="بدون جهة"
                               :selected="$editing ? $trainer->provider_id : null"
                               :hint="$providers->isEmpty() ? 'لا توجد جهات تدريب مسجّلة بعد؛ يمكن ترك الحقل فارغًا أو إضافتها لاحقًا.' : null" />

                <x-form.select name="branch_id" label="الفرع" :options="$branches" placeholder="بدون فرع"
                               :selected="$editing ? $trainer->branch_id : null" />

                <x-form.input name="national_id" label="الرقم القومي" :value="$editing ? $trainer->national_id : null"
                              hint="اختياري، لكنه فريد إن تم إدخاله." />

                <x-form.select name="gender" label="النوع" :options="$genders" placeholder="بدون تحديد"
                               :selected="$editing ? $trainer->gender?->value : null" />

                <x-form.input name="birth_date" type="date" label="تاريخ الميلاد"
                              :value="$editing ? $trainer->birth_date?->format('Y-m-d') : null" />

                <x-form.input name="phone" label="الهاتف" :value="$editing ? $trainer->phone : null" />

                <x-form.input name="email" type="email" label="البريد الإلكتروني"
                              :value="$editing ? $trainer->email : null" />

                <x-form.input name="highest_qualification" label="أعلى مؤهل"
                              :value="$editing ? $trainer->highest_qualification : null" />

                <x-form.input name="years_experience" type="number" min="0" max="80" label="سنوات الخبرة"
                              :value="$editing ? $trainer->years_experience : null" />

                <x-form.input name="hourly_rate" type="number" step="0.01" min="0" label="سعر الساعة (ج.م)"
                              :value="$editing ? $trainer->hourly_rate : null" />

                <x-form.input name="bank_account" label="الحساب البنكي"
                              :value="$editing ? $trainer->bank_account : null" />

                <x-form.checkbox name="is_active" label="المدرب نشط ومتاح للترشيح"
                                 :checked="$editing ? $trainer->is_active : true" />

                <x-form.textarea name="address" label="العنوان" wrapper="full"
                                 :value="$editing ? $trainer->address : null" />

                <x-form.textarea name="bio" label="نبذة عن المدرب" wrapper="full"
                                 :value="$editing ? $trainer->bio : null" />
            </div>
        </x-card>

        <x-card title="التخصصات التدريبية">
            <div class="field">
                <label for="specialties">تصنيفات الدورات التي يتخصص فيها المدرب</label>
                <select id="specialties" name="specialties[]" multiple class="control" style="min-height:180px">
                    @forelse ($categories as $categoryId => $categoryName)
                        <option value="{{ $categoryId }}" @selected(in_array((int) $categoryId, $selectedSpecialties, true))>
                            {{ $categoryName }}
                        </option>
                    @empty
                        <option value="" disabled>لا توجد تصنيفات مسجّلة بعد.</option>
                    @endforelse
                </select>
                <div class="hint">اختر تخصصًا واحدًا أو أكثر (Ctrl/Cmd للاختيار المتعدد). يمكن تركها فارغة.</div>
                @error('specialties')<div class="error">{{ $message }}</div>@enderror
                @error('specialties.*')<div class="error">{{ $message }}</div>@enderror
            </div>
        </x-card>

        <x-card title="الشهادات المهنية">
            <p class="muted" style="margin-block-start:0">
                أضف شهادات المدرب مع الجهة المانحة وتواريخ الإصدار والانتهاء. يمكن ترك القسم فارغًا.
            </p>

            <div data-repeat-group>
                <div class="repeat-list" data-repeat>
                    @foreach ($certificationRows as $i => $row)
                        <div class="repeat-row repeat-row--cert" data-repeat-row>
                            <div class="field">
                                <label for="cert-title-{{ $i }}">اسم الشهادة <span class="req">*</span></label>
                                <input id="cert-title-{{ $i }}" name="certifications[{{ $i }}][title]"
                                       class="control @error("certifications.$i.title") is-invalid @enderror"
                                       value="{{ $row['title'] ?? '' }}" required>
                                @error("certifications.$i.title")<div class="error">{{ $message }}</div>@enderror
                            </div>

                            <div class="field">
                                <label for="cert-issuer-{{ $i }}">الجهة المانحة</label>
                                <input id="cert-issuer-{{ $i }}" name="certifications[{{ $i }}][issuer]" class="control"
                                       value="{{ $row['issuer'] ?? '' }}">
                            </div>

                            <div class="field">
                                <label for="cert-issue-{{ $i }}">تاريخ الإصدار</label>
                                <input id="cert-issue-{{ $i }}" name="certifications[{{ $i }}][issue_date]" type="date"
                                       class="control" value="{{ $row['issue_date'] ?? '' }}">
                            </div>

                            <div class="field">
                                <label for="cert-expiry-{{ $i }}">تاريخ الانتهاء</label>
                                <input id="cert-expiry-{{ $i }}" name="certifications[{{ $i }}][expiry_date]" type="date"
                                       class="control" value="{{ $row['expiry_date'] ?? '' }}">
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
                        <div class="repeat-row repeat-row--cert" data-repeat-row>
                            <div class="field">
                                <label for="cert-title-__INDEX__">اسم الشهادة <span class="req">*</span></label>
                                <input id="cert-title-__INDEX__" name="certifications[__INDEX__][title]" class="control" required>
                            </div>

                            <div class="field">
                                <label for="cert-issuer-__INDEX__">الجهة المانحة</label>
                                <input id="cert-issuer-__INDEX__" name="certifications[__INDEX__][issuer]" class="control">
                            </div>

                            <div class="field">
                                <label for="cert-issue-__INDEX__">تاريخ الإصدار</label>
                                <input id="cert-issue-__INDEX__" name="certifications[__INDEX__][issue_date]" type="date" class="control">
                            </div>

                            <div class="field">
                                <label for="cert-expiry-__INDEX__">تاريخ الانتهاء</label>
                                <input id="cert-expiry-__INDEX__" name="certifications[__INDEX__][expiry_date]" type="date" class="control">
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
                        <i class="fa-solid fa-plus"></i> إضافة شهادة
                    </button>
                </div>
            </div>
        </x-card>

        <x-card title="الخبرات العملية">
            <p class="muted" style="margin-block-start:0">
                سجّل الخبرات السابقة للمدرب. يمكن ترك القسم فارغًا.
            </p>

            <div data-repeat-group>
                <div class="repeat-list" data-repeat>
                    @foreach ($experienceRows as $i => $row)
                        <div class="repeat-row repeat-row--exp" data-repeat-row>
                            <div class="field">
                                <label for="exp-org-{{ $i }}">جهة العمل</label>
                                <input id="exp-org-{{ $i }}" name="experiences[{{ $i }}][organization]" class="control"
                                       value="{{ $row['organization'] ?? '' }}">
                            </div>

                            <div class="field">
                                <label for="exp-position-{{ $i }}">المسمى الوظيفي</label>
                                <input id="exp-position-{{ $i }}" name="experiences[{{ $i }}][position]" class="control"
                                       value="{{ $row['position'] ?? '' }}">
                            </div>

                            <div class="field">
                                <label for="exp-from-{{ $i }}">من تاريخ</label>
                                <input id="exp-from-{{ $i }}" name="experiences[{{ $i }}][from_date]" type="date"
                                       class="control" value="{{ $row['from_date'] ?? '' }}">
                            </div>

                            <div class="field">
                                <label for="exp-to-{{ $i }}">إلى تاريخ</label>
                                <input id="exp-to-{{ $i }}" name="experiences[{{ $i }}][to_date]" type="date"
                                       class="control" value="{{ $row['to_date'] ?? '' }}">
                            </div>

                            <div class="field">
                                <label>&nbsp;</label>
                                <button type="button" class="btn btn-ghost btn-sm" data-repeat-remove>
                                    <i class="fa-solid fa-trash"></i> حذف
                                </button>
                            </div>

                            <div class="field full">
                                <label for="exp-description-{{ $i }}">وصف الخبرة</label>
                                <textarea id="exp-description-{{ $i }}" name="experiences[{{ $i }}][description]" class="control">{{ $row['description'] ?? '' }}</textarea>
                            </div>
                        </div>
                    @endforeach

                    <template data-repeat-template>
                        <div class="repeat-row repeat-row--exp" data-repeat-row>
                            <div class="field">
                                <label for="exp-org-__INDEX__">جهة العمل</label>
                                <input id="exp-org-__INDEX__" name="experiences[__INDEX__][organization]" class="control">
                            </div>

                            <div class="field">
                                <label for="exp-position-__INDEX__">المسمى الوظيفي</label>
                                <input id="exp-position-__INDEX__" name="experiences[__INDEX__][position]" class="control">
                            </div>

                            <div class="field">
                                <label for="exp-from-__INDEX__">من تاريخ</label>
                                <input id="exp-from-__INDEX__" name="experiences[__INDEX__][from_date]" type="date" class="control">
                            </div>

                            <div class="field">
                                <label for="exp-to-__INDEX__">إلى تاريخ</label>
                                <input id="exp-to-__INDEX__" name="experiences[__INDEX__][to_date]" type="date" class="control">
                            </div>

                            <div class="field">
                                <label>&nbsp;</label>
                                <button type="button" class="btn btn-ghost btn-sm" data-repeat-remove>
                                    <i class="fa-solid fa-trash"></i> حذف
                                </button>
                            </div>

                            <div class="field full">
                                <label for="exp-description-__INDEX__">وصف الخبرة</label>
                                <textarea id="exp-description-__INDEX__" name="experiences[__INDEX__][description]" class="control"></textarea>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="actions" style="margin-block-start:10px">
                    <button type="button" class="btn btn-outline btn-sm" data-repeat-add>
                        <i class="fa-solid fa-plus"></i> إضافة خبرة
                    </button>
                </div>
            </div>
        </x-card>

        <div class="actions" style="justify-content:flex-end">
            <a class="btn btn-ghost" href="{{ $editing ? route('trainers.show', $trainer) : route('trainers.index') }}">إلغاء</a>
            <button type="submit" class="btn">
                <i class="fa-solid fa-floppy-disk"></i>
                {{ $editing ? 'حفظ التعديلات' : 'إضافة المدرب' }}
            </button>
        </div>
    </div>
</form>
