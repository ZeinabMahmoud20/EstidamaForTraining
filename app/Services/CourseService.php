<?php

namespace App\Services;

use App\Models\Course;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Writes a course together with its nested rows (modules, criteria, prerequisites).
 */
class CourseService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Course
    {
        return DB::transaction(function () use ($data) {
            [$attributes, $modules, $criteria, $prerequisites] = $this->extract($data);

            $course = Course::create($attributes);

            $this->syncModules($course, $modules);
            $this->syncCriteria($course, $criteria);
            $this->syncPrerequisites($course, $prerequisites);

            return $course;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Course $course, array $data): Course
    {
        return DB::transaction(function () use ($course, $data) {
            [$attributes, $modules, $criteria, $prerequisites] = $this->extract($data);

            $course->update($attributes);

            $this->syncModules($course, $modules);
            $this->syncCriteria($course, $criteria);
            $this->syncPrerequisites($course, $prerequisites);

            return $course;
        });
    }

    public function delete(Course $course): void
    {
        if ($blockers = $this->deletionBlockers($course)) {
            throw ValidationException::withMessages([
                'course' => 'لا يمكن حذف الدورة لارتباطها بـ: '.implode('، ', $blockers).'.',
            ]);
        }

        DB::transaction(function () use ($course) {
            $course->courseCriteria()->delete();
            $course->courseModules()->delete();
            $course->prerequisites()->detach();
            $course->delete();
        });
    }

    /**
     * Reasons preventing deletion (empty array means the course can be deleted).
     *
     * @return list<string>
     */
    public function deletionBlockers(Course $course): array
    {
        $blockers = [];

        if ($course->courseSessions()->exists()) {
            $blockers[] = 'جلسات مجدولة';
        }
        if ($course->trainerCourseApprovals()->exists()) {
            $blockers[] = 'اعتمادات مدربين';
        }
        if ($course->questionBanks()->exists()) {
            $blockers[] = 'بنوك أسئلة';
        }
        if ($course->requiredFor()->exists()) {
            $blockers[] = 'دورات أخرى تعتمد عليها كمتطلب سابق';
        }

        foreach ($course->courseModules as $module) {
            if ($module->sessionDayModules()->exists()
                || $module->trainingKits()->exists()
                || $module->questionBanks()->exists()) {
                $blockers[] = 'وحدات مرتبطة بجلسات أو حقائب أو بنوك أسئلة';

                break;
            }
        }

        return array_values(array_unique($blockers));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: array<int, array<string, mixed>>, 2: array<int, array<string, mixed>>, 3: array<int, int|string>}
     */
    private function extract(array $data): array
    {
        $modules = Arr::pull($data, 'modules', []);
        $criteria = Arr::pull($data, 'criteria', []);
        $prerequisites = Arr::pull($data, 'prerequisites', []);

        // These columns are NOT NULL with database defaults; never write null.
        if (($data['min_attendance_pct'] ?? null) === null) {
            $data['min_attendance_pct'] = 75;
        }
        if (($data['pass_score_pct'] ?? null) === null) {
            $data['pass_score_pct'] = 60;
        }
        if (($data['is_active'] ?? null) === null) {
            $data['is_active'] = true;
        }

        return [
            $data,
            array_values(array_filter((array) $modules, 'is_array')),
            array_values(array_filter((array) $criteria, 'is_array')),
            array_values(array_filter((array) $prerequisites, fn ($id) => filled($id))),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncModules(Course $course, array $rows): void
    {
        $keptIds = [];

        foreach ($rows as $index => $row) {
            $attributes = [
                'title' => $row['title'],
                'description' => $row['description'] ?? null,
                'hours' => $row['hours'] ?? null,
                'sort_order' => $row['sort_order'] ?? $index + 1,
            ];

            $module = ! empty($row['id'])
                ? $course->courseModules()->whereKey($row['id'])->first()
                : null;

            if ($module) {
                $module->update($attributes);
            } else {
                $module = $course->courseModules()->create($attributes);
            }

            $keptIds[] = $module->id;
        }

        $removed = $course->courseModules()
            ->when($keptIds !== [], fn ($query) => $query->whereNotIn('id', $keptIds))
            ->get();

        foreach ($removed as $module) {
            if ($module->sessionDayModules()->exists()
                || $module->trainingKits()->exists()
                || $module->questionBanks()->exists()) {
                throw ValidationException::withMessages([
                    'modules' => "لا يمكن حذف الوحدة «{$module->title}» لارتباطها بجلسات أو حقائب أو بنوك أسئلة.",
                ]);
            }

            $module->delete();
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncCriteria(Course $course, array $rows): void
    {
        $course->courseCriteria()->delete();

        foreach ($rows as $row) {
            $course->courseCriteria()->create([
                'criteria_type_id' => $row['criteria_type_id'],
                'operator' => $row['operator'],
                'value' => $row['value'] ?? null,
                'is_mandatory' => (bool) ($row['is_mandatory'] ?? true),
            ]);
        }
    }

    /**
     * @param  array<int, int|string>  $prerequisites
     */
    private function syncPrerequisites(Course $course, array $prerequisites): void
    {
        $ids = array_values(array_unique(array_map('intval', $prerequisites)));

        $this->assertNoCircularPrerequisites($course, $ids);

        $course->prerequisites()->sync($ids);
    }

    /**
     * @param  array<int, int>  $prerequisites
     */
    private function assertNoCircularPrerequisites(Course $course, array $prerequisites): void
    {
        $stack = $prerequisites;
        $visited = [];

        while ($stack !== []) {
            $id = (int) array_pop($stack);

            if ($id === $course->id) {
                throw ValidationException::withMessages([
                    'prerequisites' => 'لا يمكن أن تكون الدورة متطلبًا لنفسها أو ضمن سلسلة متطلبات دائرية.',
                ]);
            }

            if (isset($visited[$id])) {
                continue;
            }
            $visited[$id] = true;

            $next = Course::find($id)?->prerequisites()->pluck('courses.id') ?? [];
            foreach ($next as $nextId) {
                $stack[] = (int) $nextId;
            }
        }
    }
}
