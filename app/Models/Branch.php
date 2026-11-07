<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;
    protected $table = 'branches';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function branchAccreditations(): HasMany
    {
        return $this->hasMany(BranchAccreditation::class, 'branch_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'branch_id');
    }

    public function trainers(): HasMany
    {
        return $this->hasMany(Trainer::class, 'branch_id');
    }

    public function halls(): HasMany
    {
        return $this->hasMany(Hall::class, 'branch_id');
    }

    public function courseSessions(): HasMany
    {
        return $this->hasMany(CourseSession::class, 'branch_id');
    }
}
