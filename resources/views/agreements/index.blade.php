@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Vendor Agreements & AMC</h1>
            <p class="text-sm text-slate-500 mt-1">Manage annual maintenance contracts, software licensing, and automated milestone billing.</p>
        </div>
        <div class="flex items-center space-x-3">
            @if (auth()->user()?->canExportData())
            <a 
                href="{{ route('agreements.export', request()->query()) }}" 
                class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 shadow-xs transition-colors"
            >
                <svg class="w-4 h-4 mr-1.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export Excel
            </a>
            @endif
            @if (auth()->user()?->canManageAgreements())
            <a 
                href="{{ route('agreements.create') }}" 
                class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-colors"
            >
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Agreement
            </a>
            @endif
        </div>
    </div>

    <!-- Filter Tabs (Chips) -->
    <div class="flex items-center space-x-2 overflow-x-auto pb-1 scrollbar-thin">
        @php
            $currentFilter = request('filter');
        @endphp
        <a 
            href="{{ route('agreements.index', array_merge(request()->except(['page', 'filter']))) }}"
            class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all whitespace-nowrap {{ !$currentFilter ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80' }}"
        >
            All Contracts
        </a>
        <a 
            href="{{ route('agreements.index', array_merge(request()->except(['page', 'filter']), ['filter' => 'due'])) }}"
            class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all whitespace-nowrap {{ $currentFilter === 'due' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80' }}"
        >
            Expiring Soon (&le; 180 Days)
        </a>
        <a 
            href="{{ route('agreements.index', array_merge(request()->except(['page', 'filter']), ['filter' => 'expired'])) }}"
            class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all whitespace-nowrap {{ $currentFilter === 'expired' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80' }}"
        >
            Expired
        </a>
    </div>

    <!-- Search Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
        <form method="GET" action="{{ route('agreements.index') }}" class="flex flex-col sm:flex-row items-center justify-between gap-3">
            @if(request('filter'))
                <input type="hidden" name="filter" value="{{ request('filter') }}">
            @endif
            <div class="relative w-full sm:max-w-md">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Search agreements by contract title, agency, or type..." 
                    class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>
            <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-xl bg-slate-900 text-white hover:bg-slate-800 transition-colors">
                Search
            </button>
        </form>
    </div>

    <!-- Contracts Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50/75">
                    <tr>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Agreement / Vendor</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Type</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Annual Cost</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Contract Expiry</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Paid Till</th>
                        <th scope="col" class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($agreements as $agreement)
                        @php
                            $isExpired = $agreement->expiry && $agreement->expiry < now();
                            $isExpiringSoon = $agreement->expiry && !$isExpired && $agreement->expiry <= now()->addDays(180);
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-xs shrink-0 border border-amber-100">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <a href="{{ route('agreements.show', $agreement) }}" class="text-sm font-semibold text-slate-900 hover:text-indigo-600 transition-colors">
                                            {{ $agreement->name }}
                                        </a>
                                        <div class="text-xs text-slate-400 font-medium">{{ $agreement->agency }}</div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-700">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 text-slate-700">
                                    {{ $agreement->type }}
                                </span>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-xs font-bold text-slate-900">
                                {{ $agreement->currency ?? 'INR' }} {{ number_format($agreement->annual_cost ?? 0, 2) }}
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-xs font-medium">
                                <div class="flex items-center space-x-2">
                                    <span class="{{ $isExpired ? 'text-rose-600 font-bold' : ($isExpiringSoon ? 'text-amber-600 font-semibold' : 'text-slate-700') }}">
                                        {{ $agreement->expiry?->format('d M Y') ?? 'N/A' }}
                                    </span>
                                    @if ($isExpired)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700">Expired</span>
                                    @elseif ($isExpiringSoon)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700">Due Soon</span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600">
                                {{ $agreement->paid_till?->format('d M Y') ?? 'No Payment Yet' }}
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-medium space-x-2">
                                <a href="{{ route('agreements.show', $agreement) }}" class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors">
                                    Details
                                </a>
                                @if (auth()->user()?->canManageAgreements())
                                <a href="{{ route('agreements.edit', $agreement) }}" class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition-colors">
                                    Edit
                                </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400 text-xs">
                                No vendor agreements or maintenance contracts found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($agreements->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
            {{ $agreements->links() }}
        </div>
        @endif
    </div>
</div>
@endsection