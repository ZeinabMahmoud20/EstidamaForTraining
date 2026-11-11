<?php

namespace App\Http\Controllers;

use App\Enums\Gender;
use App\Enums\WarningType;
use App\Http\Requests\StoreTraineeRequest;
use App\Http\Requests\UpdateTraineeRequest;
use App\Models\CourseSession;
use App\Models\EducationLevel;
use App\Models\JobGrade;
use App\Models\Trainee;
use App\Models\TrainingProvider;
use App\Services\TraineeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TraineeController extends Controller
{
    public function __construct(private readonly TraineeService $trainees) {}

    public function index(Request $request): View
    {
        $term = trim((string) $request->input('q'));

        $trainees = Trainee::query()
            ->with([
                'educationLevel:id,name',
                'jobGrade:id,name',
                'traineeProviders.provider:id,name,is_active',
            ])
            ->withCount(['traineeProviders', 'traineeWarnings', 'nominations', 'enrollments'])
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.$term.'%';
                $query->where(fn ($q) => $q
                    ->where('full_name', 'like', $like)
                    ->orWhere('national_id', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like));
            })
            ->when($request->filled('provider_id'), fn ($q) => $q->whereHas(
                'traineeProviders',
                fn ($providerQuery) => $providerQuery->where('provider_id', $request->input('provider_id'))
            ))
            ->when($request->filled('education_level_id'), fn ($q) => $q->where('education_level_id', $request->input('education_level_id')))
            ->when($request->filled('job_grade_id'), fn ($q) => $q->where('job_grade_id', $request->input('job_grade_id')))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('full_name')
            ->paginate(15)
            ->withQueryString();

        return view('trainees.index', [
            'trainees' => $trainees,
            'providers' => TrainingProvider::orderBy('name')->pluck('name', 'id'),
            'educationLevels' => EducationLevel::orderBy('rank_order')->orderBy('name')->pluck('name', 'id'),
            'jobGrades' => JobGrade::orderBy('rank_order')->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(): View
    {
        return view('trainees.create', $this->formData());
    }

    public function store(StoreTraineeRequest $request): RedirectResponse
    {
        $trainee = $this->trainees->create($request->validated());

        return redirect()
            ->route('trainees.show', $trainee)
            ->with('success', 'تمت إضافة المتدرب بنجاح.');
    }

    public function show(Trainee $trainee): View
    {
        $trainee->load([
            'educationLevel',
            'jobGrade',
            'user',
            'traineeProviders' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('id'),
            'traineeProviders.provider',
            'traineeWarnings' => fn ($query) => $query->orderByDesc('issued_at')->orderByDesc('id'),
            'traineeWarnings.session.course',
            'traineeWarnings.issuedBy',
            'nominations' => fn ($query) => $query->orderByDesc('application_date')->orderByDesc('id'),
            'nominations.session.course',
            'enrollments' => fn ($query) => $query->orderByDesc('created_at')->orderByDesc('id'),
            'enrollments.session.course',
            'invoices' => fn ($query) => $query->orderByDesc('created_at')->orderByDesc('id'),
            'invoices.session.course',
        ])->loadCount(['traineeProviders', 'traineeWarnings', 'nominations', 'enrollments', 'invoices']);

        return view('trainees.show', [
            'trainee' => $trainee,
            'warningTypes' => WarningType::options(),
            'sessions' => CourseSession::with('course:id,title')
                ->orderByDesc('start_date')
                ->get()
                ->mapWithKeys(fn (CourseSession $session) => [
                    $session->id => $session->session_code.' — '.($session->course?->title ?? 'بدون دورة'),
                ])
                ->all(),
        ]);
    }

    public function edit(Trainee $trainee): View
    {
        $trainee->load([
            'traineeProviders' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('id'),
            'traineeProviders.provider',
        ]);

        return view('trainees.edit', [
            'trainee' => $trainee,
            ...$this->formData(),
        ]);
    }

    public function update(UpdateTraineeRequest $request, Trainee $trainee): RedirectResponse
    {
        $this->trainees->update($trainee, $request->validated());

        return redirect()
            ->route('trainees.show', $trainee)
            ->with('success', 'تم تحديث بيانات المتدرب بنجاح.');
    }

    public function destroy(Trainee $trainee): RedirectResponse
    {
        if ($blockers = $this->trainees->deletionBlockers($trainee)) {
            return redirect()
                ->route('trainees.show', $trainee)
                ->with('error', 'لا يمكن حذف المتدرب لارتباطه بـ: '.implode('، ', $blockers).'.');
        }

        $this->trainees->delete($trainee);

        return redirect()
            ->route('trainees.index')
            ->with('success', 'تم حذف المتدرب بنجاح.');
    }

    /**
     * Lookups required by the create/edit form.
     *
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'providers' => TrainingProvider::orderBy('name')->pluck('name', 'id'),
            'educationLevels' => EducationLevel::orderBy('rank_order')->orderBy('name')->pluck('name', 'id'),
            'jobGrades' => JobGrade::orderBy('rank_order')->orderBy('name')->pluck('name', 'id'),
            'genders' => Gender::options(),
        ];
    }
}
