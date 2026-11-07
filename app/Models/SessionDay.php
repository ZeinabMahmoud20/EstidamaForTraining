<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SessionDay extends Model
{
    use HasFactory;
    protected $table = 'session_days';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'day_date' => 'date',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class, 'session_id');
    }

    public function hall(): BelongsTo
    {
        return $this->belongsTo(Hall::class, 'hall_id');
    }

    public function hallChangeLog(): HasMany
    {
        return $this->hasMany(HallChangeLog::class, 'session_day_id');
    }

    public function sessionDayModules(): HasMany
    {
        return $this->hasMany(SessionDayModule::class, 'session_day_id');
    }

    public function sessionDayTrainers(): HasMany
    {
        return $this->hasMany(SessionDayTrainer::class, 'session_day_id');
    }

    public function traineeAttendance(): HasMany
    {
        return $this->hasMany(TraineeAttendance::class, 'session_day_id');
    }

    public function trainerAttendance(): HasMany
    {
        return $this->hasMany(TrainerAttendance::class, 'session_day_id');
    }
}
