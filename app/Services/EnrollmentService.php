<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\NominationStatus;
use App\Models\Enrollment;
use App\Models\Nomination;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Creates and maintains enrollments, including attendance percentage calculation.
 */
class EnrollmentService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createFromNomination(Nomination $nomination, array $data): Enrollment
    {
        if ($nomination->status !== NominationStatus::Approved) {
            throw ValidationException::withMessages([
                'nomination_id' => 'لا يمكن تسجيل متدرب إلا من ترشيح معتمد.',
            ]);
        }

        if ($nomination->enrollment()->exists()) {
            throw ValidationException::withMessages([
                'nomination_id' => 'هذا الترشيح مرتبط بتسجيل بالفعل.',
            ]);
        }

        $session = $nomination->session;

        if ($session?->max_trainees !== null) {
            $count = $session->enrollments()
                ->whereNotIn('status', [EnrollmentStatus::Withdrawn, EnrollmentStatus::Dismissed])
                ->count();

            if ($count >= (int) $session->max_trainees) {
                throw ValidationException::withMessages([
                    'nomination_id' => 'الجلسة مكتملة العدد. لا يمكن إضافة تسجيل جديد.',
                ]);
            }
        }

        return DB::transaction(fn () => Enrollment::create([
            'nomination_id' => $nomination->id,
            'session_id' => $nomination->session_id,
            'trainee_id' => $nomination->trainee_id,
            'status' => $data['status'] ?? EnrollmentStatus::Enrolled,
            'attendance_pct' => $data['attendance_pct'] ?? null,
            'final_score_pct' => $data['final_score_pct'] ?? null,
            'is_passed' => $data['is_passed'] ?? null,
            'is_certificate_eligible' => $data['is_certificate_eligible'] ?? null,
            'completed_at' => $data['completed_at'] ?? null,
        ]));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Enrollment $enrollment, array $data): Enrollment
    {
        $status = $data['status'] ?? null;
        if ($status instanceof EnrollmentStatus) {
            $status = $status->value;
            $data['status'] = $status;
        }

        if ($status === EnrollmentStatus::Completed->value && blank($data['completed_at'] ?? null)) {
            $data['completed_at'] = now();
        }

        $enrollment->update($data);

        return $enrollment->refresh();
    }

    public function recalculateAttendance(Enrollment $enrollment): Enrollment
    {
        $totalDays = $enrollment->session()->first()?->sessionDays()->count() ?? 0;

        if ($totalDays === 0) {
            $enrollment->update(['attendance_pct' => null]);

            return $enrollment->refresh();
        }

        $attended = $enrollment->traineeAttendance()
            ->whereIn('status', [
                AttendanceStatus::Present,
                AttendanceStatus::Late,
                AttendanceStatus::EarlyLeave,
            ])
            ->count();

        $percentage = round(($attended / $totalDays) * 100, 2);

        $enrollment->update(['attendance_pct' => $percentage]);

        return $enrollment->refresh();
    }

    public function delete(Enrollment $enrollment): void
    {
        if ($blockers = $this->deletionBlockers($enrollment)) {
            throw ValidationException::withMessages([
                'enrollment' => 'لا يمكن حذف التسجيل لارتباطه بـ: '.implode('، ', $blockers).'.',
            ]);
        }

        $enrollment->delete();
    }

    /**
     * @return list<string>
     */
    public function deletionBlockers(Enrollment $enrollment): array
    {
        $blockers = [];

        if ($enrollment->traineeAttendance()->exists()) {
            $blockers[] = 'سجلات حضور';
        }
        if ($enrollment->examResults()->exists()) {
            $blockers[] = 'نتائج اختبارات';
        }
        if ($enrollment->evaluationParticipationAsEnrollment()->exists()
            || $enrollment->evaluationParticipationAsTargetEnrollment()->exists()
            || $enrollment->evaluationSubmissions()->exists()) {
            $blockers[] = 'تقييمات';
        }
        if ($enrollment->certificate()->exists()) {
            $blockers[] = 'شهادة صادرة';
        }

        return $blockers;
    }
}
