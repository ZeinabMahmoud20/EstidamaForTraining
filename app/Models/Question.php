<?php

namespace App\Models;

use App\Enums\QuestionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;
    protected $table = 'questions';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'question_type' => QuestionType::class,
            'default_points' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'bank_id');
    }

    public function questionOptions(): HasMany
    {
        return $this->hasMany(QuestionOption::class, 'question_id');
    }

    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'exam_questions', 'question_id', 'exam_id')->withPivot('points', 'sort_order');
    }

    public function examAnswers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class, 'question_id');
    }
}
