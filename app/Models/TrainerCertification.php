<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainerCertification extends Model
{
    use HasFactory;
    protected $table = 'trainer_certifications';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer_id');
    }
}
