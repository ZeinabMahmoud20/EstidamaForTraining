<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingProvider extends Model
{
    use HasFactory;
    protected $table = 'training_providers';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function trainers(): HasMany
    {
        return $this->hasMany(Trainer::class, 'provider_id');
    }

    public function traineeProviders(): HasMany
    {
        return $this->hasMany(TraineeProvider::class, 'provider_id');
    }

    public function courseSessions(): HasMany
    {
        return $this->hasMany(CourseSession::class, 'provider_id');
    }

    public function nominations(): HasMany
    {
        return $this->hasMany(Nomination::class, 'nominated_by_provider_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'provider_id');
    }
}
