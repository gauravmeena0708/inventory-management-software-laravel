@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ config('inventory.poc_ui_mode') ? 'Consumables & Stock' : 'Consumables & Supplies' }}</h1>
            <p class="text-sm text-slate-500 mt-1">{{ config('inventory.poc_ui_mode') ? 'View current quantities, receive stock, and issue items to people.' : 'Track physical peripherals, cables, stationery, and stock reorder thresholds.' }}</p>
        </div>
        <div class="flex items-center space-x-3">
            <a 
                href="{{ route('stock.index') }}" 
                class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 shadow-xs transition-colors"
            >
                <svg class="w-4 h-4 mr-1.5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                View Transaction History
            </a>
            @if (auth()->user()?->canManageInventory())
            <a 
                href="{{ route('consumables.create') }}" 
                class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-colors"
            >
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Consumable
            </a>
            @endif
        </div>
    </div>

    <!-- Search & Filter Controls -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
        <form method="GET" action="{{ route('consumables.index') }}" class="flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="relative w-full sm:max-w-md">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Search consumables by name or SKU..." 
                    class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>

            <div class="flex items-center space-x-3 w-full sm:w-auto justify-end">
                <label class="inline-flex items-center cursor-pointer text-xs font-semibold text-slate-700 select-none">
                    <input 
                        type="checkbox" 
                        name="low_stock" 
                        value="1" 
                        {{ request('low_stock') ? 'checked' : '' }} 
                        onchange="this.form.submit()" 
                        class="rounded text-indigo-600 focus:ring-indigo-500 mr-2"
                    >
                    <span>Low Stock Only</span>
                </label>
                <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-xl bg-slate-900 text-white hover:bg-slate-800 transition-colors">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Consumables Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50/75">
                    <tr>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Item Name & SKU</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Stock Level</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Min / Max Threshold</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Unit</th>
                        <th scope="col" class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($consumables as $item)
                        @php
                            $isLow = $item->isLowStock();
                            $max = max($item->max_quantity ?? 100, 1);
                            $percentage = min(100, round(($item->in_stock / $max) * 100));
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-xl {{ $isLow ? 'bg-rose-50 text-rose-600 border border-rose-100' : 'bg-emerald-50 text-emerald-600 border border-emerald-100' }} flex items-center justify-center font-bold text-xs shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <a href="{{ route('consumables.show', $item) }}" class="text-sm font-semibold text-slate-900 hover:text-indigo-600 transition-colors">
                                            {{ $item->name }}
                                        </a>
                                        <div class="text-xs font-mono text-slate-400">{{ $item->sku ?? 'NO-SKU' }}</div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="w-48">
                                    <div class="flex items-center justify-between text-xs mb-1">
                                        <span class="font-bold {{ $isLow ? 'text-rose-600' : 'text-slate-900' }}">
                                            {{ $item->in_stock }} in stock
                                        </span>
                                        @if($isLow)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700">
                                                Low Stock
                                            </span>
                                        @endif
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                        <div 
                                            class="h-2 rounded-full {{ $isLow ? 'bg-rose-500' : 'bg-emerald-500' }}" 
                                            style="width: {{ $percentage }}%"
                                        ></div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600">
                                <div>Min: <span class="font-semibold text-slate-900">{{ $item->min_quantity ?? '-' }}</span></div>
                                <div class="text-slate-400">Max: {{ $item->max_quantity ?? '-' }}</div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500 uppercase font-medium">
                                {{ $item->unit ?? 'Unit' }}
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-medium space-x-2">
                                <a href="{{ route('consumables.show', $item) }}" class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors">
                                    View / Update Stock
                                </a>
                                @if (auth()->user()?->canManageInventory())
                                <a href="{{ route('consumables.edit', $item) }}" class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition-colors">
                                    Edit
                                </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400 text-xs">
                                No consumable supplies found matching the criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($consumables->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
            {{ $consumables->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
