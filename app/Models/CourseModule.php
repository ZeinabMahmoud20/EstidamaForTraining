<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseModule extends Model
{
    use HasFactory;
    protected $table = 'course_modules';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'hours' => 'decimal:2',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function sessionDayModules(): HasMany
    {
        return $this->hasMany(SessionDayModule::class, 'module_id');
    }

    public function trainingKits(): HasMany
    {
        return $this->hasMany(TrainingKit::class, 'module_id');
    }

    public function questionBanks(): HasMany
    {
        return $this->hasMany(QuestionBank::class, 'module_id');
    }
}
