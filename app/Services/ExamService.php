<?php

namespace App\Services;

use App\Enums\ExamResultStatus;
use App\Models\CourseSession;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Maintains question banks, exams and exam grading.
 */
class ExamService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function saveBank(QuestionBank $bank, array $data): QuestionBank
    {
        return DB::transaction(function () use ($bank, $data) {
            $bank->fill([
                'course_id' => $data['course_id'],
                'module_id' => $data['module_id'] ?? null,
                'name' => $data['name'] ?? null,
            ])->save();

            $keepIds = [];

            foreach ((array) ($data['questions'] ?? []) as $row) {
                if (! is_array($row) || blank($row['question_text'] ?? null)) {
                    continue;
                }

                $question = ! empty($row['id'])
                    ? $bank->questions()->find((int) $row['id'])
                    : null;

                $question ??= new Question(['bank_id' => $bank->id]);

                $question->fill([
                    'question_text' => $row['question_text'],
                    'question_type' => $row['question_type'],
                    'difficulty' => $row['difficulty'] ?? null,
                    'default_points' => $row['default_points'] ?? 1,
                    'is_active' => (bool) ($row['is_active'] ?? false),
                ])->save();

                $question->questionOptions()->delete();

                $sort = 0;
                foreach ((array) ($row['options'] ?? []) as $option) {
                    $text = trim((string) ($option['option_text'] ?? ''));

                    if ($text === '') {
                        continue;
                    }

                    $question->questionOptions()->create([
                        'option_text' => $text,
                        'is_correct' => (bool) ($option['is_correct'] ?? false),
                        'sort_order' => ++$sort,
                    ]);
                }

                $keepIds[] = $question->id;
            }

            $removed = $bank->questions()
                ->when($keepIds !== [], fn ($query) => $query->whereNotIn('id', $keepIds))
                ->get();

            foreach ($removed as $question) {
                if ($question->exams()->exists() || $question->examAnswers()->exists()) {
                    continue;
                }

                $question->questionOptions()->delete();
                $question->delete();
            }

            return $bank->refresh();
        });
    }

    public function deleteBank(QuestionBank $bank): void
    {
        DB::transaction(function () use ($bank) {
            $questions = $bank->questions()->withCount(['exams', 'examAnswers'])->get();

            if ($questions->contains(fn (Question $question) => $question->exams_count > 0 || $question->exam_answers_count > 0)) {
                throw ValidationException::withMessages([
                    'bank' => 'لا يمكن حذف بنك الأسئلة لاحتوائه على أسئلة مستخدمة في اختبارات.',
                ]);
            }

            foreach ($questions as $question) {
                $question->questionOptions()->delete();
                $question->delete();
            }

            $bank->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveExam(Exam $exam, array $data, User $actor): Exam
    {
        return DB::transaction(function () use ($exam, $data, $actor) {
            $exam->fill([
                'session_id' => $data['session_id'],
                'exam_type' => $data['exam_type'],
                'title' => $data['title'] ?? null,
                'scheduled_start' => $data['scheduled_start'] ?? null,
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'total_score' => $data['total_score'] ?? null,
                'pass_score' => $data['pass_score'] ?? null,
                'weight_pct' => $data['weight_pct'] ?? null,
                'created_by' => $exam->exists ? $exam->created_by : $actor->id,
            ])->save();

            $sync = [];
            foreach ((array) ($data['question_ids'] ?? []) as $order => $questionId) {
                $sync[(int) $questionId] = [
                    'points' => $data['points'][$questionId] ?? null,
                    'sort_order' => $order + 1,
                ];
            }

            $exam->questions()->sync($sync);

            return $exam->refresh();
        });
    }

    public function deleteExam(Exam $exam): void
    {
        if ($exam->examResults()->exists()) {
            throw ValidationException::withMessages([
                'exam' => 'لا يمكن حذف اختبار له نتائج مسجّلة.',
            ]);
        }

        DB::transaction(function () use ($exam) {
            $exam->questions()->detach();
            $exam->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{graded: int, skipped: int}
     */
    public function grade(Exam $exam, array $data, User $actor): array
    {
        $exam->loadMissing(['session.enrollments']);

        $validEnrollments = $exam->session?->enrollments
            ->whereNotIn('status', ['withdrawn', 'dismissed'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all() ?? [];

        $total = (float) ($exam->total_score ?? 0);
        $pass = $exam->pass_score !== null ? (float) $exam->pass_score : null;

        return DB::transaction(function () use ($exam, $data, $actor, $validEnrollments, $total, $pass) {
            $graded = 0;
            $skipped = 0;

            foreach ((array) ($data['results'] ?? []) as $enrollmentId => $row) {
                $enrollmentId = (int) $enrollmentId;

                if (! in_array($enrollmentId, $validEnrollments, true)) {
                    throw ValidationException::withMessages([
                        'results' => 'أحد المتدربين المحددين لا ينتمي إلى جلسة الاختبار.',
                    ]);
                }

                $score = $row['score'] ?? null;

                if ($score === null || $score === '') {
                    $skipped++;

                    continue;
                }

                $score = (float) $score;
                $percentage = $total > 0 ? round(($score / $total) * 100, 2) : null;
                $isPassed = $pass !== null ? $score >= $pass : ($percentage !== null && $percentage >= 50);

                ExamResult::updateOrCreate(
                    ['exam_id' => $exam->id, 'enrollment_id' => $enrollmentId],
                    [
                        'score' => $score,
                        'percentage' => $percentage,
                        'is_passed' => $isPassed,
                        'status' => ExamResultStatus::Graded,
                        'taken_at' => $row['taken_at'] ?? now(),
                        'graded_by' => $actor->id,
                    ],
                );

                $graded++;
            }

            return ['graded' => $graded, 'skipped' => $skipped];
        });
    }

    /**
     * @return array<int, string>
     */
    public function sessionOptions(): array
    {
        return CourseSession::query()
            ->with('course')
            ->orderByDesc('start_date')
            ->get()
            ->mapWithKeys(fn (CourseSession $session) => [
                $session->id => $session->session_code.' — '.($session->course?->title ?? 'دورة محذوفة'),
            ])
            ->all();
    }
}
