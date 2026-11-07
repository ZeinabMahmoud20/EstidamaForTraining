<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainerExperience extends Model
{
    use HasFactory;
    protected $table = 'trainer_experiences';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
        ];
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer_id');
    }
}
