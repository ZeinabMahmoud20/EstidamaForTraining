<?php

namespace App\Services;

use App\Enums\CriteriaOperator;
use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\CourseCriteria;
use App\Models\CourseSession;
use App\Models\Trainee;

/**
 * Evaluates the course admission criteria against a trainee profile.
 * The result is informational and mandatory failures can be overridden by OVERRIDE_CRITERIA.
 */
class NominationEligibilityService
{
    /**
     * @return array{items: list<array<string, mixed>>, blocking: bool}
     */
    public function evaluate(CourseSession $session, Trainee $trainee): array
    {
        $session->loadMissing(['course.prerequisites', 'course.courseCriteria.criteriaType']);
        $trainee->loadMissing([
            'educationLevel',
            'jobGrade',
            'traineeWarnings',
            'enrollments.session.course',
        ]);

        /** @var Course|null $course */
        $course = $session->course;

        if (! $course) {
            return ['items' => [], 'blocking' => false];
        }

        $items = $course->courseCriteria
            ->map(fn (CourseCriteria $criterion) => $this->evaluateCriterion($criterion, $course, $trainee))
            ->values()
            ->all();

        $blocking = collect($items)->contains(fn (array $item) => $item['mandatory'] && ! $item['passed']);

        return ['items' => $items, 'blocking' => $blocking];
    }

    /**
     * @return array<string, mixed>
     */
    private function evaluateCriterion(CourseCriteria $criterion, Course $course, Trainee $trainee): array
    {
        $code = $criterion->criteriaType?->code ?? '';
        $operator = $criterion->operator;
        $value = trim((string) $criterion->value);

        [$passed, $actual, $required] = match ($code) {
            'MIN_EDUCATION' => $this->checkEducation($trainee, $operator, $value),
            'MIN_EXPERIENCE' => $this->checkNumeric($trainee->years_experience, $operator, $value, 'سنوات الخبرة'),
            'JOB_TITLE' => $this->checkString($trainee->job_title, $operator, $value, 'المسمى الوظيفي'),
            'JOB_GRADE' => $this->checkJobGrade($trainee, $operator, $value),
            'MIN_AGE' => $this->checkAge($trainee, $operator, $value, true),
            'MAX_AGE' => $this->checkAge($trainee, $operator, $value, false),
            'PASSED_PREREQ' => $this->checkPrerequisites($trainee, $course),
            'NOT_TAKEN_WITHIN_YEARS' => $this->checkNotTakenRecently($trainee, $course, $value),
            'NO_ACTIVE_WARNING' => [
                $trainee->traineeWarnings->where('is_active', true)->isEmpty(),
                $trainee->traineeWarnings->where('is_active', true)->count().' إنذار ساري',
                'لا يوجد إنذار ساري',
            ],
            default => [true, '—', 'معيار غير مدعوم'],
        };

        return [
            'code' => $code,
            'name' => $criterion->criteriaType?->name ?? $code,
            'mandatory' => (bool) $criterion->is_mandatory,
            'passed' => $passed,
            'actual' => $actual,
            'required' => $required,
        ];
    }

    /**
     * @return array{0: bool, 1: string, 2: string}
     */
    private function checkEducation(Trainee $trainee, CriteriaOperator $operator, string $value): array
    {
        $level = $trainee->educationLevel;

        if (! $level) {
            return [false, 'غير محدد', $value !== '' ? $value : 'مؤهل مطلوب'];
        }

        if (is_numeric($value)) {
            return [
                $this->compareNumber($level->rank_order, $operator, (float) $value),
                (string) ($level->rank_order ?? $level->name),
                $value,
            ];
        }

        return [
            $this->compareString($level->name, $operator, $value),
            $level->name,
            $value,
        ];
    }

    /**
     * @return array{0: bool, 1: string, 2: string}
     */
    private function checkJobGrade(Trainee $trainee, CriteriaOperator $operator, string $value): array
    {
        $grade = $trainee->jobGrade;

        if (! $grade) {
            return [false, 'غير محدد', $value];
        }

        if (is_numeric($value)) {
            return [
                $this->compareNumber($grade->rank_order, $operator, (float) $value),
                (string) ($grade->rank_order ?? $grade->name),
                $value,
            ];
        }

        return [
            $this->compareString($grade->name, $operator, $value),
            $grade->name,
            $value,
        ];
    }

    /**
     * @return array{0: bool, 1: string, 2: string}
     */
    private function checkNumeric(?int $actual, CriteriaOperator $operator, string $value, string $label): array
    {
        if ($actual === null || ! is_numeric($value)) {
            return [false, $actual === null ? 'غير محدد' : (string) $actual, $value];
        }

        return [
            $this->compareNumber($actual, $operator, (float) $value),
            $actual.' '.$label,
            $value,
        ];
    }

