<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HallChangeLog extends Model
{
    use HasFactory;
    protected $table = 'hall_change_log';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }

    public function sessionDay(): BelongsTo
    {
        return $this->belongsTo(SessionDay::class, 'session_day_id');
    }

    public function oldHall(): BelongsTo
    {
        return $this->belongsTo(Hall::class, 'old_hall_id');
    }

    public function newHall(): BelongsTo
    {
        return $this->belongsTo(Hall::class, 'new_hall_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
