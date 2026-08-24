@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Immutable Stock Ledger</h1>
            <p class="text-sm text-slate-500 mt-1">Audit log of all inbound purchase orders, outbound issues, and physical stock count adjustments.</p>
        </div>
        <div class="flex items-center space-x-3">
            <a 
                href="{{ route('consumables.index') }}" 
                class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 shadow-xs transition-colors"
            >
                <svg class="w-4 h-4 mr-1.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                Consumables Catalog
            </a>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
        <form method="GET" action="{{ route('stock.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <!-- Consumable Filter -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Filter by Item</label>
                <select 
                    name="consumable_id" 
                    class="w-full py-2 px-3 text-xs bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    onchange="this.form.submit()"
                >
                    <option value="">All Consumables</option>
                    @foreach($consumables as $c)
                        <option value="{{ $c->id }}" {{ request('consumable_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->sku ?? 'No SKU' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Type Filter -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Transaction Type</label>
                <select 
                    name="type" 
                    class="w-full py-2 px-3 text-xs bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    onchange="this.form.submit()"
                >
                    <option value="">All Entry Types</option>
                    @foreach(\App\Enums\StockEntryType::cases() as $entryType)
                        <option value="{{ $entryType->value }}" {{ request('type') === $entryType->value ? 'selected' : '' }}>
                            {{ $entryType->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Actions -->
            <div class="flex items-end space-x-2">
                <button type="submit" class="w-full py-2 px-4 text-xs font-semibold rounded-xl bg-slate-900 text-white hover:bg-slate-800 transition-colors">
                    Apply Filter
                </button>
                @if(request()->hasAny(['consumable_id', 'type']))
                    <a href="{{ route('stock.index') }}" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors" title="Clear Filters">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Ledger Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50/75">
                    <tr>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Tx ID</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Date & Time</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Item / SKU</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Type</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Quantity</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Stock Balance</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Recipient</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Recorded By</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-xs">
                    @forelse ($entries as $entry)
                        @php
                            $entryType = $entry->type instanceof \App\Enums\StockEntryType ? $entry->type : \App\Enums\StockEntryType::tryFrom($entry->type);
                            $isInbound = $entryType ? $entryType->isInbound() : false;
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap font-mono text-slate-400">#{{ $entry->id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-slate-600">
                                {{ $entry->created_at?->format('d M Y, H:i') ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($entry->consumable)
                                    <a href="{{ route('consumables.show', $entry->consumable) }}" class="font-semibold text-slate-900 hover:text-indigo-600 transition-colors">
                                        {{ $entry->consumable->name }}
                                    </a>
                                    <div class="text-[11px] font-mono text-slate-400">{{ $entry->consumable->sku ?? '' }}</div>
                                @else
                                    <span class="text-slate-400">Deleted Consumable</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold 
                                    {{ $isInbound ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}
                                ">
                                    {{ $entryType ? $entryType->label() : (string)$entry->type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-bold text-sm {{ $isInbound ? 'text-emerald-600' : 'text-blue-600' }}">
                                {{ $isInbound ? '+' : '-' }}{{ $entry->quantity }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-mono font-bold text-slate-900 text-sm">
                                {{ $entry->stock_after }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-slate-700 font-medium">
                                {{ $entry->recipient?->name ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-slate-500">
                                {{ $entry->recorder?->name ?? 'System' }}
                            </td>
                            <td class="px-6 py-4 text-slate-500 max-w-xs truncate">
                                {{ $entry->remarks ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-slate-400 text-xs">
                                No stock ledger transactions recorded.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($entries->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
            {{ $entries->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
