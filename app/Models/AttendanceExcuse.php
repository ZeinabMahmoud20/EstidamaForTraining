<?php

namespace App\Models;

use App\Enums\ExcuseStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceExcuse extends Model
{
    use HasFactory;
    protected $table = 'attendance_excuses';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => ExcuseStatus::class,
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function traineeAttendance(): HasMany
    {
        return $this->hasMany(TraineeAttendance::class, 'excuse_id');
    }

    public function trainerAttendance(): HasMany
    {
        return $this->hasMany(TrainerAttendance::class, 'excuse_id');
    }
}
