<?php

namespace App\Models;

use App\Enums\EvalEvaluator;
use App\Enums\EvalTarget;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationSubmission extends Model
{
    use HasFactory;
    protected $table = 'evaluation_submissions';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'evaluator_type' => EvalEvaluator::class,
            'target_type' => EvalTarget::class,
            'total_score' => 'decimal:2',
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

    public function evaluatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_user_id');
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

    public function evaluationAnswers(): HasMany
    {
        return $this->hasMany(EvaluationAnswer::class, 'submission_id');
    }
}
