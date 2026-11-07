<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobGrade extends Model
{
    use HasFactory;
    protected $table = 'job_grades';
    protected $guarded = [];

    public function trainees(): HasMany
    {
        return $this->hasMany(Trainee::class, 'job_grade_id');
    }
}
