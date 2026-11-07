<?php

namespace App\Models;

use App\Enums\Gender;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Trainee extends Model
{
    use HasFactory;
    protected $table = 'trainees';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'birth_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'trainee_id');
    }

    public function educationLevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class, 'education_level_id');
    }

    public function jobGrade(): BelongsTo
    {
        return $this->belongsTo(JobGrade::class, 'job_grade_id');
    }

    public function traineeProviders(): HasMany
    {
        return $this->hasMany(TraineeProvider::class, 'trainee_id');
    }

    public function traineeWarnings(): HasMany
    {
        return $this->hasMany(TraineeWarning::class, 'trainee_id');
    }

    public function nominations(): HasMany
    {
        return $this->hasMany(Nomination::class, 'trainee_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'trainee_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'trainee_id');
    }
}
