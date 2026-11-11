<?php

namespace App\Http\Controllers;

use App\Enums\DeliveryMode;
use App\Enums\SessionStatus;
use App\Enums\TrainerRole;
use App\Http\Requests\StoreSessionRequest;
use App\Http\Requests\UpdateSessionRequest;
use App\Models\Accreditation;
use App\Models\Branch;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Hall;
use App\Models\Trainer;
use App\Models\TrainingProvider;
use App\Services\CourseSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseSessionController extends Controller
{
    public function __construct(private readonly CourseSessionService $sessions) {}

    public function index(Request $request): View
    {
        $term = trim((string) $request->input('q'));

        $sessions = CourseSession::query()
            ->with(['course', 'branch', 'provider'])
            ->withCount(['sessionDays', 'sessionTrainers', 'enrollments', 'nominations'])
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.$term.'%';
                $query->where(fn ($q) => $q
                    ->where('session_code', 'like', $like)
                    ->orWhereHas('course', fn ($courseQuery) => $courseQuery
                        ->where('title', 'like', $like)
                        ->orWhere('code', 'like', $like)));
            })
            ->when($request->filled('course_id'), fn ($q) => $q->where('course_id', $request->input('course_id')))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->input('branch_id')))
            ->when($request->filled('provider_id'), fn ($q) => $q->where('provider_id', $request->input('provider_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('delivery_mode'), fn ($q) => $q->where('delivery_mode', $request->input('delivery_mode')))
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('sessions.index', [
            'sessions' => $sessions,
            'courses' => $this->courseOptions(),
            'branches' => Branch::orderBy('name')->pluck('name', 'id'),
            'providers' => $this->providerOptions(),
            'statuses' => SessionStatus::options(),
            'deliveryModes' => DeliveryMode::options(),
        ]);
    }

    public function create(): View
    {
        return view('sessions.create', $this->formData());
    }

    public function store(StoreSessionRequest $request): RedirectResponse
    {
        $session = $this->sessions->create($request->validated(), $request->user()?->id);

        return redirect()
            ->route('sessions.show', $session)
            ->with('success', 'تمت إضافة الجلسة بنجاح.');
    }

    public function show(CourseSession $session): View
    {
        $session->load([
            'course',
            'provider',
            'branch',
            'accreditation',
            'createdBy',
            'sessionDays' => fn ($query) => $query->orderBy('day_no')->orderBy('day_date'),
            'sessionDays.hall.branch',
            'sessionDays.hallChangeLog.oldHall',
            'sessionDays.hallChangeLog.newHall',
            'sessionDays.hallChangeLog.changedBy',
            'sessionTrainers.trainer.provider',
        ])->loadCount(['enrollments', 'nominations']);

        return view('sessions.show', [
            'session' => $session,
            'nextStatuses' => collect($session->status->nextStatuses())
                ->mapWithKeys(fn (SessionStatus $status) => [$status->value => $status->label()])
                ->all(),
            'hallOptions' => $this->hallOptions(),
        ]);
    }

    public function edit(CourseSession $session): View
    {
        $session->load([
            'sessionDays' => fn ($query) => $query->orderBy('day_no')->orderBy('day_date'),
            'sessionTrainers.trainer',
        ]);

        return view('sessions.edit', [
            'session' => $session,
            ...$this->formData(),
        ]);
    }

    public function update(UpdateSessionRequest $request, CourseSession $session): RedirectResponse
    {
        $this->sessions->update($session, $request->validated(), $request->user()?->id);

        return redirect()
            ->route('sessions.show', $session)
            ->with('success', 'تم تحديث الجلسة بنجاح.');
    }

    public function destroy(CourseSession $session): RedirectResponse
    {
        if ($blockers = $this->sessions->deletionBlockers($session)) {
            return redirect()
                ->route('sessions.show', $session)
                ->with('error', 'لا يمكن حذف الجلسة لارتباطها بـ: '.implode('، ', $blockers).'.');
        }

        $this->sessions->delete($session);

        return redirect()
            ->route('sessions.index')
            ->with('success', 'تم حذف الجلسة بنجاح.');
    }

    /**
     * Lookups required by the create/edit form.
     *
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'courses' => $this->courseOptions(),
            'providers' => $this->providerOptions(),
            'branches' => Branch::orderBy('name')->pluck('name', 'id'),
            'accreditations' => Accreditation::orderBy('name')->pluck('name', 'id'),
            'deliveryModes' => DeliveryMode::options(),
            'trainerRoles' => TrainerRole::options(),
            'hallOptions' => $this->hallOptions(),
            'trainerOptions' => $this->trainerOptions(),
        ];
    }

    /** @return array<int, string> */
    private function courseOptions(): array
    {
        return Course::orderBy('title')
            ->get(['id', 'code', 'title'])
            ->mapWithKeys(fn (Course $course) => [$course->id => $course->code.' — '.$course->title])
            ->all();
    }

    /** @return array<int, string> */
    private function providerOptions(): array
    {
        return TrainingProvider::orderBy('name')
            ->get()
            ->mapWithKeys(fn (TrainingProvider $provider) => [
                $provider->id => $provider->name.($provider->is_active ? '' : ' (غير نشطة)'),
            ])
            ->all();
    }

    /** @return array<int, string> */
    private function hallOptions(): array
    {
        return Hall::with('branch')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Hall $hall) => [
                $hall->id => $hall->name
                    .' — '.($hall->branch?->name ?? 'بدون فرع')
                    .($hall->is_active ? '' : ' (غير نشطة)'),
            ])
            ->all();
    }

    /** @return array<int, string> */
    private function trainerOptions(): array
    {
        return Trainer::with('provider')
            ->orderBy('full_name')
            ->get()
            ->mapWithKeys(fn (Trainer $trainer) => [
                $trainer->id => $trainer->full_name
                    .' — '.($trainer->trainer_type?->label() ?? 'مدرب')
                    .($trainer->provider ? ' ('.$trainer->provider->name.')' : ''),
            ])
            ->all();
    }
}
