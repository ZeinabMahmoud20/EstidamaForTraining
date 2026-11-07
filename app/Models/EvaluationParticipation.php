<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationParticipation extends Model
{
    use HasFactory;
    protected $table = 'evaluation_participation';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_submitted' => 'boolean',
            'submitted_at' => 'datetime',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(EvaluationForm::class, 'form_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class, 'session_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id');
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer_id');
    }

    public function targetTrainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'target_trainer_id');
    }

    public function targetEnrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'target_enrollment_id');
    }

    public function targetHall(): BelongsTo
    {
        return $this->belongsTo(Hall::class, 'target_hall_id');
    }
}
