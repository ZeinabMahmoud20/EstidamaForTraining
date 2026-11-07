<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAnswer extends Model
{
    use HasFactory;
    protected $table = 'exam_answers';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'points_awarded' => 'decimal:2',
        ];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(ExamResult::class, 'result_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    public function selectedOption(): BelongsTo
    {
        return $this->belongsTo(QuestionOption::class, 'selected_option_id');
    }
}
