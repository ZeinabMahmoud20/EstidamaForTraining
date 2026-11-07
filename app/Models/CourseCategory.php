<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseCategory extends Model
{
    use HasFactory;
    protected $table = 'course_categories';
    protected $guarded = [];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CourseCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(CourseCategory::class, 'parent_id');
    }

    public function trainers(): BelongsToMany
    {
        return $this->belongsToMany(Trainer::class, 'trainer_specialties', 'category_id', 'trainer_id');
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'category_id');
    }
}
