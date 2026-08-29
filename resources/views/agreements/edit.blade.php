@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Edit Agreement: {{ $agreement->name }}</h1>
            <p class="text-sm text-slate-500 mt-1">Update vendor contract terms, billing intervals, and expiry dates.</p>
        </div>
        <a href="{{ route('agreements.show', $agreement) }}" class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 shadow-xs transition-colors">
            &larr; View Contract
        </a>
    </div>

    <form action="{{ route('agreements.update', $agreement) }}" method="POST" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <!-- Name -->
            <div class="sm:col-span-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Agreement / Contract Name <span class="text-rose-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $agreement->name) }}" required class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('name')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <!-- Agency -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Vendor / Contracting Agency <span class="text-rose-500">*</span></label>
                <input type="text" name="agency" value="{{ old('agency', $agreement->agency) }}" required class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('agency')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <!-- Contract Type -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Contract Type <span class="text-rose-500">*</span></label>
                <input type="text" name="type" value="{{ old('type', $agreement->type) }}" required class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('type')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <!-- Annual Cost -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Annual Total Cost</label>
                <div class="flex">
                    <span class="inline-flex items-center px-3 text-xs font-semibold text-slate-500 bg-slate-100 border border-r-0 border-slate-200 rounded-l-xl">{{ $agreement->currency ?? 'INR' }}</span>
                    <input type="number" step="0.01" name="annual_cost" value="{{ old('annual_cost', $agreement->annual_cost) }}" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-r-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                @error('annual_cost')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <!-- Billing Interval (Months) -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Billing Interval</label>
                <select name="billing_interval_months" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="1" {{ old('billing_interval_months', $agreement->billing_interval_months) == 1 ? 'selected' : '' }}>Monthly (1 month)</option>
                    <option value="3" {{ old('billing_interval_months', $agreement->billing_interval_months) == 3 ? 'selected' : '' }}>Quarterly (3 months)</option>
                    <option value="6" {{ old('billing_interval_months', $agreement->billing_interval_months) == 6 ? 'selected' : '' }}>Semi-Annually (6 months)</option>
                    <option value="12" {{ old('billing_interval_months', $agreement->billing_interval_months) == 12 ? 'selected' : '' }}>Annually (12 months)</option>
                </select>
            </div>

            <!-- Billing Anchor Date -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Contract Start / Anchor Date</label>
                <input type="date" name="billing_anchor_date" value="{{ old('billing_anchor_date', $agreement->billing_anchor_date?->format('Y-m-d')) }}" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- Expiry Date -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Contract Expiry Date</label>
                <input type="date" name="expiry" value="{{ old('expiry', $agreement->expiry?->format('Y-m-d')) }}" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- File Registry -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">File Record Reference</label>
                <select name="file_id" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">No Linked Registry File</option>
                    @foreach($files ?? \App\Models\FileRecord::orderBy('name')->get() as $file)
                        <option value="{{ $file->id }}" {{ old('file_id', $agreement->file_id) == $file->id ? 'selected' : '' }}>
                            {{ $file->name }} ({{ $file->file_number ?? 'File #' . $file->id }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Paid Till -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Paid Till Date</label>
                <input type="date" name="paid_till" value="{{ old('paid_till', $agreement->paid_till?->format('Y-m-d')) }}" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- Remarks -->
            <div class="sm:col-span-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Remarks / Scope of Work</label>
                <textarea name="remarks" rows="3" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('remarks', $agreement->remarks) }}</textarea>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
            <a href="{{ route('agreements.show', $agreement) }}" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 hover:bg-slate-200 transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-5 py-2.5 text-xs font-bold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-colors">
                Save Changes
            </button>
        </div>
    </form>
</div>
@endsection