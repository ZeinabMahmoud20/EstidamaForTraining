<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\NominationSource;
use App\Enums\NominationStatus;
use App\Enums\SessionStatus;
use App\Models\CourseSession;
use App\Models\Nomination;
use App\Models\NominationStatusHistory;
use App\Models\Trainee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Writes nominations and moves them through their status lifecycle.
 */
class NominationService
{
    public function __construct(private readonly NominationEligibilityService $eligibility) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Nomination
    {
        return DB::transaction(function () use ($data, $actor) {
            $session = CourseSession::with('course')->findOrFail($data['session_id']);
            $trainee = Trainee::findOrFail($data['trainee_id']);

            $this->assertNominationWindow($session, $trainee);
            $assessment = $this->assertEligibility($session, $trainee, $actor, (bool) ($data['override'] ?? false));

            $nomination = Nomination::create([
                'session_id' => $session->id,
                'trainee_id' => $trainee->id,
                'source' => $data['source'],
                'nominated_by_provider_id' => $data['source'] === NominationSource::Provider->value
                    ? ($data['nominated_by_provider_id'] ?? null)
                    : null,
                'application_date' => now(),
                'application_data' => [
                    'notes' => $data['notes'] ?? null,
                    'criteria_override' => (bool) ($data['override'] ?? false),
                    'criteria' => $assessment['items'],
                ],
                'status' => NominationStatus::Submitted,
            ]);

            $this->writeHistory($nomination, null, NominationStatus::Submitted, $actor, 'إنشاء الترشيح');

            return $nomination;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Nomination $nomination, array $data, User $actor): Nomination
    {
        if (! in_array($nomination->status, [NominationStatus::Submitted, NominationStatus::UnderReview], true)) {
            throw ValidationException::withMessages([
                'nomination' => 'لا يمكن تعديل ترشيح بعد اتخاذ قرار نهائي بشأنه.',
            ]);
        }

        return DB::transaction(function () use ($nomination, $data, $actor) {
            $session = CourseSession::with('course')->findOrFail($data['session_id']);
            $trainee = Trainee::findOrFail($data['trainee_id']);

            $this->assertNominationWindow($session, $trainee);
            $assessment = $this->assertEligibility($session, $trainee, $actor, (bool) ($data['override'] ?? false));

            $nomination->update([
                'session_id' => $session->id,
                'trainee_id' => $trainee->id,
                'source' => $data['source'],
                'nominated_by_provider_id' => $data['source'] === NominationSource::Provider->value
                    ? ($data['nominated_by_provider_id'] ?? null)
                    : null,
                'application_data' => [
                    'notes' => $data['notes'] ?? null,
                    'criteria_override' => (bool) ($data['override'] ?? false),
                    'criteria' => $assessment['items'],
                ],
            ]);

            return $nomination->refresh();
        });
    }

    public function transition(
        Nomination $nomination,
        NominationStatus $to,
        ?string $reason,
        User $actor,
        bool $override = false,
    ): Nomination {
        if (! in_array($to, $nomination->status->nextStatuses(), true)) {
            throw ValidationException::withMessages([
                'status' => 'لا يمكن الانتقال من الحالة «'.$nomination->status->label().'» إلى «'.$to->label().'».',
            ]);
        }

        return DB::transaction(function () use ($nomination, $to, $reason, $actor, $override) {
            $from = $nomination->status;

            if ($to === NominationStatus::Approved) {
                $nomination->loadMissing(['session.course', 'trainee.educationLevel', 'trainee.jobGrade', 'trainee.traineeWarnings', 'trainee.enrollments']);
                $this->assertNominationWindow($nomination->session, $nomination->trainee);
                $this->assertEligibility($nomination->session, $nomination->trainee, $actor, $override);
                $this->assertCapacity($nomination->session, $nomination);
            }

            if ($to === NominationStatus::Withdrawn && $nomination->enrollment) {
                if ($nomination->enrollment->status === EnrollmentStatus::Completed || $nomination->enrollment->certificate()->exists()) {
                    throw ValidationException::withMessages([
                        'status' => 'لا يمكن سحب ترشيح مرتبط بتسجيل مكتمل أو شهادة صادرة.',
                    ]);
                }

                $nomination->enrollment->update(['status' => EnrollmentStatus::Withdrawn]);
            }

            $attributes = ['status' => $to];

            if (in_array($to, [NominationStatus::Approved, NominationStatus::Rejected, NominationStatus::Waitlisted], true)) {
                $attributes['decision_by'] = $actor->id;
                $attributes['decision_at'] = now();
                $attributes['decision_reason'] = $reason;
            }

            if ($to === NominationStatus::Waitlisted) {
                $attributes['waitlist_order'] = ((int) Nomination::query()
                    ->where('session_id', $nomination->session_id)
                    ->where('status', NominationStatus::Waitlisted)
                    ->max('waitlist_order')) + 1;
            }

            $nomination->update($attributes);

            if ($to === NominationStatus::Approved && ! $nomination->enrollment()->exists()) {
                $nomination->enrollment()->create([
                    'session_id' => $nomination->session_id,
                    'trainee_id' => $nomination->trainee_id,
                    'status' => EnrollmentStatus::Enrolled,
                    'attendance_pct' => null,
                    'final_score_pct' => null,
                    'is_passed' => null,
                    'is_certificate_eligible' => null,
                ]);
            }

            $this->writeHistory($nomination, $from, $to, $actor, $reason);

            return $nomination->refresh();
        });
    }

    public function delete(Nomination $nomination): void
    {
        if ($nomination->enrollment()->exists()) {
            throw ValidationException::withMessages([
                'nomination' => 'لا يمكن حذف ترشيح مرتبط بتسجيل. احذف التسجيل أولًا.',
            ]);
        }

        DB::transaction(function () use ($nomination) {
            $nomination->nominationStatusHistory()->delete();
            $nomination->delete();
        });
    }

    private function assertNominationWindow(CourseSession $session, Trainee $trainee): void
    {
        if (! $trainee->is_active) {
            throw ValidationException::withMessages([
                'trainee_id' => 'لا يمكن ترشيح متدرب غير نشط.',
            ]);
        }

        if (in_array($session->status, [SessionStatus::Cancelled, SessionStatus::Completed], true)) {
            throw ValidationException::withMessages([
                'session_id' => 'لا يمكن الترشيح في جلسة ملغاة أو مكتملة.',
            ]);
        }
    }

    /**
     * @return array{items: list<array<string, mixed>>, blocking: bool}
     */
    private function assertEligibility(CourseSession $session, Trainee $trainee, User $actor, bool $override): array
    {
        $assessment = $this->eligibility->evaluate($session, $trainee);

        if ($assessment['blocking'] && (! $override || ! $actor->can('OVERRIDE_CRITERIA'))) {
            throw ValidationException::withMessages([
                'override' => $actor->can('OVERRIDE_CRITERIA')
                    ? 'المتدرب لا يستوفي بعض معايير الالتحاق. فعّل تجاوز المعايير للمتابعة.'
                    : 'المتدرب لا يستوفي معايير الالتحاق، ولا تملك صلاحية تجاوزها.',
            ]);
        }

        return $assessment;
    }

    private function assertCapacity(CourseSession $session, Nomination $nomination): void
    {
        $max = $session->max_trainees;

        if ($max === null) {
            return;
        }

        $alreadyEnrolled = $session->enrollments()
            ->whereNotIn('status', [EnrollmentStatus::Withdrawn, EnrollmentStatus::Dismissed])
            ->when($nomination->enrollment, fn ($query, $enrollment) => $query->whereKeyNot($enrollment->id))
            ->count();

        if ($alreadyEnrolled >= (int) $max) {
            throw ValidationException::withMessages([
                'status' => 'الجلسة مكتملة العدد. أضف المتدرب إلى قائمة الانتظار بدلًا من الاعتماد.',
            ]);
        }
    }

    private function writeHistory(
        Nomination $nomination,
        ?NominationStatus $from,
        NominationStatus $to,
        User $actor,
        ?string $note,
    ): NominationStatusHistory {
        return NominationStatusHistory::create([
            'nomination_id' => $nomination->id,
            'from_status' => $from,
            'to_status' => $to,
            'changed_by' => $actor->id,
            'changed_at' => now(),
            'note' => $note,
        ]);
    }
}
