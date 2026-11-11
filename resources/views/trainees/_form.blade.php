@php
    $editing = isset($trainee) && $trainee && $trainee->exists;

    $providerRows = old('providers');
    if ($providerRows === null) {
        $providerRows = $editing
            ? $trainee->traineeProviders->map(fn ($affiliation) => [
                'provider_id' => $affiliation->provider_id,
                'job_title_at_provider' => $affiliation->job_title_at_provider,
                'from_date' => $affiliation->from_date?->format('Y-m-d'),
                'to_date' => $affiliation->to_date?->format('Y-m-d'),
                'is_primary' => $affiliation->is_primary,
            ])->values()->all()
            : [];
    }

    $providerRows = array_values((array) $providerRows);
@endphp

<form method="POST"
      action="{{ $editing ? route('trainees.update', $trainee) : route('trainees.store') }}">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <div class="grid">
        <x-card title="البيانات الأساسية">
            <div class="form-grid">
                <x-form.input name="national_id" label="الرقم القومي"
                              :value="$editing ? $trainee->national_id : null" required
                              hint="مطلوب وفريد لكل متدرب." />

                <x-form.input name="full_name" label="اسم المتدرب"
                              :value="$editing ? $trainee->full_name : null" required />

                <x-form.select name="gender" label="النوع" :options="$genders" placeholder="بدون تحديد"
                               :selected="$editing ? $trainee->gender?->value : null" />

                <x-form.input name="birth_date" type="date" label="تاريخ الميلاد"
                              :value="$editing ? $trainee->birth_date?->format('Y-m-d') : null" />

                <x-form.input name="phone" label="الهاتف"
                              :value="$editing ? $trainee->phone : null" />

                <x-form.input name="email" type="email" label="البريد الإلكتروني"
                              :value="$editing ? $trainee->email : null" />

                <x-form.textarea name="address" label="العنوان" wrapper="full"
                                 :value="$editing ? $trainee->address : null" />

                <x-form.checkbox name="is_active" label="المتدرب نشط ويمكن ترشيحه"
                                 :checked="$editing ? $trainee->is_active : true" />
            </div>
        </x-card>

        <x-card title="المؤهل والعمل">
            <div class="form-grid">
                <x-form.select name="education_level_id" label="المستوى التعليمي" :options="$educationLevels"
                               placeholder="بدون تحديد"
                               :selected="$editing ? $trainee->education_level_id : null" />

                <x-form.input name="qualification" label="المؤهل"
                              :value="$editing ? $trainee->qualification : null" />

                <x-form.input name="years_experience" type="number" min="0" max="80" label="سنوات الخبرة"
                              :value="$editing ? $trainee->years_experience : null" />

                <x-form.input name="job_title" label="المسمى الوظيفي"
                              :value="$editing ? $trainee->job_title : null" />

                <x-form.select name="job_grade_id" label="الدرجة الوظيفية" :options="$jobGrades"
                               placeholder="بدون تحديد"
                               :selected="$editing ? $trainee->job_grade_id : null" />
            </div>
        </x-card>

        <x-card title="جهات التدريب المرتبطة">
            <p class="muted" style="margin-block-start:0">
                يمكن ربط المتدرب بجهة تدريب واحدة أو أكثر وتحديد الجهة الأساسية. اترك القسم فارغًا إذا لم يكن هناك ارتباط.
            </p>

            <div data-repeat-group>
                <div class="repeat-list" data-repeat>
                    @foreach ($providerRows as $i => $row)
                        <div class="repeat-row repeat-row--trainee-provider" data-repeat-row>
                            <div class="field">
                                <label for="tp-provider-{{ $i }}">جهة التدريب <span class="req">*</span></label>
                                <select id="tp-provider-{{ $i }}" name="providers[{{ $i }}][provider_id]"
                                        class="control @error("providers.$i.provider_id") is-invalid @enderror" required>
                                    <option value="">اختر...</option>
                                    @foreach ($providers as $providerId => $providerName)
                                        <option value="{{ $providerId }}"
                                            @selected((string) old("providers.$i.provider_id", $row['provider_id'] ?? '') === (string) $providerId)>
                                            {{ $providerName }}
                                        </option>
                                    @endforeach
                                </select>
                                @error("providers.$i.provider_id")<div class="error">{{ $message }}</div>@enderror
                            </div>

                            <div class="field">
                                <label for="tp-job-{{ $i }}">المسمى الوظيفي بالجهة</label>
                                <input id="tp-job-{{ $i }}" name="providers[{{ $i }}][job_title_at_provider]"
                                       class="control" value="{{ old("providers.$i.job_title_at_provider", $row['job_title_at_provider'] ?? '') }}">
                            </div>

                            <div class="field">
                                <label for="tp-from-{{ $i }}">من تاريخ</label>
                                <input id="tp-from-{{ $i }}" name="providers[{{ $i }}][from_date]" type="date"
                                       class="control @error("providers.$i.to_date") is-invalid @enderror"
                                       value="{{ old("providers.$i.from_date", $row['from_date'] ?? '') }}">
                            </div>

                            <div class="field">
                                <label for="tp-to-{{ $i }}">إلى تاريخ</label>
                                <input id="tp-to-{{ $i }}" name="providers[{{ $i }}][to_date]" type="date"
                                       class="control @error("providers.$i.to_date") is-invalid @enderror"
                                       value="{{ old("providers.$i.to_date", $row['to_date'] ?? '') }}">
                                @error("providers.$i.to_date")<div class="error">{{ $message }}</div>@enderror
                            </div>

                            <div class="field">
                                <label class="check" for="tp-primary-{{ $i }}" style="font-weight:400">
                                    <input type="hidden" name="providers[{{ $i }}][is_primary]" value="0">
                                    <input type="checkbox" id="tp-primary-{{ $i }}" name="providers[{{ $i }}][is_primary]"
                                           value="1" @checked((bool) old("providers.$i.is_primary", $row['is_primary'] ?? false))>
                                    الجهة الأساسية
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
                        <div class="repeat-row repeat-row--trainee-provider" data-repeat-row>
                            <div class="field">
                                <label for="tp-provider-__INDEX__">جهة التدريب <span class="req">*</span></label>
                                <select id="tp-provider-__INDEX__" name="providers[__INDEX__][provider_id]" class="control" required>
                                    <option value="">اختر...</option>
                                    @foreach ($providers as $providerId => $providerName)
                                        <option value="{{ $providerId }}">{{ $providerName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="field">
                                <label for="tp-job-__INDEX__">المسمى الوظيفي بالجهة</label>
                                <input id="tp-job-__INDEX__" name="providers[__INDEX__][job_title_at_provider]" class="control">
                            </div>

                            <div class="field">
                                <label for="tp-from-__INDEX__">من تاريخ</label>
                                <input id="tp-from-__INDEX__" name="providers[__INDEX__][from_date]" type="date" class="control">
                            </div>

                            <div class="field">
                                <label for="tp-to-__INDEX__">إلى تاريخ</label>
                                <input id="tp-to-__INDEX__" name="providers[__INDEX__][to_date]" type="date" class="control">
                            </div>

                            <div class="field">
                                <label class="check" for="tp-primary-__INDEX__" style="font-weight:400">
                                    <input type="hidden" name="providers[__INDEX__][is_primary]" value="0">
                                    <input type="checkbox" id="tp-primary-__INDEX__" name="providers[__INDEX__][is_primary]" value="1">
                                    الجهة الأساسية
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
                        <i class="fa-solid fa-plus"></i> إضافة جهة
                    </button>
                </div>

                @error('providers')<div class="error">{{ $message }}</div>@enderror
            </div>
        </x-card>
    </div>

    <div class="actions" style="justify-content:flex-end;margin-block-start:18px">
        <a class="btn btn-ghost" href="{{ $editing ? route('trainees.show', $trainee) : route('trainees.index') }}">إلغاء</a>
        <button type="submit" class="btn">
            <i class="fa-solid fa-floppy-disk"></i>
            {{ $editing ? 'حفظ التعديلات' : 'إضافة المتدرب' }}
        </button>
    </div>
</form>
