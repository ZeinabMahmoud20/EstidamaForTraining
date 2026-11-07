<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EducationLevel extends Model
{
    use HasFactory;
    protected $table = 'education_levels';
    protected $guarded = [];

    public function trainees(): HasMany
    {
        return $this->hasMany(Trainee::class, 'education_level_id');
    }
}
