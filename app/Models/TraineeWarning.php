<?php

namespace App\Models;

use App\Enums\WarningType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TraineeWarning extends Model
{
    use HasFactory;
    protected $table = 'trainee_warnings';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'warning_type' => WarningType::class,
            'issued_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function trainee(): BelongsTo
    {
        return $this->belongsTo(Trainee::class, 'trainee_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class, 'session_id');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
