<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationSection extends Model
{
    use HasFactory;
    protected $table = 'evaluation_sections';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(EvaluationForm::class, 'form_id');
    }

    public function evaluationItems(): HasMany
    {
        return $this->hasMany(EvaluationItem::class, 'section_id');
    }
}
