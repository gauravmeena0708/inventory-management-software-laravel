@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Add Consumable Supply</h1>
            <p class="text-sm text-slate-500 mt-1">Register a stock supply item with min/max replenishment limits.</p>
        </div>
        <a href="{{ route('consumables.index') }}" class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 shadow-xs transition-colors">
            &larr; Back to Consumables
        </a>
    </div>

    <form action="{{ route('consumables.store') }}" method="POST" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-5">
        @csrf

        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Consumable Name <span class="text-rose-500">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g., Cat6 Patch Cable (2m) or RJ45 Connectors" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @error('name')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">SKU / Item Code</label>
                <input type="text" name="sku" value="{{ old('sku') }}" placeholder="e.g., CAB-CAT6-02M" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                @error('sku')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Unit of Measure</label>
                <input type="text" name="unit" value="{{ old('unit', 'piece') }}" placeholder="e.g., piece, box, roll, pack" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('unit')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Initial In Stock</label>
                <input type="number" name="in_stock" value="{{ old('in_stock', 0) }}" min="0" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('in_stock')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Min Alert Quantity</label>
                <input type="number" name="min_quantity" value="{{ old('min_quantity', 5) }}" min="0" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('min_quantity')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Max Stock Limit</label>
                <input type="number" name="max_quantity" value="{{ old('max_quantity', 100) }}" min="0" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('max_quantity')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
            <a href="{{ route('consumables.index') }}" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 hover:bg-slate-200 transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-5 py-2.5 text-xs font-bold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-colors">
                Create Consumable
            </button>
        </div>
    </form>
</div>
@endsection