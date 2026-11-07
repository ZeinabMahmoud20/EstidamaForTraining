<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionDayModule extends Model
{
    use HasFactory;
    protected $table = 'session_day_modules';
    protected $guarded = [];

    public function sessionDay(): BelongsTo
    {
        return $this->belongsTo(SessionDay::class, 'session_day_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer_id');
    }
}
