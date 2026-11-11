<form method="POST" action="{{ route('enrollments.update', $enrollment) }}">
    @csrf
    @method('PUT')

    <div class="grid grid-2">
        <x-card title="بيانات التسجيل">
            <div class="form-grid">
                <x-form.select name="status" label="الحالة" :options="$statuses" required
                               :selected="$enrollment->status?->value" />
                <x-form.input name="attendance_pct" type="number" min="0" max="100" step="0.01"
                              label="نسبة الحضور %" :value="$enrollment->attendance_pct" />
                <x-form.input name="final_score_pct" type="number" min="0" max="100" step="0.01"
                              label="الدرجة النهائية %" :value="$enrollment->final_score_pct" />
                <x-form.input name="completed_at" type="date" label="تاريخ الإتمام"
                              :value="$enrollment->completed_at?->format('Y-m-d')" />

                <div class="field">
                    <label for="is_passed">النتيجة</label>
                    <select id="is_passed" name="is_passed" class="control">
                        <option value="" @selected($enrollment->is_passed === null)>غير محددة</option>
                        <option value="1" @selected($enrollment->is_passed === true)>ناجح</option>
                        <option value="0" @selected($enrollment->is_passed === false)>راسب</option>
                    </select>
                </div>

                <div class="field">
                    <label for="is_certificate_eligible">أهلية الشهادة</label>
                    <select id="is_certificate_eligible" name="is_certificate_eligible" class="control">
                        <option value="" @selected($enrollment->is_certificate_eligible === null)>غير محددة</option>
                        <option value="1" @selected($enrollment->is_certificate_eligible === true)>مؤهل</option>
                        <option value="0" @selected($enrollment->is_certificate_eligible === false)>غير مؤهل</option>
                    </select>
                </div>
            </div>
        </x-card>

        <x-card title="ملخص الجلسة">
            <dl class="details">
                <div><dt>المتدرب</dt><dd>{{ $enrollment->trainee?->full_name ?? '—' }}</dd></div>
                <div><dt>الجلسة</dt><dd class="num">{{ $enrollment->session?->session_code ?? '—' }}</dd></div>
                <div><dt>الدورة</dt><dd>{{ $enrollment->session?->course?->title ?? '—' }}</dd></div>
                <div><dt>الترشيح</dt><dd><a href="{{ route('nominations.show', $enrollment->nomination_id) }}">عرض الترشيح</a></dd></div>
            </dl>

            @if ($enrollment->attendance_pct === null)
                <div class="alert alert-info" style="margin-block-start:12px">
                    يمكن احتساب نسبة الحضور تلقائيًا من صفحة التسجيل بعد تسجيل الحضور.
                </div>
            @endif
        </x-card>
    </div>

    <div class="actions" style="justify-content:flex-end;margin-block-start:18px">
        <a class="btn btn-ghost" href="{{ route('enrollments.show', $enrollment) }}">إلغاء</a>
        <button type="submit" class="btn"><i class="fa-solid fa-floppy-disk"></i> حفظ التعديلات</button>
    </div>
</form>
