<?php

namespace App\Http\Controllers;

use App\Http\Requests\CertificateTemplateRequest;
use App\Models\CertificateTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CertificateTemplateController extends Controller
{
    public function index(Request $request): View
    {
        $term = trim((string) $request->input('q'));

        $templates = CertificateTemplate::query()
            ->withCount('certificates')
            ->when($term !== '', fn ($query) => $query->where('name', 'like', '%'.$term.'%'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('certificate-templates.index', ['templates' => $templates]);
    }

    public function create(): View
    {
        return view('certificate-templates.create', ['template' => null]);
    }

    public function store(CertificateTemplateRequest $request): RedirectResponse
    {
        $template = CertificateTemplate::create([
            'name' => $request->validated('name'),
            'template_html' => $request->validated('template_html'),
            'file_path' => $request->validated('file_path'),
            'is_active' => (bool) $request->validated('is_active', true),
        ]);

        return redirect()
            ->route('certificates.templates.edit', $template)
            ->with('success', 'تم إنشاء قالب الشهادة.');
    }

    public function edit(CertificateTemplate $template): View
    {
        return view('certificate-templates.edit', ['template' => $template]);
    }

    public function update(CertificateTemplateRequest $request, CertificateTemplate $template): RedirectResponse
    {
        $template->update([
            'name' => $request->validated('name'),
            'template_html' => $request->validated('template_html'),
            'file_path' => $request->validated('file_path'),
            'is_active' => (bool) $request->validated('is_active', false),
        ]);

        return redirect()
            ->route('certificates.templates.edit', $template)
            ->with('success', 'تم تحديث قالب الشهادة.');
    }

    public function destroy(CertificateTemplate $template): RedirectResponse
    {
        if ($template->certificates()->exists()) {
            return back()->with('error', 'لا يمكن حذف قالب مرتبط بشهادات صادرة.');
        }

        $template->delete();

        return redirect()
            ->route('certificates.templates.index')
            ->with('success', 'تم حذف قالب الشهادة.');
    }
}
