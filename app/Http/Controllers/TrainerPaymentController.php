<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Http\Requests\TrainerPaymentRequest;
use App\Http\Requests\UpdateTrainerPaymentStatusRequest;
use App\Models\CourseSession;
use App\Models\Trainer;
use App\Models\TrainerPayment;
use App\Services\FinanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainerPaymentController extends Controller
{
    public function __construct(private readonly FinanceService $finance) {}

    public function index(Request $request): View
    {
        $term = trim((string) $request->input('q'));

        $payments = TrainerPayment::query()
            ->with(['trainer', 'session.course', 'approvedBy'])
            ->when($term !== '', fn ($query) => $query->whereHas('trainer', fn ($trainer) => $trainer
                ->where('full_name', 'like', '%'.$term.'%')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('session_id'), fn ($q) => $q->where('session_id', $request->input('session_id')))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('trainer-payments.index', [
            'payments' => $payments,
            'statuses' => PaymentStatus::options(),
            'sessions' => CourseSession::query()->orderByDesc('start_date')->pluck('session_code', 'id')->all(),
        ]);
    }

    public function create(): View
    {
        return view('trainer-payments.create', [
            'trainers' => Trainer::query()->orderBy('full_name')->pluck('full_name', 'id')->all(),
            'sessions' => CourseSession::query()
                ->with('course')
                ->orderByDesc('start_date')
                ->get()
                ->mapWithKeys(fn (CourseSession $session) => [
                    $session->id => $session->session_code.' — '.($session->course?->title ?? 'دورة محذوفة'),
                ])
                ->all(),
        ]);
    }

    public function store(TrainerPaymentRequest $request): RedirectResponse
    {
        $payment = $this->finance->saveTrainerPayment(new TrainerPayment, $request->validated());

        return redirect()
            ->route('trainer-payments.index')
            ->with('success', 'تم تسجيل مستحق المدرب بمبلغ '.($payment->amount ?? 0).' ج.م.');
    }

    public function updateStatus(UpdateTrainerPaymentStatusRequest $request, TrainerPayment $trainerPayment): RedirectResponse
    {
        $this->finance->updateTrainerPaymentStatus(
            $trainerPayment,
            PaymentStatus::from($request->validated('status')),
            $request->user(),
        );

        return back()->with('success', 'تم تحديث حالة مستحق المدرب.');
    }
}
