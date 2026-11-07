<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainerPayment extends Model
{
    use HasFactory;
    protected $table = 'trainer_payments';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'total_hours' => 'decimal:2',
            'hourly_rate' => 'decimal:2',
            'amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class, 'session_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
