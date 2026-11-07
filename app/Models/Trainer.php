<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\TrainerType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Trainer extends Model
{
    use HasFactory;
    protected $table = 'trainers';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'trainer_type' => TrainerType::class,
            'gender' => Gender::class,
            'birth_date' => 'date',
            'hourly_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'trainer_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(TrainingProvider::class, 'provider_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function trainerCertifications(): HasMany
    {
        return $this->hasMany(TrainerCertification::class, 'trainer_id');
    }

    public function trainerExperiences(): HasMany
    {
        return $this->hasMany(TrainerExperience::class, 'trainer_id');
    }

    public function courseCategories(): BelongsToMany
    {
        return $this->belongsToMany(CourseCategory::class, 'trainer_specialties', 'trainer_id', 'category_id');
    }

    public function trainerCourseApprovals(): HasMany
    {
        return $this->hasMany(TrainerCourseApproval::class, 'trainer_id');
    }

    public function sessionDayModules(): HasMany
    {
        return $this->hasMany(SessionDayModule::class, 'trainer_id');
    }

    public function sessionTrainers(): HasMany
    {
        return $this->hasMany(SessionTrainer::class, 'trainer_id');
    }

    public function sessionDayTrainersAsTrainer(): HasMany
    {
        return $this->hasMany(SessionDayTrainer::class, 'trainer_id');
    }

    public function sessionDayTrainersAsReplacedTrainer(): HasMany
    {
        return $this->hasMany(SessionDayTrainer::class, 'replaced_trainer_id');
    }

    public function trainingKits(): HasMany
    {
        return $this->hasMany(TrainingKit::class, 'trainer_id');
    }

    public function trainerAttendance(): HasMany
    {
        return $this->hasMany(TrainerAttendance::class, 'trainer_id');
    }

    public function evaluationParticipationAsTrainer(): HasMany
    {
        return $this->hasMany(EvaluationParticipation::class, 'trainer_id');
    }

    public function evaluationParticipationAsTargetTrainer(): HasMany
    {
        return $this->hasMany(EvaluationParticipation::class, 'target_trainer_id');
    }

    public function evaluationSubmissions(): HasMany
    {
        return $this->hasMany(EvaluationSubmission::class, 'target_trainer_id');
    }

    public function trainerPayments(): HasMany
    {
        return $this->hasMany(TrainerPayment::class, 'trainer_id');
    }
}