    /**
     * @return array{0: bool, 1: string, 2: string}
     */
    private function checkString(?string $actual, CriteriaOperator $operator, string $value, string $label): array
    {
        if ($actual === null || $actual === '') {
            return [false, 'غير محدد', $value];
        }

        return [
            $this->compareString($actual, $operator, $value),
            $actual,
            $value,
        ];
    }

    /**
     * @return array{0: bool, 1: string, 2: string}
     */
    private function checkAge(Trainee $trainee, CriteriaOperator $operator, string $value, bool $minimum): array
    {
        if (! $trainee->birth_date) {
            return [false, 'تاريخ الميلاد غير محدد', $value];
        }

        $age = (int) $trainee->birth_date->diffInYears(now());
        $comparison = $minimum
            ? $this->compareNumber($age, $operator === CriteriaOperator::Eq ? CriteriaOperator::Gte : $operator, (float) $value)
            : $this->compareNumber($age, $operator === CriteriaOperator::Eq ? CriteriaOperator::Lte : $operator, (float) $value);

        return [$comparison, $age.' سنة', $value.' سنة'];
    }

    /**
     * @return array{0: bool, 1: string, 2: string}
     */
    private function checkPrerequisites(Trainee $trainee, Course $course): array
    {
        $prerequisites = $course->prerequisites;

        if ($prerequisites->isEmpty()) {
            return [true, 'لا توجد متطلبات سابقة', '—'];
        }

        $completedCourseIds = $trainee->enrollments
            ->filter(fn ($enrollment) => $enrollment->status === EnrollmentStatus::Completed || $enrollment->is_passed === true)
            ->map(fn ($enrollment) => $enrollment->session?->course_id)
            ->filter()
            ->unique()
            ->all();

        $missing = $prerequisites
            ->reject(fn (Course $prerequisite) => in_array($prerequisite->id, $completedCourseIds, true))
            ->pluck('title')
            ->all();

        return [
            $missing === [],
            $missing === [] ? 'كل المتطلبات مكتملة' : 'ناقص: '.implode('، ', $missing),
            $prerequisites->pluck('title')->implode('، '),
        ];
    }

    /**
     * @return array{0: bool, 1: string, 2: string}
     */
    private function checkNotTakenRecently(Trainee $trainee, Course $course, string $value): array
    {
        $years = is_numeric($value) ? (int) $value : 0;

        if ($years <= 0) {
            return [true, '—', $value];
        }

        $cutoff = now()->subYears($years);

        $recent = $trainee->enrollments
            ->filter(function ($enrollment) use ($course, $cutoff) {
                $session = $enrollment->session;

                return $session
                    && (int) $session->course_id === (int) $course->id
                    && $session->start_date
                    && $session->start_date->greaterThanOrEqualTo($cutoff)
                    && ! in_array($enrollment->status, [EnrollmentStatus::Withdrawn, EnrollmentStatus::Dismissed], true);
            })
            ->count();

        return [
            $recent === 0,
            $recent === 0 ? 'لم يلتحق مؤخرًا' : 'التحق خلال آخر '.$years.' سنة',
            'عدم الالتحاق خلال '.$years.' سنة',
        ];
    }

    private function compareNumber(int|float|null $actual, CriteriaOperator $operator, float $target): bool
    {
        if ($actual === null) {
            return false;
        }

        return match ($operator) {
            CriteriaOperator::Eq => (float) $actual === $target,
            CriteriaOperator::Gte => (float) $actual >= $target,
            CriteriaOperator::Lte => (float) $actual <= $target,
            CriteriaOperator::In => in_array((string) $actual, $this->listValues((string) $target), true),
            CriteriaOperator::NotIn => ! in_array((string) $actual, $this->listValues((string) $target), true),
        };
    }

    private function compareString(?string $actual, CriteriaOperator $operator, string $value): bool
    {
        if ($actual === null || $actual === '') {
            return false;
        }

        $actual = trim($actual);
        $values = $this->listValues($value);

        return match ($operator) {
            CriteriaOperator::Eq => $actual === $value,
            CriteriaOperator::Gte => strcmp($actual, $value) >= 0,
            CriteriaOperator::Lte => strcmp($actual, $value) <= 0,
            CriteriaOperator::In => in_array($actual, $values, true),
            CriteriaOperator::NotIn => ! in_array($actual, $values, true),
        };
    }

    /**
     * @return list<string>
     */
    private function listValues(string $value): array
    {
        return collect(explode(',', $value))
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }
}
