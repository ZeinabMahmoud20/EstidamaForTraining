<?php

namespace App\Models;

use App\Enums\EvalEvaluator;
use App\Enums\EvalTarget;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationForm extends Model
{
    use HasFactory;
    protected $table = 'evaluation_forms';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'target_type' => EvalTarget::class,
            'evaluator_type' => EvalEvaluator::class,
            'is_anonymous' => 'boolean',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function evaluationSections(): HasMany
    {
        return $this->hasMany(EvaluationSection::class, 'form_id');
    }

    public function evaluationItems(): HasMany
    {
        return $this->hasMany(EvaluationItem::class, 'form_id');
    }

    public function evaluationParticipation(): HasMany
    {
        return $this->hasMany(EvaluationParticipation::class, 'form_id');
    }

    public function evaluationSubmissions(): HasMany
    {
        return $this->hasMany(EvaluationSubmission::class, 'form_id');
    }
}
