<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationAnswer extends Model
{
    use HasFactory;
    protected $table = 'evaluation_answers';
    protected $guarded = [];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(EvaluationSubmission::class, 'submission_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(EvaluationItem::class, 'item_id');
    }
}
