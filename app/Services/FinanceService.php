<?php

namespace App\Services;

use App\Enums\BillTo;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\TrainerPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Maintains invoices, payments and trainer dues.
 */
class FinanceService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function saveInvoice(Invoice $invoice, array $data): Invoice
    {
        $quantity = max(1, (int) ($data['quantity'] ?? 1));
        $amount = round((float) $data['amount'], 2);
        $tax = round((float) ($data['tax_amount'] ?? 0), 2);
        $billTo = BillTo::from((string) $data['bill_to']);

        return DB::transaction(function () use ($invoice, $data, $quantity, $amount, $tax, $billTo) {
            $invoice->fill([
                'invoice_no' => $invoice->invoice_no ?: $this->invoiceNumber(),
                'session_id' => $data['session_id'],
                'bill_to' => $billTo,
                'trainee_id' => $billTo === BillTo::Trainee ? ($data['trainee_id'] ?? null) : null,
                'provider_id' => $billTo === BillTo::Provider ? ($data['provider_id'] ?? null) : null,
                'quantity' => $quantity,
                'amount' => $amount,
                'tax_amount' => $tax,
                'total_amount' => round(($amount * $quantity) + $tax, 2),
                'status' => $data['status'] ?? InvoiceStatus::Draft,
                'issue_date' => $data['issue_date'] ?? null,
                'due_date' => $data['due_date'] ?? null,
            ])->save();

            return $invoice->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addPayment(Invoice $invoice, array $data, User $actor): Payment
    {
        if ($invoice->status === InvoiceStatus::Cancelled) {
            throw ValidationException::withMessages([
                'amount' => 'لا يمكن تسجيل دفعة على فاتورة ملغاة.',
            ]);
        }

        $amount = round((float) $data['amount'], 2);
        $paid = round((float) $invoice->payments()->sum('amount'), 2);
        $remaining = round((float) $invoice->total_amount - $paid, 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'قيمة الدفعة يجب أن تكون أكبر من صفر.',
            ]);
        }

        if ($amount > $remaining + 0.001) {
            throw ValidationException::withMessages([
                'amount' => 'قيمة الدفعة تتجاوز المتبقي على الفاتورة ('.$remaining.').',
            ]);
        }

        return DB::transaction(function () use ($invoice, $data, $actor, $amount) {
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'method' => $data['method'] ?? null,
                'reference_no' => $data['reference_no'] ?? null,
                'paid_at' => $data['paid_at'] ?? now(),
                'received_by' => $actor->id,
            ]);

            $this->refreshInvoiceStatus($invoice->refresh());

            return $payment;
        });
    }

    public function refreshInvoiceStatus(Invoice $invoice): Invoice
    {
        if ($invoice->status === InvoiceStatus::Cancelled) {
            return $invoice;
        }

        $paid = round((float) $invoice->payments()->sum('amount'), 2);
        $total = round((float) $invoice->total_amount, 2);

        $status = match (true) {
            $paid >= $total && $total > 0 => InvoiceStatus::Paid,
            $paid > 0 => InvoiceStatus::PartiallyPaid,
            $invoice->issue_date !== null => InvoiceStatus::Issued,
            default => InvoiceStatus::Draft,
        };

        if ($invoice->status !== $status) {
            $invoice->update(['status' => $status]);
        }

        return $invoice->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveTrainerPayment(TrainerPayment $trainerPayment, array $data): TrainerPayment
    {
        $hours = isset($data['total_hours']) && $data['total_hours'] !== '' ? (float) $data['total_hours'] : null;
        $rate = isset($data['hourly_rate']) && $data['hourly_rate'] !== '' ? (float) $data['hourly_rate'] : null;
        $amount = isset($data['amount']) && $data['amount'] !== ''
            ? round((float) $data['amount'], 2)
            : ($hours !== null && $rate !== null ? round($hours * $rate, 2) : null);

        $trainerPayment->fill([
            'trainer_id' => $data['trainer_id'],
            'session_id' => $data['session_id'],
            'total_hours' => $hours,
            'hourly_rate' => $rate,
            'amount' => $amount,
            'status' => $trainerPayment->status ?? PaymentStatus::Pending,
        ])->save();

        return $trainerPayment->refresh();
    }

    public function updateTrainerPaymentStatus(TrainerPayment $trainerPayment, PaymentStatus $status, User $actor): TrainerPayment
    {
        $trainerPayment->update([
            'status' => $status,
            'approved_by' => in_array($status, [PaymentStatus::Approved, PaymentStatus::Paid], true)
                ? $actor->id
                : $trainerPayment->approved_by,
            'paid_at' => $status === PaymentStatus::Paid ? now() : null,
        ]);

        return $trainerPayment->refresh();
    }

    private function invoiceNumber(): string
    {
        do {
            $number = 'INV-'.now()->format('Y').'-'.strtoupper(Str::random(6));
        } while (Invoice::where('invoice_no', $number)->exists());

        return $number;
    }
}
