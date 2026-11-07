<?php

namespace App\Models;

use App\Enums\NominationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NominationStatusHistory extends Model
{
    use HasFactory;
    protected $table = 'nomination_status_history';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'from_status' => NominationStatus::class,
            'to_status' => NominationStatus::class,
            'changed_at' => 'datetime',
        ];
    }

    public function nomination(): BelongsTo
    {
        return $this->belongsTo(Nomination::class, 'nomination_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
