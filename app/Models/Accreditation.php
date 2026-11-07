<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Accreditation extends Model
{
    use HasFactory;
    protected $table = 'accreditations';
    protected $guarded = [];

    public function branchAccreditations(): HasMany
    {
        return $this->hasMany(BranchAccreditation::class, 'accreditation_id');
    }

    public function courseSessions(): HasMany
    {
        return $this->hasMany(CourseSession::class, 'accreditation_id');
    }
}
