<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainerAttendance extends Model
{
    use HasFactory;
    protected $table = 'trainer_attendance';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'status' => AttendanceStatus::class,
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

    public function checkInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'check_in_by');
    }

    public function checkOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'check_out_by');
    }

    public function excuse(): BelongsTo
    {
        return $this->belongsTo(AttendanceExcuse::class, 'excuse_id');
    }
}
