<?php

namespace App\Http\Controllers;

use App\Http\Requests\LookupRequest;
use App\Models\CourseCategory;
use App\Models\CourseType;
use App\Models\CriteriaType;
use App\Models\EducationLevel;
use App\Models\JobGrade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LookupController extends Controller
{
    /** @var array<string, class-string<Model>> */
    public const TYPES = [
        'categories' => CourseCategory::class,
        'course-types' => CourseType::class,
        'criteria-types' => CriteriaType::class,
        'education-levels' => EducationLevel::class,
        'job-grades' => JobGrade::class,
    ];

    private const LABELS = [
        'categories' => 'تصنيفات الدورات',
        'course-types' => 'أنواع الدورات',
        'criteria-types' => 'معايير الالتحاق',
        'education-levels' => 'المستويات التعليمية',
        'job-grades' => 'الدرجات الوظيفية',
    ];

    private const SINGULAR = [
        'categories' => 'التصنيف',
        'course-types' => 'النوع',
        'criteria-types' => 'المعيار',
        'education-levels' => 'المستوى التعليمي',
        'job-grades' => 'الدرجة الوظيفية',
    ];

    public function index(Request $request): View
    {
        $tab = (string) $request->query('tab', 'categories');
        if (! array_key_exists($tab, self::TYPES)) {
            $tab = 'categories';
        }

        $editingId = $request->integer('edit');
        $editing = null;
        if ($editingId > 0) {
            $model = self::TYPES[$tab];
            $editing = $model::find($editingId);
        }

        $items = match ($tab) {
            'categories' => CourseCategory::query()
                ->with('parent')
                ->withCount(['children', 'courses', 'trainers'])
                ->orderBy('name')
                ->get(),
            'course-types' => CourseType::query()
                ->withCount('courses')
                ->orderBy('name')
                ->get(),
            'criteria-types' => CriteriaType::query()
                ->withCount('courseCriteria')
                ->orderBy('name')
                ->get(),
            'education-levels' => EducationLevel::query()
                ->withCount('trainees')
                ->orderBy('rank_order')
                ->orderBy('name')
                ->get(),
            'job-grades' => JobGrade::query()
                ->withCount('trainees')
                ->orderBy('rank_order')
                ->orderBy('name')
                ->get(),
        };

        return view('lookups.index', [
            'tab' => $tab,
            'tabs' => self::LABELS,
            'singular' => self::SINGULAR[$tab],
            'editing' => $editing,
            'items' => $items,
            'categoryOptions' => $tab === 'categories' ? $this->categoryOptions($editing) : [],
        ]);
    }

    public function store(LookupRequest $request, string $type): RedirectResponse
    {
        $model = $this->model($type);
        $model::create($request->validated());

        return $this->backToTab($type, 'تمت الإضافة بنجاح.');
    }

    public function update(LookupRequest $request, string $type, string $lookup): RedirectResponse
    {
        $model = $this->model($type);
        $record = $model::findOrFail((int) $lookup);
        $record->update($request->validated());

        return $this->backToTab($type, 'تم تحديث البيانات بنجاح.');
    }

    public function destroy(string $type, string $lookup): RedirectResponse
    {
        $model = $this->model($type);
        $record = $model::findOrFail((int) $lookup);

        if ($blockers = $this->deletionBlockers($type, $record)) {
            return $this->backToTab(
                $type,
                'لا يمكن حذف «'.$record->name.'» لارتباطه بـ: '.implode('، ', $blockers).'.',
                'error'
            );
        }

        $record->delete();

        return $this->backToTab($type, 'تم الحذف بنجاح.');
    }

    private function backToTab(string $type, string $message, string $level = 'success'): RedirectResponse
    {
        return redirect()
            ->route('lookups.index', ['tab' => $type])
            ->with($level, $message);
    }

    /**
     * @return class-string<Model>
     */
    private function model(string $type): string
    {
        abort_unless(array_key_exists($type, self::TYPES), 404);

        return self::TYPES[$type];
    }

    /**
     * @return list<string>
     */
    private function deletionBlockers(string $type, Model $record): array
    {
        $blockers = match ($type) {
            'categories' => [
                $record->children()->exists() ? 'تصنيفات فرعية' : null,
                $record->courses()->exists() ? 'دورات' : null,
                $record->trainers()->exists() ? 'مدربين' : null,
            ],
            'course-types' => [
                $record->courses()->exists() ? 'دورات' : null,
            ],
            'criteria-types' => [
                $record->courseCriteria()->exists() ? 'شروط التحاق بدورات' : null,
            ],
            'education-levels' => [
                $record->trainees()->exists() ? 'متدربين' : null,
            ],
            'job-grades' => [
                $record->trainees()->exists() ? 'متدربين' : null,
            ],
            default => [],
        };

        return array_values(array_filter($blockers));
    }

    /**
     * Category options for the parent select, excluding the edited category
     * and all of its descendants to prevent cycles.
     *
     * @return array<int, string>
     */
    private function categoryOptions(?CourseCategory $editing): array
    {
        $categories = CourseCategory::orderBy('name')->get();

        if (! $editing) {
            return $categories->pluck('name', 'id')->all();
        }

        $excluded = [$editing->id => true];
        $stack = $editing->children()->pluck('id')->map(fn ($id) => (int) $id)->all();

        while ($stack !== []) {
            $id = (int) array_pop($stack);

            if (isset($excluded[$id])) {
                continue;
            }

            $excluded[$id] = true;
            foreach (CourseCategory::where('parent_id', $id)->pluck('id') as $childId) {
                $stack[] = (int) $childId;
            }
        }

        return $categories
            ->reject(fn (CourseCategory $category) => isset($excluded[$category->id]))
            ->pluck('name', 'id')
            ->all();
    }
}
