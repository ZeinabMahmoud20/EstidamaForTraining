<?php

namespace App\Models;

use App\Enums\KitStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingKit extends Model
{
    use HasFactory;
    protected $table = 'training_kits';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'submitted_at' => 'datetime',
            'status' => KitStatus::class,
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class, 'session_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
