<?php

namespace App\Models;

use App\Enums\ExamType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasFactory;
    protected $table = 'exams';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'exam_type' => ExamType::class,
            'scheduled_start' => 'datetime',
            'total_score' => 'decimal:2',
            'pass_score' => 'decimal:2',
            'weight_pct' => 'decimal:2',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class, 'session_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_questions', 'exam_id', 'question_id')->withPivot('points', 'sort_order');
    }

    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class, 'exam_id');
    }
}
