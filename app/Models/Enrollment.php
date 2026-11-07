<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Enrollment extends Model
{
    use HasFactory;
    protected $table = 'enrollments';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => EnrollmentStatus::class,
            'attendance_pct' => 'decimal:2',
            'final_score_pct' => 'decimal:2',
            'is_passed' => 'boolean',
            'is_certificate_eligible' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function nomination(): BelongsTo
    {
        return $this->belongsTo(Nomination::class, 'nomination_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class, 'session_id');
    }

    public function trainee(): BelongsTo
    {
        return $this->belongsTo(Trainee::class, 'trainee_id');
    }

    public function traineeAttendance(): HasMany
    {
        return $this->hasMany(TraineeAttendance::class, 'enrollment_id');
    }

    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class, 'enrollment_id');
    }

    public function evaluationParticipationAsEnrollment(): HasMany
    {
        return $this->hasMany(EvaluationParticipation::class, 'enrollment_id');
    }

    public function evaluationParticipationAsTargetEnrollment(): HasMany
    {
        return $this->hasMany(EvaluationParticipation::class, 'target_enrollment_id');
    }

    public function evaluationSubmissions(): HasMany
    {
        return $this->hasMany(EvaluationSubmission::class, 'target_enrollment_id');
    }

    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class, 'enrollment_id');
    }
}
