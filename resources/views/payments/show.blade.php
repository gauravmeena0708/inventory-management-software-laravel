@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Payment Milestone: {{ $payment->schedule_key ?? '#' . $payment->id }}</h1>
            <p class="text-sm text-slate-500 mt-1">Milestone payment details and transaction settlement audit.</p>
        </div>
        <a href="{{ route('payments.index') }}" class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 shadow-xs transition-colors">
            &larr; Back to Payments
        </a>
    </div>

    @php
        $pStatus = $payment->status instanceof \App\Enums\PaymentStatus ? $payment->status : \App\Enums\PaymentStatus::tryFrom($payment->status);
        $isPending = $pStatus ? $pStatus->isPending() : ($payment->status === 'pending');
        $isCompleted = $pStatus ? $pStatus->isCompleted() : ($payment->status === 'completed');
        $isOverdue = $isPending && $payment->due_date && $payment->due_date < now();
    @endphp

    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-5">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Associated Agreement</span>
                <h2 class="text-lg font-bold text-slate-900 mt-0.5">
                    @if($payment->agreement)
                        <a href="{{ route('agreements.show', $payment->agreement) }}" class="hover:text-indigo-600">
                            {{ $payment->agreement->name }}
                        </a>
                    @else
                        <span class="text-slate-400">Unlinked</span>
                    @endif
                </h2>
            </div>
            <div>
                @if ($isCompleted)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Completed
                    </span>
                @elseif ($isOverdue)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                        Overdue
                    </span>
                @elseif ($isPending)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                        Pending
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                        Cancelled
                    </span>
                @endif
            </div>
        </div>

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                <dt class="text-slate-500 font-medium">Payment Amount</dt>
                <dd class="text-slate-900 font-extrabold text-lg mt-0.5">{{ $payment->currency ?? 'INR' }} {{ number_format($payment->amount, 2) }}</dd>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                <dt class="text-slate-500 font-medium">Due Date</dt>
                <dd class="font-bold text-lg mt-0.5 {{ $isOverdue ? 'text-rose-600' : 'text-slate-900' }}">{{ $payment->due_date?->format('d M Y') ?? '-' }}</dd>
            </div>
            <div class="py-2.5 border-b border-slate-100 flex justify-between">
                <dt class="text-slate-500 font-medium">Invoice Number</dt>
                <dd class="text-slate-900 font-mono font-semibold">{{ $payment->invoice_number ?? 'Not Invoiced' }}</dd>
            </div>
            <div class="py-2.5 border-b border-slate-100 flex justify-between">
                <dt class="text-slate-500 font-medium">Paid Date</dt>
                <dd class="text-slate-900 font-semibold">{{ $payment->paid_date?->format('d M Y') ?? '-' }}</dd>
            </div>
            <div class="py-2.5 border-b border-slate-100 flex justify-between">
                <dt class="text-slate-500 font-medium">Completed By</dt>
                <dd class="text-slate-900 font-semibold">{{ $payment->completedBy?->name ?? '-' }}</dd>
            </div>
            <div class="py-2.5 border-b border-slate-100 flex justify-between">
                <dt class="text-slate-500 font-medium">Schedule Key</dt>
                <dd class="text-slate-900 font-mono font-semibold">{{ $payment->schedule_key ?? '-' }}</dd>
            </div>
        </dl>

        @if ($payment->remarks)
            <div class="pt-3 border-t border-slate-100">
                <span class="text-xs font-semibold text-slate-500">Remarks:</span>
                <p class="text-xs text-slate-700 mt-1 whitespace-pre-line bg-slate-50 p-3 rounded-xl">{{ $payment->remarks }}</p>
            </div>
        @endif
    </div>
</div>
@endsection