<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionBank extends Model
{
    use HasFactory;
    protected $table = 'question_banks';
    protected $guarded = [];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'bank_id');
    }
}
