<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\Invoice;
use App\Services\FinanceService;
use Illuminate\Http\RedirectResponse;

class PaymentController extends Controller
{
    public function __construct(private readonly FinanceService $finance) {}

    public function store(StorePaymentRequest $request, Invoice $invoice): RedirectResponse
    {
        $payment = $this->finance->addPayment($invoice, $request->validated(), $request->user());

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'تم تسجيل الدفعة بمبلغ '.$payment->amount.' ج.م.');
    }
}
