<?php

namespace App\Services;

use App\Enums\EvalAnswerType;
use App\Models\CourseSession;
use App\Models\EvaluationAnswer;
use App\Models\EvaluationForm;
use App\Models\EvaluationItem;
use App\Models\EvaluationParticipation;
use App\Models\EvaluationSection;
use App\Models\EvaluationSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Builds evaluation forms and records evaluation submissions.
 */
class EvaluationService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function saveForm(EvaluationForm $form, array $data, User $actor): EvaluationForm
    {
        return DB::transaction(function () use ($form, $data, $actor) {
            $isNew = ! $form->exists;

            $form->fill([
                'code' => $data['code'],
                'title' => $data['title'],
                'target_type' => $data['target_type'],
                'evaluator_type' => $data['evaluator_type'],
                'is_anonymous' => (bool) ($data['is_anonymous'] ?? false),
                'is_active' => (bool) ($data['is_active'] ?? false),
                'version' => $data['version'] ?? 1,
                'created_by' => $isNew ? $actor->id : $form->created_by,
            ])->save();

            $keepSections = [];
            $keepItems = [];

            foreach ((array) ($data['sections'] ?? []) as $sectionIndex => $sectionRow) {
                if (! is_array($sectionRow) || blank($sectionRow['title'] ?? null)) {
                    continue;
                }

                $section = ! empty($sectionRow['id'])
                    ? $form->evaluationSections()->find((int) $sectionRow['id'])
                    : null;

                $section ??= new EvaluationSection(['form_id' => $form->id]);

                $section->fill([
                    'form_id' => $form->id,
                    'title' => $sectionRow['title'],
                    'weight' => $sectionRow['weight'] ?? null,
                    'sort_order' => $sectionIndex + 1,
                ])->save();

                foreach ((array) ($sectionRow['items'] ?? []) as $itemIndex => $itemRow) {
                    if (! is_array($itemRow) || blank($itemRow['item_text'] ?? null)) {
                        continue;
                    }

                    $item = ! empty($itemRow['id'])
                        ? $section->evaluationItems()->find((int) $itemRow['id'])
                        : null;

                    $item ??= new EvaluationItem(['form_id' => $form->id, 'section_id' => $section->id]);

                    $item->fill([
                        'form_id' => $form->id,
                        'section_id' => $section->id,
                        'item_text' => $itemRow['item_text'],
                        'answer_type' => $itemRow['answer_type'],
                        'weight' => $itemRow['weight'] ?? 1,
                        'is_required' => (bool) ($itemRow['is_required'] ?? false),
                        'sort_order' => $itemIndex + 1,
                    ])->save();

                    $keepItems[] = $item->id;
                }

                $keepSections[] = $section->id;

                $section->evaluationItems()
                    ->when($keepItems !== [], fn ($query) => $query->whereNotIn('id', $keepItems))
                    ->get()
                    ->each(function (EvaluationItem $item) {
                        if (! $item->evaluationAnswers()->exists()) {
                            $item->delete();
                        }
                    });
            }

            $form->evaluationSections()
                ->when($keepSections !== [], fn ($query) => $query->whereNotIn('id', $keepSections))
                ->get()
                ->each(function (EvaluationSection $section) {
                    if ($section->evaluationItems()->whereHas('evaluationAnswers')->exists()) {
                        return;
                    }

                    $section->evaluationItems()->delete();
                    $section->delete();
                });

            return $form->refresh();
        });
    }

    public function deleteForm(EvaluationForm $form): void
    {
        if ($form->evaluationSubmissions()->exists() || $form->evaluationParticipation()->exists()) {
            throw ValidationException::withMessages([
                'form' => 'لا يمكن حذف نموذج تقييم له مشاركات أو إجابات.',
            ]);
        }

        DB::transaction(function () use ($form) {
            $form->evaluationItems()->delete();
            $form->evaluationSections()->delete();
            $form->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function submit(EvaluationForm $form, CourseSession $session, array $data, User $evaluator): EvaluationSubmission
    {
        $form->load(['evaluationItems' => fn ($query) => $query->orderBy('sort_order')]);
        $answers = (array) ($data['answers'] ?? []);

        return DB::transaction(function () use ($form, $session, $answers, $evaluator, $data) {
            $enrollmentId = $evaluator->trainee
                ?->enrollments()
                ->where('session_id', $session->id)
                ->value('id');

            $targetTrainerId = null;
            $targetEnrollmentId = null;
            $targetHallId = null;

            if ($form->target_type?->value === 'trainer') {
                $targetTrainerId = $data['target_trainer_id']
                    ?? $session->sessionTrainers()->value('trainer_id');
            } elseif ($form->target_type?->value === 'trainee') {
                $targetEnrollmentId = $data['target_enrollment_id'] ?? $enrollmentId;
            } elseif ($form->target_type?->value === 'venue') {
                $targetHallId = $data['target_hall_id']
                    ?? $session->sessionDays()->whereNotNull('hall_id')->value('hall_id');
            }

            $submission = EvaluationSubmission::create([
                'form_id' => $form->id,
                'session_id' => $session->id,
                'evaluator_type' => $form->evaluator_type,
                'evaluator_user_id' => $evaluator->id,
                'target_type' => $form->target_type,
                'target_trainer_id' => $targetTrainerId,
                'target_enrollment_id' => $targetEnrollmentId,
                'target_hall_id' => $targetHallId,
                'total_score' => $this->score($form, $answers),
                'submitted_at' => now(),
            ]);

            foreach ($form->evaluationItems as $item) {
                $row = (array) ($answers[$item->id] ?? []);
                $score = $row['score_value'] ?? null;
                $text = $row['text_value'] ?? null;

                if ($item->answer_type === EvalAnswerType::Scale15) {
                    $text = null;
                } else {
                    $score = null;
                }

                if (($score === null || $score === '') && ($text === null || trim((string) $text) === '')) {
                    continue;
                }

                EvaluationAnswer::create([
                    'submission_id' => $submission->id,
                    'item_id' => $item->id,
                    'score_value' => $score !== null && $score !== '' ? (int) $score : null,
                    'text_value' => $text !== null && trim((string) $text) !== '' ? $text : null,
                ]);
            }

            EvaluationParticipation::firstOrCreate(
                [
                    'form_id' => $form->id,
                    'session_id' => $session->id,
                    'enrollment_id' => $enrollmentId,
                    'trainer_id' => $evaluator->trainer_id,
                ],
                [
                    'is_submitted' => true,
                    'submitted_at' => now(),
                ],
            );

            return $submission->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $answers
     */
    private function score(EvaluationForm $form, array $answers): ?float
    {
        $weighted = 0.0;
        $maxWeighted = 0.0;

        foreach ($form->evaluationItems as $item) {
            if ($item->answer_type !== EvalAnswerType::Scale15) {
                continue;
            }

            $value = (int) (((array) ($answers[$item->id] ?? []))['score_value'] ?? 0);

            if ($value < 1) {
                continue;
            }

            $weight = (float) $item->weight;
            $weighted += $value * $weight;
            $maxWeighted += 5 * $weight;
        }

        return $maxWeighted > 0 ? round(($weighted / $maxWeighted) * 100, 2) : null;
    }
}
