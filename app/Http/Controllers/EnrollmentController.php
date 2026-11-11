<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Enums\NominationStatus;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Requests\UpdateEnrollmentRequest;
use App\Models\CourseSession;
use App\Models\Enrollment;
use App\Models\Nomination;
use App\Services\EnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    public function __construct(private readonly EnrollmentService $enrollments) {}

    public function index(Request $request): View
    {
        $term = trim((string) $request->input('q'));

        $enrollments = Enrollment::query()
            ->with(['session.course', 'trainee', 'nomination'])
            ->withCount(['traineeAttendance', 'examResults'])
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.$term.'%';
                $query->whereHas('trainee', fn ($q) => $q
                    ->where('full_name', 'like', $like)
                    ->orWhere('national_id', 'like', $like));
            })
            ->when($request->filled('session_id'), fn ($q) => $q->where('session_id', $request->input('session_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('is_passed'), fn ($q) => $q->where('is_passed', $request->boolean('is_passed')))
            ->when($request->filled('certificate'), fn ($q) => $q->where('is_certificate_eligible', $request->boolean('certificate')))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('enrollments.index', [
            'enrollments' => $enrollments,
            'sessions' => $this->sessionOptions(),
            'statuses' => EnrollmentStatus::options(),
        ]);
    }

    public function create(): View
    {
        return view('enrollments.create', [
            'nominations' => Nomination::query()
                ->with(['session.course', 'trainee'])
                ->where('status', NominationStatus::Approved)
                ->whereDoesntHave('enrollment')
                ->orderBy('id')
                ->get()
                ->mapWithKeys(fn (Nomination $nomination) => [
                    $nomination->id => ($nomination->trainee?->full_name ?? '—')
                        .' — '.($nomination->session?->session_code ?? '—')
                        .' — '.($nomination->session?->course?->title ?? '—'),
                ])
                ->all(),
            'statuses' => EnrollmentStatus::options(),
        ]);
    }

    public function store(StoreEnrollmentRequest $request): RedirectResponse
    {
        $nomination = Nomination::findOrFail($request->validated('nomination_id'));
        $enrollment = $this->enrollments->createFromNomination($nomination, $request->validated());

        return redirect()
            ->route('enrollments.show', $enrollment)
            ->with('success', 'تم إنشاء التسجيل بنجاح.');
    }

    public function show(Enrollment $enrollment): View
    {
        $enrollment->load([
            'session.course',
            'trainee',
            'nomination',
            'traineeAttendance' => fn ($query) => $query->orderByDesc('id'),
            'traineeAttendance.sessionDay',
            'examResults' => fn ($query) => $query->orderByDesc('id'),
            'examResults.exam',
            'evaluationSubmissions' => fn ($query) => $query->orderByDesc('id'),
            'evaluationSubmissions.form',
            'certificate',
        ])->loadCount(['traineeAttendance', 'examResults', 'evaluationSubmissions']);

        return view('enrollments.show', [
            'enrollment' => $enrollment,
            'statuses' => EnrollmentStatus::options(),
        ]);
    }

    public function edit(Enrollment $enrollment): View
    {
        $enrollment->load(['session.course', 'trainee', 'nomination']);

        return view('enrollments.edit', [
            'enrollment' => $enrollment,
            'statuses' => EnrollmentStatus::options(),
        ]);
    }

    public function update(UpdateEnrollmentRequest $request, Enrollment $enrollment): RedirectResponse
    {
        $this->enrollments->update($enrollment, $request->validated());

        return redirect()
            ->route('enrollments.show', $enrollment)
            ->with('success', 'تم تحديث التسجيل بنجاح.');
    }

    public function recalculate(Enrollment $enrollment): RedirectResponse
    {
        $this->enrollments->recalculateAttendance($enrollment);

        return redirect()
            ->route('enrollments.show', $enrollment)
            ->with('success', 'تم احتساب نسبة الحضور من سجلات الحضور.');
    }

    public function destroy(Enrollment $enrollment): RedirectResponse
    {
        try {
            $this->enrollments->delete($enrollment);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('enrollments.show', $enrollment)
                ->with('error', $exception->validator->errors()->first());
        }

        return redirect()
            ->route('enrollments.index')
            ->with('success', 'تم حذف التسجيل بنجاح.');
    }

    /**
     * @return array<int, string>
     */
    private function sessionOptions(): array
    {
        return CourseSession::query()
            ->with('course:id,title')
            ->orderByDesc('start_date')
            ->get()
            ->mapWithKeys(fn (CourseSession $session) => [
                $session->id => $session->session_code.' — '.($session->course?->title ?? 'بدون دورة'),
            ])
            ->all();
    }
}
