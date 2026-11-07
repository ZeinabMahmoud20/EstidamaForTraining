<?php

namespace App\Models;

use App\Enums\ExamResultStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamResult extends Model
{
    use HasFactory;
    protected $table = 'exam_results';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'percentage' => 'decimal:2',
            'is_passed' => 'boolean',
            'status' => ExamResultStatus::class,
            'taken_at' => 'datetime',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id');
    }

    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function examAnswers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class, 'result_id');
    }
}
