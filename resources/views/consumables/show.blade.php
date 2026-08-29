@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="{ entryModal: false, entryType: 'purchase' }">
    @php
        $isLow = $consumable->isLowStock();
        $max = max($consumable->max_quantity ?? 100, 1);
        $percentage = min(100, round(($consumable->in_stock / $max) * 100));
    @endphp

    <!-- Header Banner -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-start space-x-4">
            <div class="w-14 h-14 rounded-2xl {{ $isLow ? 'bg-rose-50 text-rose-600 border border-rose-100' : 'bg-emerald-50 text-emerald-600 border border-emerald-100' }} flex items-center justify-center font-bold text-xl shrink-0">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <div>
                <div class="flex items-center space-x-3">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $consumable->name }}</h1>
                    @if($isLow)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                            Low Stock Alert
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Healthy Stock
                        </span>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 mt-1 font-medium">
                    <span class="font-mono text-slate-700 font-semibold">SKU: {{ $consumable->sku ?? 'N/A' }}</span>
                    <span>&bull;</span>
                    <span>Unit: {{ $consumable->unit ?? 'Unit' }}</span>
                    <span>&bull;</span>
                    <span>Min Alert: {{ $consumable->min_quantity ?? 'None' }}</span>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center space-x-2">
            @if (auth()->user()?->canPostStockEntries())
                <button 
                    type="button" 
                    @click="entryType = 'issue'; entryModal = true" 
                    class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-colors"
                >
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Issue Stock
                </button>
                <button 
                    type="button" 
                    @click="entryType = 'purchase'; entryModal = true" 
                    class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-xl text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition-colors"
                >
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ config('inventory.poc_ui_mode') ? 'Receive Stock' : 'Restock (PO)' }}
                </button>
            @endif
            @if (auth()->user()?->canManageInventory())
                <a 
                    href="{{ route('consumables.edit', $consumable) }}" 
                    class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 shadow-xs transition-colors"
                >
                    Edit Item
                </a>
            @endif
        </div>
    </div>

    <!-- Stock Metrics Card -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Current Inventory Balance</span>
            <div class="text-3xl font-extrabold {{ $isLow ? 'text-rose-600' : 'text-slate-900' }} mt-2">
                {{ $consumable->in_stock }} <span class="text-sm font-medium text-slate-500">{{ $consumable->unit ?? 'units' }}</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-2 mt-3 overflow-hidden">
                <div class="h-2 rounded-full {{ $isLow ? 'bg-rose-500' : 'bg-emerald-500' }}" style="width: {{ $percentage }}%"></div>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Minimum Safe Reserve</span>
            <div class="text-3xl font-extrabold text-slate-900 mt-2">
                {{ $consumable->min_quantity ?? '-' }} <span class="text-sm font-medium text-slate-500">{{ $consumable->unit ?? 'units' }}</span>
            </div>
            <p class="text-xs text-slate-500 mt-2">Trigger for automated procurement alerts.</p>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Storage Capacity Limit</span>
            <div class="text-3xl font-extrabold text-slate-900 mt-2">
                {{ $consumable->max_quantity ?? '-' }} <span class="text-sm font-medium text-slate-500">{{ $consumable->unit ?? 'units' }}</span>
            </div>
            <p class="text-xs text-slate-500 mt-2">Recommended maximum batch stock limit.</p>
        </div>
    </div>

    <!-- Stock Ledger Transaction History -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">{{ config('inventory.poc_ui_mode') ? 'Stock Transaction History' : 'Immutable Stock Transaction Ledger' }}</h2>
            <span class="text-xs text-slate-500">{{ $consumable->entries->count() }} recent transactions</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50/75">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Tx ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Date / Time</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Type</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Quantity</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Stock After</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Recipient</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Recorded By</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-xs">
                    @forelse ($consumable->entries as $entry)
                        @php
                            $entryType = $entry->type instanceof \App\Enums\StockEntryType ? $entry->type : \App\Enums\StockEntryType::tryFrom($entry->type);
                            $isInbound = $entryType ? $entryType->isInbound() : false;
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-3.5 whitespace-nowrap font-mono text-slate-400">#{{ $entry->id }}</td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-slate-600">
                                {{ $entry->created_at?->format('d M Y H:i') ?? '-' }}
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold {{ $isInbound ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">
                                    {{ $entryType ? $entryType->label() : (string)$entry->type }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap font-bold {{ $isInbound ? 'text-emerald-600' : 'text-blue-600' }}">
                                {{ $isInbound ? '+' : '-' }}{{ $entry->quantity }}
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap font-mono font-bold text-slate-900">
                                {{ $entry->stock_after }}
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-slate-700 font-medium">
                                {{ $entry->recipient?->name ?? '-' }}
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-slate-500">
                                {{ $entry->recorder?->name ?? 'System' }}
                            </td>
                            <td class="px-6 py-3.5 text-slate-500 max-w-xs truncate">
                                {{ $entry->remarks ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-slate-400 text-xs">
                                No stock ledger transactions recorded for this item.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Stock Ledger Entry Modal -->
    <div x-show="entryModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs" @click="entryModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200">
                <form action="{{ route('consumables.entries.store', $consumable) }}" method="POST">
                    @csrf
                    <input type="hidden" name="consumable_id" value="{{ $consumable->id }}">

                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h3 class="text-base font-bold text-slate-900" x-text="entryType === 'issue' ? 'Issue Stock' : 'Receive Stock'"></h3>
                            <button type="button" @click="entryModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Transaction Type <span class="text-rose-500">*</span></label>
                            <select name="type" x-model="entryType" required class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                @foreach(\App\Enums\StockEntryType::cases() as $entryTypeCase)
                                    @continue(config('inventory.poc_ui_mode') && ! in_array($entryTypeCase, [\App\Enums\StockEntryType::PURCHASE, \App\Enums\StockEntryType::ISSUE], true))
                                    <option value="{{ $entryTypeCase->value }}">{{ $entryTypeCase->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Quantity <span class="text-rose-500">*</span></label>
                            <input type="number" name="quantity" min="1" required placeholder="1" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <!-- Recipient (shown when type is issue) -->
                        <div x-show="entryType === 'issue'">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Recipient Official <span class="text-rose-500">*</span></label>
                            <select name="recipient_official_id" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Select Official</option>
                                @foreach($officials ?? \App\Models\Official::orderBy('name')->get() as $off)
                                    <option value="{{ $off->id }}">{{ $off->name }} ({{ $off->designation ?? 'Official' }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Remarks / Reason</label>
                            <textarea name="remarks" rows="2" placeholder="PO number, requisition reason, batch tag..." class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-slate-50 flex items-center justify-end space-x-3">
                        <button type="button" @click="entryModal = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 hover:bg-slate-200">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-xs font-bold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm" x-text="entryType === 'issue' ? 'Issue Stock' : 'Receive Stock'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
