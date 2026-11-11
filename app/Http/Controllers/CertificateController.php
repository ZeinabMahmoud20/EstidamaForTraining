<?php

namespace App\Http\Controllers;

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use App\Http\Requests\RevokeCertificateRequest;
use App\Http\Requests\StoreCertificateRequest;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\CourseSession;
use App\Models\Enrollment;
use App\Services\CertificateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CertificateController extends Controller
{
    public function __construct(private readonly CertificateService $certificates) {}

    public function index(Request $request): View
    {
        $term = trim((string) $request->input('q'));

        $certificates = Certificate::query()
            ->with(['enrollment.trainee', 'enrollment.session.course', 'template'])
            ->when($term !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('certificate_no', 'like', '%'.$term.'%')
                ->orWhereHas('enrollment.trainee', fn ($trainee) => $trainee
                    ->where('full_name', 'like', '%'.$term.'%')
                    ->orWhere('national_id', 'like', '%'.$term.'%'))))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('certificate_type'), fn ($q) => $q->where('certificate_type', $request->input('certificate_type')))
            ->when($request->filled('session_id'), fn ($q) => $q->whereHas('enrollment', fn ($enrollment) => $enrollment
                ->where('session_id', $request->input('session_id'))))
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('certificates.index', [
            'certificates' => $certificates,
            'statuses' => CertificateStatus::options(),
            'types' => CertificateType::options(),
            'sessions' => CourseSession::query()->orderByDesc('start_date')->pluck('session_code', 'id')->all(),
        ]);
    }

    public function create(): View
    {
        return view('certificates.create', [
            'enrollments' => Enrollment::query()
                ->with(['trainee', 'session.course'])
                ->whereDoesntHave('certificate')
                ->whereNotIn('status', ['withdrawn', 'dismissed'])
                ->orderByDesc('id')
                ->get()
                ->mapWithKeys(fn (Enrollment $enrollment) => [
                    $enrollment->id => ($enrollment->trainee?->full_name ?? 'متدرب محذوف')
                        .' — '.($enrollment->session?->session_code ?? '—')
                        .' — '.($enrollment->session?->course?->title ?? '—'),
                ])
                ->all(),
            'templates' => CertificateTemplate::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all(),
            'types' => CertificateType::options(),
        ]);
    }

    public function store(StoreCertificateRequest $request): RedirectResponse
    {
        $certificate = $this->certificates->issue($request->validated(), $request->user());

        return redirect()
            ->route('certificates.show', $certificate)
            ->with('success', 'تم إصدار الشهادة بنجاح: '.$certificate->certificate_no);
    }

    public function show(Certificate $certificate): View
    {
        $certificate->load([
            'enrollment.trainee',
            'enrollment.session.course',
            'enrollment.session.provider',
            'template',
            'issuedBy',
            'revokedBy',
        ]);

        return view('certificates.show', ['certificate' => $certificate]);
    }

    public function print(Certificate $certificate): View
    {
        $certificate->load(['enrollment.trainee', 'enrollment.session.course', 'template', 'issuedBy']);

        return view('certificates.print', [
            'certificate' => $certificate,
            'renderedHtml' => $this->renderTemplate($certificate),
        ]);
    }

    public function own(Request $request): View
    {
        $trainee = $request->user()?->trainee;

        $certificates = $trainee
            ? Certificate::query()
                ->with(['enrollment.session.course', 'template'])
                ->whereHas('enrollment', fn ($query) => $query->where('trainee_id', $trainee->id))
                ->orderByDesc('issue_date')
                ->get()
            : collect();

        return view('certificates.own', ['certificates' => $certificates]);
    }

    public function verify(string $code): View
    {
        $certificate = Certificate::query()
            ->with(['enrollment.trainee', 'enrollment.session.course', 'template'])
            ->where('verification_code', $code)
            ->first();

        return view('certificates.verify', [
            'certificate' => $certificate,
            'code' => $code,
        ]);
    }

    public function revoke(RevokeCertificateRequest $request, Certificate $certificate): RedirectResponse
    {
        $this->certificates->revoke($certificate, $request->validated('revoke_reason'), $request->user());

        return redirect()
            ->route('certificates.show', $certificate)
            ->with('success', 'تم إلغاء الشهادة.');
    }

    private function renderTemplate(Certificate $certificate): ?string
    {
        $html = $certificate->template?->template_html;

        if (blank($html)) {
            return null;
        }

        $enrollment = $certificate->enrollment;
        $session = $enrollment?->session;

        return str_replace(
            [
                '{{trainee_name}}', '{{national_id}}', '{{course_title}}', '{{session_code}}',
                '{{certificate_no}}', '{{certificate_type}}', '{{issue_date}}',
                '{{expiry_date}}', '{{verification_code}}', '{{provider_name}}',
            ],
            [
                $enrollment?->trainee?->full_name ?? '',
                $enrollment?->trainee?->national_id ?? '',
                $session?->course?->title ?? '',
                $session?->session_code ?? '',
                $certificate->certificate_no,
                $certificate->certificate_type?->label() ?? '',
                $certificate->issue_date?->format('Y-m-d') ?? '',
                $certificate->expiry_date?->format('Y-m-d') ?? '',
                $certificate->verification_code ?? '',
                $session?->provider?->name ?? '',
            ],
            $html,
        );
    }
}
