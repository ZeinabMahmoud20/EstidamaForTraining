<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchAccreditation extends Model
{
    use HasFactory;
    protected $table = 'branch_accreditations';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function accreditation(): BelongsTo
    {
        return $this->belongsTo(Accreditation::class, 'accreditation_id');
    }
}
