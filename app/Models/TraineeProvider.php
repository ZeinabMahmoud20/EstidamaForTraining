<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TraineeProvider extends Model
{
    use HasFactory;
    protected $table = 'trainee_providers';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
            'is_primary' => 'boolean',
        ];
    }

    public function trainee(): BelongsTo
    {
        return $this->belongsTo(Trainee::class, 'trainee_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(TrainingProvider::class, 'provider_id');
    }
}
