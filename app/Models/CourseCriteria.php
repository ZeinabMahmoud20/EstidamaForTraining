<?php

namespace App\Models;

use App\Enums\CriteriaOperator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseCriteria extends Model
{
    use HasFactory;
    protected $table = 'course_criteria';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'operator' => CriteriaOperator::class,
            'is_mandatory' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function criteriaType(): BelongsTo
    {
        return $this->belongsTo(CriteriaType::class, 'criteria_type_id');
    }
}
