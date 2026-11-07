<?php

namespace App\Models;

use App\Enums\DeliveryMode;
use App\Enums\SessionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseSession extends Model
{
    use HasFactory;
    protected $table = 'course_sessions';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'delivery_mode' => DeliveryMode::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'registration_open' => 'date',
            'registration_close' => 'date',
            'price' => 'decimal:2',
            'status' => SessionStatus::class,
        ];
    }

    public function traineeWarnings(): HasMany
    {
        return $this->hasMany(TraineeWarning::class, 'session_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(TrainingProvider::class, 'provider_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function accreditation(): BelongsTo
    {
        return $this->belongsTo(Accreditation::class, 'accreditation_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sessionDays(): HasMany
    {
        return $this->hasMany(SessionDay::class, 'session_id');
    }

    public function sessionTrainers(): HasMany
    {
        return $this->hasMany(SessionTrainer::class, 'session_id');
    }

    public function trainingKits(): HasMany
    {
        return $this->hasMany(TrainingKit::class, 'session_id');
    }

    public function nominations(): HasMany
    {
        return $this->hasMany(Nomination::class, 'session_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'session_id');
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class, 'session_id');
    }

    public function evaluationParticipation(): HasMany
    {
        return $this->hasMany(EvaluationParticipation::class, 'session_id');
    }

    public function evaluationSubmissions(): HasMany
    {
        return $this->hasMany(EvaluationSubmission::class, 'session_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'session_id');
    }

    public function trainerPayments(): HasMany
    {
        return $this->hasMany(TrainerPayment::class, 'session_id');
    }
}
