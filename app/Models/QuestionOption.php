<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionOption extends Model
{
    use HasFactory;
    protected $table = 'question_options';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    public function examAnswers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class, 'selected_option_id');
    }
}
