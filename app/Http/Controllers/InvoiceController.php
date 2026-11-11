<?php

namespace App\Http\Controllers;

use App\Enums\BillTo;
use App\Enums\InvoiceStatus;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\CourseSession;
use App\Models\Invoice;
use App\Models\Trainee;
use App\Models\TrainingProvider;
use App\Services\FinanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(private readonly FinanceService $finance) {}

    public function index(Request $request): View
    {
        $term = trim((string) $request->input('q'));

        $invoices = Invoice::query()
            ->with(['session.course', 'trainee', 'provider'])
            ->withSum('payments', 'amount')
            ->when($term !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('invoice_no', 'like', '%'.$term.'%')
                ->orWhereHas('trainee', fn ($trainee) => $trainee->where('full_name', 'like', '%'.$term.'%'))
                ->orWhereHas('provider', fn ($provider) => $provider->where('name', 'like', '%'.$term.'%'))))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('bill_to'), fn ($q) => $q->where('bill_to', $request->input('bill_to')))
            ->when($request->filled('session_id'), fn ($q) => $q->where('session_id', $request->input('session_id')))
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('invoices.index', [
            'invoices' => $invoices,
            'statuses' => InvoiceStatus::options(),
            'billTo' => BillTo::options(),
            'sessions' => $this->sessionOptions(),
        ]);
    }

    public function create(): View
    {
        return view('invoices.create', $this->formData());
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $invoice = $this->finance->saveInvoice(new Invoice, $request->validated());

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'تم إنشاء الفاتورة: '.$invoice->invoice_no);
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['session.course', 'trainee', 'provider', 'payments.receivedBy']);

        return view('invoices.show', [
            'invoice' => $invoice,
            'paid' => (float) $invoice->payments->sum('amount'),
        ]);
    }

    public function edit(Invoice $invoice): View
    {
        return view('invoices.edit', $this->formData() + ['invoice' => $invoice]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->finance->saveInvoice($invoice, $request->validated());
        $this->finance->refreshInvoiceStatus($invoice);

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'تم تحديث الفاتورة.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        if ($invoice->payments()->exists()) {
            return back()->with('error', 'لا يمكن حذف فاتورة لها دفعات مسجّلة.');
        }

        $invoice->delete();

        return redirect()
            ->route('invoices.index')
            ->with('success', 'تم حذف الفاتورة.');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'sessions' => $this->sessionOptions(),
            'trainees' => Trainee::query()->orderBy('full_name')->pluck('full_name', 'id')->all(),
            'providers' => TrainingProvider::query()->orderBy('name')->pluck('name', 'id')->all(),
            'billTo' => BillTo::options(),
            'statuses' => InvoiceStatus::options(),
        ];
    }

    /** @return array<int, string> */
    private function sessionOptions(): array
    {
        return CourseSession::query()
            ->with('course')
            ->orderByDesc('start_date')
            ->get()
            ->mapWithKeys(fn (CourseSession $session) => [
                $session->id => $session->session_code.' — '.($session->course?->title ?? 'دورة محذوفة'),
            ])
            ->all();
    }
}
