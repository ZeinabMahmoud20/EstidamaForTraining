<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Equipment extends Model
{
    use HasFactory;
    protected $table = 'equipment';
    protected $guarded = [];

    public function halls(): BelongsToMany
    {
        return $this->belongsToMany(Hall::class, 'hall_equipment', 'equipment_id', 'hall_id')->withPivot('quantity');
    }
}
