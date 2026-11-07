<?php

namespace App\Models;

use App\Enums\TrainerRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionDayTrainer extends Model
{
    use HasFactory;
    protected $table = 'session_day_trainers';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'actual_role' => TrainerRole::class,
            'actual_hours' => 'decimal:2',
        ];
    }

    public function sessionDay(): BelongsTo
    {
        return $this->belongsTo(SessionDay::class, 'session_day_id');
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer_id');
    }

    public function replacedTrainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'replaced_trainer_id');
    }
}
