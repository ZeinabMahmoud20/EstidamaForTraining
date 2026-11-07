<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hall extends Model
{
    use HasFactory;
    protected $table = 'halls';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function equipment(): BelongsToMany
    {
        return $this->belongsToMany(Equipment::class, 'hall_equipment', 'hall_id', 'equipment_id')->withPivot('quantity');
    }

    public function hallBlocks(): HasMany
    {
        return $this->hasMany(HallBlock::class, 'hall_id');
    }

    public function sessionDays(): HasMany
    {
        return $this->hasMany(SessionDay::class, 'hall_id');
    }

    public function hallChangeLogAsOldHall(): HasMany
    {
        return $this->hasMany(HallChangeLog::class, 'old_hall_id');
    }

    public function hallChangeLogAsNewHall(): HasMany
    {
        return $this->hasMany(HallChangeLog::class, 'new_hall_id');
    }

    public function evaluationParticipation(): HasMany
    {
        return $this->hasMany(EvaluationParticipation::class, 'target_hall_id');
    }

    public function evaluationSubmissions(): HasMany
    {
        return $this->hasMany(EvaluationSubmission::class, 'target_hall_id');
    }
}
