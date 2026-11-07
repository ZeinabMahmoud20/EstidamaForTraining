<?php

namespace App\Models;

use App\Enums\EvalAnswerType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationItem extends Model
{
    use HasFactory;
    protected $table = 'evaluation_items';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'answer_type' => EvalAnswerType::class,
            'weight' => 'decimal:2',
            'is_required' => 'boolean',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(EvaluationForm::class, 'form_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(EvaluationSection::class, 'section_id');
    }

    public function evaluationAnswers(): HasMany
    {
        return $this->hasMany(EvaluationAnswer::class, 'item_id');
    }
}
