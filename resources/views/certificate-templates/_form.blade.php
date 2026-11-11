@php($editing = isset($template) && $template && $template->exists)

<form method="POST" action="{{ $editing ? route('certificates.templates.update', $template) : route('certificates.templates.store') }}">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <div class="grid">
        <x-card title="بيانات القالب">
            <div class="form-grid">
                <x-form.input name="name" label="اسم القالب" required
                              :value="$editing ? $template->name : null" />
                <x-form.input name="file_path" label="مسار ملف خلفية (اختياري)"
                              :value="$editing ? $template->file_path : null"
                              placeholder="images/certificate-bg.png" />

                <div class="field">
                    <label>&nbsp;</label>
                    <input type="hidden" name="is_active" value="0">
                    <label style="font-weight:600;display:flex;gap:8px;align-items:center">
                        <input type="checkbox" name="is_active" value="1"
                               @checked((bool) old('is_active', $editing ? $template->is_active : true))>
                        قالب مفعّل
                    </label>
                </div>
            </div>
        </x-card>

        <x-card title="محتوى القالب (HTML)">
            <p class="muted" style="margin-block-start:0">
                يمكنك استخدام المتغيرات التالية داخل HTML وسيتم استبدالها عند الطباعة:
                <code>@{{trainee_name}}</code>،
                <code>@{{national_id}}</code>،
                <code>@{{course_title}}</code>،
                <code>@{{session_code}}</code>،
                <code>@{{certificate_no}}</code>،
                <code>@{{certificate_type}}</code>،
                <code>@{{issue_date}}</code>،
                <code>@{{expiry_date}}</code>،
                <code>@{{verification_code}}</code>،
                <code>@{{provider_name}}</code>.
            </p>

            <div class="field">
                <label for="template_html">محتوى HTML</label>
                <textarea id="template_html" name="template_html" class="control" rows="16"
                          style="font-family:monospace;direction:ltr;text-align:left">{{ $editing ? $template->template_html : old('template_html') }}</textarea>
                @error('template_html')<div class="error">{{ $message }}</div>@enderror
            </div>
        </x-card>

        <div class="actions" style="justify-content:flex-end">
            <a class="btn btn-ghost" href="{{ route('certificates.templates.index') }}">إلغاء</a>
            <button type="submit" class="btn">
                <i class="fa-solid fa-floppy-disk"></i> {{ $editing ? 'حفظ التعديلات' : 'إنشاء القالب' }}
            </button>
        </div>
    </div>
</form>
