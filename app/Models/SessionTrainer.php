<?php

namespace App\Models;

use App\Enums\TrainerRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionTrainer extends Model
{
    use HasFactory;
    protected $table = 'session_trainers';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'planned_role' => TrainerRole::class,
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class, 'session_id');
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
