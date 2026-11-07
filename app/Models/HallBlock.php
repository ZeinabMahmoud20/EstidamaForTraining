<?php

namespace App\Models;

use App\Enums\HallBlockReason;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HallBlock extends Model
{
    use HasFactory;
    protected $table = 'hall_blocks';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'reason' => HallBlockReason::class,
        ];
    }

    public function hall(): BelongsTo
    {
        return $this->belongsTo(Hall::class, 'hall_id');
    }
}
