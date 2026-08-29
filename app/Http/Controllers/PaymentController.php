<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Http\Requests\CompletePaymentRequest;
use App\Models\Payment;
use App\Services\Agreements\CompletePaymentAction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    /**
     * Display a listing of payments.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        $query = Payment::query()
            ->visibleTo($request->user())
            ->with(['agreement', 'completedBy']);

        $status = $request->input('status');
        if ($status === 'overdue') {
            $query->overdue();
        } elseif ($status === 'pending') {
            $query->pending();
        } elseif ($status === 'completed') {
            $query->completed();
        } elseif ($request->filled('status')) {
            $query->where('status', $status);
        }

        if ($request->filled('agreement_id')) {
            $query->where('agreement_id', $request->input('agreement_id'));
        }

        $payments = $query->orderBy('due_date', 'asc')->paginate(25)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($payments);
        }

        return view('payments.index', [
            'payments' => $payments,
            'filters' => $request->only(['status', 'agreement_id']),
        ]);
    }

    /**
     * Display the specified payment.
     */
    public function show(Request $request, Payment $payment): View|JsonResponse
    {
        $this->authorize('view', $payment);

        $payment->load(['agreement', 'completedBy']);

        if ($request->wantsJson()) {
            return response()->json($payment);
        }

        return view('payments.show', ['payment' => $payment]);
    }

    /**
     * Mark a scheduled payment as completed.
     */
    public function complete(
        CompletePaymentRequest $request,
        Payment $payment,
        CompletePaymentAction $action
    ): RedirectResponse|JsonResponse {
        $this->authorize('complete', $payment);

        $paidDate = $request->filled('paid_date')
            ? Carbon::parse($request->input('paid_date'))
            : now();

        $completedPayment = $action->execute(
            payment: $payment,
            user: $request->user(),
            invoiceNumber: $request->validated('invoice_number'),
            paidDate: $paidDate
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Payment marked as completed successfully.',
                'payment' => $completedPayment->fresh(['agreement', 'completedBy']),
            ]);
        }

        return redirect()->route('payments.show', $payment)
            ->with('success', 'Payment marked as completed successfully.');
    }

    /**
     * Cancel a pending scheduled payment.
     */
    public function cancel(Request $request, Payment $payment): RedirectResponse|JsonResponse
    {
        $this->authorize('cancel', $payment);

        if ($payment->status === PaymentStatus::COMPLETED) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Completed payments cannot be cancelled.'], 422);
            }

            return back()->withErrors(['status' => 'Completed payments cannot be cancelled.']);
        }

        $payment->update([
            'status' => PaymentStatus::CANCELLED,
            'remarks' => trim(($payment->remarks ? $payment->remarks."\n" : '').'[Cancelled by user: '.($request->user()?->name ?? 'System').']'),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Payment cancelled successfully.',
                'payment' => $payment->fresh(),
            ]);
        }

        return redirect()->route('payments.show', $payment)
            ->with('success', 'Payment cancelled successfully.');
    }

    /**
     * Alias for due payments.
     */
    public function due(Request $request): View|JsonResponse
    {
        $request->merge(['status' => 'overdue']);

        return $this->index($request);
    }

    /**
     * Alias for completed payments.
     */
    public function completed(Request $request): View|JsonResponse
    {
        $request->merge(['status' => 'completed']);

        return $this->index($request);
    }
}
