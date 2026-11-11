<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitEvaluationRequest;
use App\Models\CourseSession;
use App\Models\EvaluationForm;
use App\Services\EvaluationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvaluationSubmissionController extends Controller
{
    public function __construct(private readonly EvaluationService $evaluations) {}

    public function create(Request $request, EvaluationForm $evaluation): View
    {
        abort_unless($evaluation->is_active, 404);

        $evaluation->load([
            'evaluationSections' => fn ($query) => $query->orderBy('sort_order'),
            'evaluationSections.evaluationItems' => fn ($query) => $query->orderBy('sort_order'),
        ]);

        if (! $request->filled('session_id')) {
            return view('evaluations.submit-choose', [
                'form' => $evaluation,
                'sessions' => $this->sessionOptions(),
            ]);
        }

        $session = CourseSession::query()
            ->with('course')
            ->findOrFail((int) $request->input('session_id'));

        return view('evaluations.submit', [
            'form' => $evaluation,
            'session' => $session,
        ]);
    }

    public function store(SubmitEvaluationRequest $request, EvaluationForm $evaluation): RedirectResponse
    {
        abort_unless($evaluation->is_active, 404);

        $session = CourseSession::query()->findOrFail((int) $request->validated('session_id'));

        $this->evaluations->submit($evaluation, $session, $request->validated(), $request->user());

        return redirect()
            ->route('evaluations.index')
            ->with('success', 'تم إرسال التقييم بنجاح. شكرًا لمشاركتك.');
    }

    /** @return array<int, string> */
    private function sessionOptions(): array
    {
        return CourseSession::query()
            ->with('course')
            ->orderByDesc('start_date')
            ->get()
            ->mapWithKeys(fn (CourseSession $session) => [
                $session->id => $session->session_code.' — '.($session->course?->title ?? 'دورة محذوفة'),
            ])
            ->all();
    }
}
