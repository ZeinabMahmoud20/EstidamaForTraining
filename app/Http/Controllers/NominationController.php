<?php

namespace App\Http\Controllers;

use App\Enums\NominationSource;
use App\Enums\NominationStatus;
use App\Enums\SessionStatus;
use App\Http\Requests\StoreNominationRequest;
use App\Http\Requests\TransitionNominationRequest;
use App\Http\Requests\UpdateNominationRequest;
use App\Models\CourseSession;
use App\Models\Nomination;
use App\Models\Trainee;
use App\Models\TrainingProvider;
use App\Services\NominationEligibilityService;
use App\Services\NominationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NominationController extends Controller
{
    public function __construct(
        private readonly NominationService $nominations,
        private readonly NominationEligibilityService $eligibility,
    ) {}

    public function index(Request $request): View
    {
        $term = trim((string) $request->input('q'));

        $nominations = Nomination::query()
            ->with(['session.course', 'trainee', 'nominatedByProvider', 'decisionBy'])
            ->withCount('nominationStatusHistory')
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.$term.'%';
                $query->whereHas('trainee', fn ($q) => $q
                    ->where('full_name', 'like', $like)
                    ->orWhere('national_id', 'like', $like));
            })
            ->when($request->filled('session_id'), fn ($q) => $q->where('session_id', $request->input('session_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('source'), fn ($q) => $q->where('source', $request->input('source')))
            ->when($request->filled('provider_id'), fn ($q) => $q->where('nominated_by_provider_id', $request->input('provider_id')))
            ->orderByDesc('application_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('nominations.index', [
            'nominations' => $nominations,
            ...$this->formData(),
            'statuses' => NominationStatus::options(),
            'sources' => NominationSource::options(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('nominations.create', [
            ...$this->formData(),
            'sources' => NominationSource::options(),
            'selectedSession' => $request->input('session_id'),
            'selectedTrainee' => $request->input('trainee_id'),
        ]);
    }

    public function store(StoreNominationRequest $request): RedirectResponse
    {
        $nomination = $this->nominations->create($request->validated(), $request->user());

        return redirect()
            ->route('nominations.show', $nomination)
            ->with('success', 'تم إنشاء الترشيح بنجاح.');
    }

    public function show(Nomination $nomination): View
    {
        $nomination->load([
            'session.course',
            'trainee.educationLevel',
            'trainee.jobGrade',
            'trainee.traineeWarnings',
            'trainee.enrollments.session.course',
            'nominatedByProvider',
            'decisionBy',
            'nominationStatusHistory' => fn ($query) => $query->orderByDesc('changed_at')->orderByDesc('id'),
            'nominationStatusHistory.changedBy',
            'enrollment',
        ]);

        $assessment = $this->eligibility->evaluate($nomination->session, $nomination->trainee);
        $user = request()->user();

        $nextStatuses = collect($nomination->status->nextStatuses())
            ->filter(fn (NominationStatus $status) => match ($status) {
                NominationStatus::UnderReview => $user?->can('REVIEW_NOMINATION'),
                NominationStatus::Approved,
                NominationStatus::Rejected,
                NominationStatus::Waitlisted => $user?->can('APPROVE_NOMINATION'),
                NominationStatus::Withdrawn => $user?->can('REVIEW_NOMINATION') || $user?->can('APPROVE_NOMINATION'),
                default => false,
            })
            ->mapWithKeys(fn (NominationStatus $status) => [$status->value => $status->label()])
            ->all();

        return view('nominations.show', [
            'nomination' => $nomination,
            'assessment' => $assessment,
            'nextStatuses' => $nextStatuses,
            'canOverride' => (bool) $user?->can('OVERRIDE_CRITERIA'),
        ]);
    }

    public function edit(Nomination $nomination): View
    {
        return view('nominations.edit', [
            'nomination' => $nomination,
            ...$this->formData(),
            'sources' => NominationSource::options(),
        ]);
    }

    public function update(UpdateNominationRequest $request, Nomination $nomination): RedirectResponse
    {
        $this->nominations->update($nomination, $request->validated(), $request->user());

        return redirect()
            ->route('nominations.show', $nomination)
            ->with('success', 'تم تحديث الترشيح بنجاح.');
    }

    public function transition(TransitionNominationRequest $request, Nomination $nomination): RedirectResponse
    {
        $this->nominations->transition(
            $nomination,
            NominationStatus::from($request->validated('status')),
            $request->validated('reason'),
            $request->user(),
            $request->boolean('override'),
        );

        return redirect()
            ->route('nominations.show', $nomination)
            ->with('success', 'تم تحديث حالة الترشيح بنجاح.');
    }

    public function destroy(Nomination $nomination): RedirectResponse
    {
        try {
            $this->nominations->delete($nomination);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('nominations.show', $nomination)
                ->with('error', $exception->validator->errors()->first());
        }

        return redirect()
            ->route('nominations.index')
            ->with('success', 'تم حذف الترشيح بنجاح.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        $sessions = CourseSession::query()
            ->with('course:id,title')
            ->whereNotIn('status', [SessionStatus::Cancelled, SessionStatus::Completed])
            ->orderByDesc('start_date')
            ->get()
            ->mapWithKeys(fn (CourseSession $session) => [
                $session->id => $session->session_code.' — '.($session->course?->title ?? 'بدون دورة')
                    .' ('.($session->start_date?->format('Y-m-d') ?? '—').')',
            ])
            ->all();

        $trainees = Trainee::query()
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get()
            ->mapWithKeys(fn (Trainee $trainee) => [
                $trainee->id => $trainee->full_name.' — '.$trainee->national_id,
            ])
            ->all();

        $providers = TrainingProvider::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();

        return [
            'sessions' => $sessions,
            'trainees' => $trainees,
            'providers' => $providers,
        ];
    }
}
