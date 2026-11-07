<?php

namespace App\Models;

use App\Enums\BillTo;
use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;
    protected $table = 'invoices';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'bill_to' => BillTo::class,
            'amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'status' => InvoiceStatus::class,
            'issue_date' => 'date',
            'due_date' => 'date',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class, 'session_id');
    }

    public function trainee(): BelongsTo
    {
        return $this->belongsTo(Trainee::class, 'trainee_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(TrainingProvider::class, 'provider_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'invoice_id');
    }
}
