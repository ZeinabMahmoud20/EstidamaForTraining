<?php

namespace App\Http\Controllers;

use App\Enums\Gender;
use App\Enums\TrainerType;
use App\Http\Requests\StoreTrainerRequest;
use App\Http\Requests\UpdateTrainerRequest;
use App\Models\Branch;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Trainer;
use App\Models\TrainingProvider;
use App\Services\TrainerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainerController extends Controller
{
    public function __construct(private readonly TrainerService $trainers) {}

    public function index(Request $request): View
    {
        $term = trim((string) $request->input('q'));

        $trainers = Trainer::query()
            ->with(['provider', 'branch', 'courseCategories'])
            ->withCount('courseCategories')
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.$term.'%';
                $query->where(fn ($q) => $q
                    ->where('full_name', 'like', $like)
                    ->orWhere('national_id', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like));
            })
            ->when($request->filled('trainer_type'), fn ($q) => $q->where('trainer_type', $request->input('trainer_type')))
            ->when($request->filled('provider_id'), fn ($q) => $q->where('provider_id', $request->input('provider_id')))
            ->when($request->filled('category_id'), fn ($q) => $q->whereHas(
                'courseCategories',
                fn ($categoryQuery) => $categoryQuery->where('course_categories.id', $request->input('category_id'))
            ))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('full_name')
            ->paginate(15)
            ->withQueryString();

        return view('trainers.index', [
            'trainers' => $trainers,
            'providers' => TrainingProvider::orderBy('name')->pluck('name', 'id'),
            'categories' => CourseCategory::orderBy('name')->pluck('name', 'id'),
            'trainerTypes' => TrainerType::options(),
        ]);
    }

    public function create(): View
    {
        return view('trainers.create', $this->formData());
    }

    public function store(StoreTrainerRequest $request): RedirectResponse
    {
        $trainer = $this->trainers->create($request->validated());

        return redirect()
            ->route('trainers.show', $trainer)
            ->with('success', 'تمت إضافة المدرب بنجاح.');
    }

    public function show(Trainer $trainer): View
    {
        $trainer->load([
            'provider',
            'branch',
            'courseCategories',
            'trainerCertifications' => fn ($query) => $query->orderByDesc('issue_date')->orderBy('id'),
            'trainerExperiences' => fn ($query) => $query->orderByDesc('from_date')->orderBy('id'),
            'trainerCourseApprovals.course',
            'trainerCourseApprovals.approvedBy',
        ])->loadCount(['sessionTrainers', 'courseCategories', 'trainerCertifications', 'trainerExperiences']);

        return view('trainers.show', [
            'trainer' => $trainer,
            'courses' => Course::orderBy('title')->get(['id', 'code', 'title']),
        ]);
    }

    public function edit(Trainer $trainer): View
    {
        $trainer->load([
            'courseCategories',
            'trainerCertifications' => fn ($query) => $query->orderByDesc('issue_date')->orderBy('id'),
            'trainerExperiences' => fn ($query) => $query->orderByDesc('from_date')->orderBy('id'),
        ]);

        return view('trainers.edit', [
            'trainer' => $trainer,
            ...$this->formData(),
        ]);
    }

    public function update(UpdateTrainerRequest $request, Trainer $trainer): RedirectResponse
    {
        $this->trainers->update($trainer, $request->validated());

        return redirect()
            ->route('trainers.show', $trainer)
            ->with('success', 'تم تحديث بيانات المدرب بنجاح.');
    }

    public function destroy(Trainer $trainer): RedirectResponse
    {
        if ($blockers = $this->trainers->deletionBlockers($trainer)) {
            return redirect()
                ->route('trainers.show', $trainer)
                ->with('error', 'لا يمكن حذف المدرب لارتباطه بـ: '.implode('، ', $blockers).'.');
        }

        $this->trainers->delete($trainer);

        return redirect()
            ->route('trainers.index')
            ->with('success', 'تم حذف المدرب بنجاح.');
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
            'branches' => Branch::orderBy('name')->pluck('name', 'id'),
            'categories' => CourseCategory::orderBy('name')->pluck('name', 'id'),
            'trainerTypes' => TrainerType::options(),
            'genders' => Gender::options(),
        ];
    }
}
