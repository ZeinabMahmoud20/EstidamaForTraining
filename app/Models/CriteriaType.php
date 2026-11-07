<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CriteriaType extends Model
{
    use HasFactory;
    protected $table = 'criteria_types';
    protected $guarded = [];

    public function courseCriteria(): HasMany
    {
        return $this->hasMany(CourseCriteria::class, 'criteria_type_id');
    }
}
