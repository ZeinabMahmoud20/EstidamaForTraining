<?php

namespace App\Models;

use App\Enums\NominationSource;
use App\Enums\NominationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Nomination extends Model
{
    use HasFactory;
    protected $table = 'nominations';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'source' => NominationSource::class,
            'application_date' => 'datetime',
            'application_data' => 'array',
            'status' => NominationStatus::class,
            'decision_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class, 'session_id');
    }

    public function trainee(): BelongsTo
    {
        return $this->belongsTo(Trainee::class, 'trainee_id');
    }

    public function nominatedByProvider(): BelongsTo
    {
        return $this->belongsTo(TrainingProvider::class, 'nominated_by_provider_id');
    }

    public function decisionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decision_by');
    }

    public function nominationStatusHistory(): HasMany
    {
        return $this->hasMany(NominationStatusHistory::class, 'nomination_id');
    }

    public function enrollment(): HasOne
    {
        return $this->hasOne(Enrollment::class, 'nomination_id');
    }
}
