<?php

namespace App\Http\Controllers;

use App\Enums\EvalTarget;
use App\Models\CourseSession;
use App\Models\EvaluationForm;
use App\Models\EvaluationSubmission;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvaluationReportController extends Controller
{
    public function index(Request $request): View
    {
        $query = EvaluationSubmission::query()
            ->with([
                'form',
                'session.course',
                'evaluatorUser',
                'targetTrainer',
                'targetEnrollment.trainee',
                'targetHall',
            ])
            ->when($request->filled('form_id'), fn ($q) => $q->where('form_id', $request->input('form_id')))
            ->when($request->filled('session_id'), fn ($q) => $q->where('session_id', $request->input('session_id')))
            ->when($request->filled('target_type'), fn ($q) => $q->where('target_type', $request->input('target_type')));

        $summary = clone $query;

        $submissions = $query->orderByDesc('submitted_at')->orderByDesc('id')->paginate(20)->withQueryString();

        return view('evaluations.reports', [
            'submissions' => $submissions,
            'total' => $summary->count(),
            'average' => (clone $summary)->avg('total_score'),
            'forms' => EvaluationForm::query()->orderBy('title')->pluck('title', 'id')->all(),
            'sessions' => CourseSession::query()->orderByDesc('start_date')->pluck('session_code', 'id')->all(),
            'targets' => EvalTarget::options(),
        ]);
    }
}
