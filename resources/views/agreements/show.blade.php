@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="{ payModal: false, selectedPaymentId: null, selectedAmount: '', selectedDueDate: '' }">
    @php
        $isExpired = $agreement->expiry && $agreement->expiry < now();
        $isExpiringSoon = $agreement->expiry && !$isExpired && $agreement->expiry <= now()->addDays(180);
    @endphp

    <!-- Header Banner -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-start space-x-4">
            <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-xl shrink-0 border border-amber-100">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <div class="flex items-center space-x-3">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $agreement->name }}</h1>
                    @if ($isExpired)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                            Expired
                        </span>
                    @elseif ($isExpiringSoon)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                            Due Soon (&le; 180d)
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Active Contract
                        </span>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 mt-1 font-medium">
                    <span class="text-slate-900 font-semibold">Vendor: {{ $agreement->agency }}</span>
                    <span>&bull;</span>
                    <span>Type: {{ $agreement->type }}</span>
                    <span>&bull;</span>
                    <span>Expiry: {{ $agreement->expiry?->format('d M Y') ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route('agreements.index') }}" class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 shadow-xs transition-colors">
                &larr; Agreements
            </a>
            @if (auth()->user()?->canManageAgreements())
                <a href="{{ route('agreements.edit', $agreement) }}" class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-xl text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition-colors">
                    Edit Contract
                </a>
            @endif
        </div>
    </div>

    <!-- Contract Details & Milestones -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Financial Terms Card -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-900 border-b border-slate-100 pb-3">Financial Terms</h2>
            <dl class="divide-y divide-slate-100 text-xs">
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-500 font-medium">Annual Value</dt>
                    <dd class="text-slate-900 font-bold">{{ $agreement->currency ?? 'INR' }} {{ number_format($agreement->annual_cost ?? 0, 2) }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-500 font-medium">Billing Interval</dt>
                    <dd class="text-slate-900 font-semibold">
                        @if($agreement->billing_interval_months == 1) Monthly
                        @elseif($agreement->billing_interval_months == 3) Quarterly
                        @elseif($agreement->billing_interval_months == 6) Semi-Annually
                        @elseif($agreement->billing_interval_months == 12) Annually
                        @else Every {{ $agreement->billing_interval_months }} months
                        @endif
                    </dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-500 font-medium">Anchor Start Date</dt>
                    <dd class="text-slate-900">{{ $agreement->billing_anchor_date?->format('d M Y') ?? '-' }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-500 font-medium">Paid Till Date</dt>
                    <dd class="text-slate-900 font-semibold">{{ $agreement->paid_till?->format('d M Y') ?? 'Not Paid' }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-500 font-medium">Registry File</dt>
                    <dd class="text-slate-900 font-semibold">
                        @if($agreement->file)
                            <a href="{{ route('files.show', $agreement->file) }}" class="text-indigo-600 hover:underline">
                                {{ $agreement->file->name }}
                            </a>
                        @else
                            <span class="text-slate-400">None</span>
                        @endif
                    </dd>
                </div>
            </dl>
            @if($agreement->remarks)
                <div class="pt-3 border-t border-slate-100">
                    <span class="text-xs font-semibold text-slate-500">Contract Scope & Remarks:</span>
                    <p class="text-xs text-slate-700 mt-1 whitespace-pre-line bg-slate-50 p-3 rounded-xl">{{ $agreement->remarks }}</p>
                </div>
            @endif
        </div>

        <!-- Payment Milestones Table (2 Cols) -->
        <div class="md:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Scheduled Payment Milestones</h2>
                <span class="text-xs font-medium text-slate-500">{{ $agreement->payments->count() }} Milestones</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50/75">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Milestone</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Due Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Amount</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Invoice / Paid</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white text-xs">
                        @forelse ($agreement->payments as $payment)
                            @php
                                $pStatus = $payment->status instanceof \App\Enums\PaymentStatus ? $payment->status : \App\Enums\PaymentStatus::tryFrom($payment->status);
                                $isPending = $pStatus ? $pStatus->isPending() : ($payment->status === 'pending');
                                $isCompleted = $pStatus ? $pStatus->isCompleted() : ($payment->status === 'completed');
                                $isOverdue = $isPending && $payment->due_date && $payment->due_date < now();
                            @endphp
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-3.5 whitespace-nowrap font-mono text-slate-700">
                                    {{ $payment->schedule_key ?? '#' . $payment->id }}
                                </td>
                                <td class="px-6 py-3.5 whitespace-nowrap">
                                    <span class="{{ $isOverdue ? 'text-rose-600 font-bold' : 'text-slate-700 font-medium' }}">
                                        {{ $payment->due_date?->format('d M Y') ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 whitespace-nowrap font-bold text-slate-900">
                                    {{ $payment->currency ?? 'INR' }} {{ number_format($payment->amount, 2) }}
                                </td>
                                <td class="px-6 py-3.5 whitespace-nowrap">
                                    @if ($isCompleted)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Completed
                                        </span>
                                    @elseif ($isOverdue)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            Overdue
                                        </span>
                                    @elseif ($isPending)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                            Pending
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700">
                                            Cancelled
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5 whitespace-nowrap text-slate-600">
                                    @if($payment->invoice_number)
                                        <div class="font-mono text-slate-900 font-semibold">{{ $payment->invoice_number }}</div>
                                        <div class="text-[11px] text-slate-400">Paid: {{ $payment->paid_date?->format('d M Y') }}</div>
                                    @else
                                        <span class="text-slate-400 italic">Uninvoiced</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5 whitespace-nowrap text-right space-x-2">
                                    @if ($isPending && auth()->user()?->canManagePayments())
                                        <button 
                                            type="button" 
                                            @click="selectedPaymentId = {{ $payment->id }}; selectedAmount = '{{ number_format($payment->amount, 2) }}'; selectedDueDate = '{{ $payment->due_date?->format('d M Y') }}'; payModal = true"
                                            class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 font-semibold transition-colors"
                                        >
                                            Mark as Paid
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-slate-400 text-xs">
                                    No payment schedule milestones generated yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Complete Payment Modal -->
    <div x-show="payModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs" @click="payModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200">
                <form :action="'/payments/' + selectedPaymentId + '/complete'" method="POST">
                    @csrf
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h3 class="text-base font-bold text-slate-900">Record Completed Payment</h3>
                            <button type="button" @click="payModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                        </div>

                        <div class="p-3 bg-slate-50 rounded-xl text-xs space-y-1">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Scheduled Amount:</span>
                                <span class="font-bold text-slate-900">INR <span x-text="selectedAmount"></span></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Due Date:</span>
                                <span class="text-slate-700" x-text="selectedDueDate"></span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Invoice / Voucher Reference <span class="text-rose-500">*</span></label>
                            <input type="text" name="invoice_number" required placeholder="e.g., INV-2026-9812" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Actual Payment Date <span class="text-rose-500">*</span></label>
                            <input type="date" name="paid_date" value="{{ date('Y-m-d') }}" required class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Payment Remarks</label>
                            <textarea name="remarks" rows="2" placeholder="Bank transaction ID, Cheque number, clearance note..." class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-slate-50 flex items-center justify-end space-x-3">
                        <button type="button" @click="payModal = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 hover:bg-slate-200">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-xs font-bold rounded-xl text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm">Confirm Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection