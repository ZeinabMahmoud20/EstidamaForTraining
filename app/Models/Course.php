<?php

namespace App\Models;

use App\Enums\CourseLevel;
use App\Enums\DeliveryMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;
    protected $table = 'courses';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'level' => CourseLevel::class,
            'total_hours' => 'decimal:2',
            'default_delivery_mode' => DeliveryMode::class,
            'price' => 'decimal:2',
            'min_attendance_pct' => 'decimal:2',
            'pass_score_pct' => 'decimal:2',
            'extra_details' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function trainerCourseApprovals(): HasMany
    {
        return $this->hasMany(TrainerCourseApproval::class, 'course_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CourseCategory::class, 'category_id');
    }

    public function courseType(): BelongsTo
    {
        return $this->belongsTo(CourseType::class, 'course_type_id');
    }

    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_prerequisites', 'course_id', 'prerequisite_course_id');
    }

    public function requiredFor(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_prerequisites', 'prerequisite_course_id', 'course_id');
    }

    public function courseModules(): HasMany
    {
        return $this->hasMany(CourseModule::class, 'course_id');
    }

    public function courseCriteria(): HasMany
    {
        return $this->hasMany(CourseCriteria::class, 'course_id');
    }

    public function courseSessions(): HasMany
    {
        return $this->hasMany(CourseSession::class, 'course_id');
    }

    public function questionBanks(): HasMany
    {
        return $this->hasMany(QuestionBank::class, 'course_id');
    }
}
