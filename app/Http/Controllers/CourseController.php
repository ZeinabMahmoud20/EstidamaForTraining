<?php

namespace App\Http\Controllers;

use App\Enums\CourseLevel;
use App\Enums\CriteriaOperator;
use App\Enums\DeliveryMode;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseType;
use App\Models\CriteriaType;
use App\Services\CourseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function __construct(private readonly CourseService $courses) {}

    public function index(Request $request): View
    {
        $term = trim((string) $request->input('q'));

        $courses = Course::query()
            ->with(['category', 'courseType'])
            ->withCount('courseSessions')
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.$term.'%';
                $query->where(fn ($q) => $q->where('code', 'like', $like)->orWhere('title', 'like', $like));
            })
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->input('category_id')))
            ->when($request->filled('course_type_id'), fn ($q) => $q->where('course_type_id', $request->input('course_type_id')))
            ->when($request->filled('level'), fn ($q) => $q->where('level', $request->input('level')))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('title')
            ->paginate(15)
            ->withQueryString();

        return view('courses.index', [
            'courses' => $courses,
            'categories' => CourseCategory::orderBy('name')->pluck('name', 'id'),
            'types' => CourseType::orderBy('name')->pluck('name', 'id'),
            'levels' => CourseLevel::options(),
        ]);
    }

    public function create(): View
    {
        return view('courses.create', $this->formData());
    }

    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $course = $this->courses->create($request->validated());

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'تمت إضافة الدورة بنجاح.');
    }

    public function show(Course $course): View
    {
        $course->load([
            'category',
            'courseType',
            'courseModules' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'courseCriteria.criteriaType',
            'prerequisites',
            'requiredFor',
        ])->loadCount('courseSessions');

        return view('courses.show', compact('course'));
    }

    public function edit(Course $course): View
    {
        $course->load([
            'courseModules' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'courseCriteria.criteriaType',
            'prerequisites',
        ]);

        return view('courses.edit', [
            'course' => $course,
            ...$this->formData($course),
        ]);
    }

    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $this->courses->update($course, $request->validated());

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'تم تحديث الدورة بنجاح.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        if ($blockers = $this->courses->deletionBlockers($course)) {
            return redirect()
                ->route('courses.show', $course)
                ->with('error', 'لا يمكن حذف الدورة لارتباطها بـ: '.implode('، ', $blockers).'.');
        }

        $this->courses->delete($course);

        return redirect()
            ->route('courses.index')
            ->with('success', 'تم حذف الدورة بنجاح.');
    }

    /**
     * Lookups required by the create/edit form.
     *
     * @return array<string, mixed>
     */
    private function formData(?Course $course = null): array
    {
        return [
            'categories' => CourseCategory::orderBy('name')->pluck('name', 'id'),
            'types' => CourseType::orderBy('name')->pluck('name', 'id'),
            'levels' => CourseLevel::options(),
            'deliveryModes' => DeliveryMode::options(),
            'operators' => CriteriaOperator::options(),
            'criteriaTypes' => CriteriaType::orderBy('name')->pluck('name', 'id'),
            'allCourses' => Course::query()
                ->when($course, fn ($query) => $query->whereKeyNot($course->id))
                ->orderBy('title')
                ->get(['id', 'code', 'title']),
        ];
    }
}
