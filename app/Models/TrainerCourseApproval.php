<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainerCourseApproval extends Model
{
    use HasFactory;
    protected $table = 'trainer_course_approvals';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
            'approved_at' => 'date',
        ];
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
