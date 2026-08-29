@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Location Stock</h1>
            <p class="mt-1 text-sm text-slate-500">Authorized balances and immutable movements by store.</p>
        </div>
        <a href="{{ route('stock.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700">Legacy ledger</a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                <tr><th class="px-5 py-3">Store</th><th class="px-5 py-3">Consumable</th><th class="px-5 py-3">Quantity</th><th class="px-5 py-3">Threshold</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($balances as $balance)
                    <tr>
                        <td class="px-5 py-3"><span class="font-semibold">{{ $balance->location->name }}</span><div class="text-xs text-slate-500">{{ $balance->location->site?->name }}</div></td>
                        <td class="px-5 py-3">{{ $balance->consumable->name }}</td>
                        <td class="px-5 py-3 font-mono font-bold {{ $balance->isLowStock() ? 'text-rose-600' : 'text-slate-900' }}">{{ $balance->quantity }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $balance->min_quantity ?? '-' }} / {{ $balance->max_quantity ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">No authorized location balances.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-slate-100 px-5 py-3">{{ $balances->links() }}</div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 px-5 py-4 font-semibold text-slate-900">Location transaction ledger</div>
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                <tr><th class="px-5 py-3">Date</th><th class="px-5 py-3">Item</th><th class="px-5 py-3">Type</th><th class="px-5 py-3">Source</th><th class="px-5 py-3">Destination</th><th class="px-5 py-3">Quantity</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($transactions as $transaction)
                    <tr>
                        <td class="px-5 py-3 text-slate-500">{{ $transaction->created_at?->format('d M Y H:i') }}</td>
                        <td class="px-5 py-3">{{ $transaction->consumable->name }}</td>
                        <td class="px-5 py-3 font-semibold">{{ str($transaction->transaction_type->value)->replace('_', ' ')->title() }}</td>
                        <td class="px-5 py-3">{{ $transaction->sourceLocation?->name ?? '-' }}</td>
                        <td class="px-5 py-3">{{ $transaction->destinationLocation?->name ?? '-' }}</td>
                        <td class="px-5 py-3 font-mono font-bold">{{ $transaction->quantity }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">No authorized transactions.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-slate-100 px-5 py-3">{{ $transactions->links() }}</div>
    </div>
</div>
@endsection
