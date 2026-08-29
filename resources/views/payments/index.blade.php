@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="{ payModal: false, selectedPaymentId: null, selectedAmount: '', selectedDueDate: '', agreementName: '' }">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Scheduled Vendor Payments</h1>
            <p class="text-sm text-slate-500 mt-1">Track milestone obligations, upcoming due dates, and record invoice voucher settlements.</p>
        </div>
        <div class="flex items-center space-x-3">
            <a 
                href="{{ route('agreements.index') }}" 
                class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 shadow-xs transition-colors"
            >
                <svg class="w-4 h-4 mr-1.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Agreements & AMC
            </a>
        </div>
    </div>

    <!-- Filter Tabs (Chips) -->
    <div class="flex items-center space-x-2 overflow-x-auto pb-1 scrollbar-thin">
        @php
            $currentStatus = request('status');
        @endphp
        <a 
            href="{{ route('payments.index', array_merge(request()->except(['page', 'status']))) }}"
            class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all whitespace-nowrap {{ !$currentStatus ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80' }}"
        >
            All Payments
        </a>
        <a 
            href="{{ route('payments.index', array_merge(request()->except(['page', 'status']), ['status' => 'pending'])) }}"
            class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all whitespace-nowrap {{ $currentStatus === 'pending' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80' }}"
        >
            Pending
        </a>
        <a 
            href="{{ route('payments.index', array_merge(request()->except(['page', 'status']), ['status' => 'overdue'])) }}"
            class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all whitespace-nowrap {{ $currentStatus === 'overdue' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80' }}"
        >
            Overdue
        </a>
        <a 
            href="{{ route('payments.index', array_merge(request()->except(['page', 'status']), ['status' => 'completed'])) }}"
            class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all whitespace-nowrap {{ $currentStatus === 'completed' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80' }}"
        >
            Completed
        </a>
    </div>

    <!-- Payments Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50/75">
                    <tr>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Milestone ID</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Agreement & Vendor</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Due Date</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Amount</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Invoice / Paid Date</th>
                        <th scope="col" class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($payments as $payment)
                        @php
                            $pStatus = $payment->status instanceof \App\Enums\PaymentStatus ? $payment->status : \App\Enums\PaymentStatus::tryFrom($payment->status);
                            $isPending = $pStatus ? $pStatus->isPending() : ($payment->status === 'pending');
                            $isCompleted = $pStatus ? $pStatus->isCompleted() : ($payment->status === 'completed');
                            $isOverdue = $isPending && $payment->due_date && $payment->due_date < now();
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap font-mono text-xs text-slate-700">
                                {{ $payment->schedule_key ?? '#' . $payment->id }}
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($payment->agreement)
                                    <a href="{{ route('agreements.show', $payment->agreement) }}" class="text-sm font-semibold text-slate-900 hover:text-indigo-600 transition-colors">
                                        {{ $payment->agreement->name }}
                                    </a>
                                    <div class="text-xs text-slate-400">{{ $payment->agreement->agency }}</div>
                                @else
                                    <span class="text-xs text-slate-400">Deleted Agreement</span>
                                @endif
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-xs">
                                <span class="{{ $isOverdue ? 'text-rose-600 font-bold' : 'text-slate-700 font-medium' }}">
                                    {{ $payment->due_date?->format('d M Y') ?? '-' }}
                                </span>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-xs font-bold text-slate-900">
                                {{ $payment->currency ?? 'INR' }} {{ number_format($payment->amount, 2) }}
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($isCompleted)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Completed
                                    </span>
                                @elseif ($isOverdue)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        Overdue
                                    </span>
                                @elseif ($isPending)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        Pending
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                        Cancelled
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600">
                                @if($payment->invoice_number)
                                    <div class="font-mono font-semibold text-slate-900">{{ $payment->invoice_number }}</div>
                                    <div class="text-[11px] text-slate-400">Paid: {{ $payment->paid_date?->format('d M Y') }}</div>
                                @else
                                    <span class="text-slate-400 italic">Uninvoiced</span>
                                @endif
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-medium space-x-2">
                                @if ($isPending && auth()->user()?->canManagePayments())
                                    <button 
                                        type="button" 
                                        @click="selectedPaymentId = {{ $payment->id }}; selectedAmount = '{{ number_format($payment->amount, 2) }}'; selectedDueDate = '{{ $payment->due_date?->format('d M Y') }}'; agreementName = '{{ addslashes($payment->agreement?->name ?? 'Contract') }}'; payModal = true"
                                        class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 font-semibold transition-colors"
                                    >
                                        Mark as Paid
                                    </button>
                                @endif
                                @if($payment->agreement)
                                    <a href="{{ route('agreements.show', $payment->agreement) }}" class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors">
                                        Agreement
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400 text-xs">
                                No scheduled payment records found matching criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($payments->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
            {{ $payments->links() }}
        </div>
        @endif
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
                            <div class="text-slate-700 font-semibold truncate" x-text="agreementName"></div>
                            <div class="flex justify-between pt-1 border-t border-slate-200/60">
                                <span class="text-slate-500">Amount Due:</span>
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
                            <textarea name="remarks" rows="2" placeholder="Bank transaction ID, Cheque reference, clearance note..." class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
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