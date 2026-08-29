@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">IT Hardware Fleet</h1>
            <p class="text-sm text-slate-500 mt-1">{{ config('inventory.poc_ui_mode') ? 'Add, find, assign, return, and export inventory assets.' : 'Manage, assign, and track physical compute, networking, and storage devices.' }}</p>
        </div>
        <div class="flex items-center space-x-3">
            @if (auth()->user()?->canExportData())
            <a 
                href="{{ route('assets.export', request()->query()) }}" 
                class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 shadow-xs transition-colors"
            >
                <svg class="w-4 h-4 mr-1.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export Excel
            </a>
            @endif
            @if (auth()->user()?->canManageInventory())
            <a 
                href="{{ route('assets.create') }}" 
                class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-colors"
            >
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Asset
            </a>
            @endif
        </div>
    </div>

    <!-- Filter Tabs (Chips) -->
    <div class="flex items-center space-x-2 overflow-x-auto pb-1 scrollbar-thin">
        @php
            $currentType = request('type') ?? request('asset_type');
            $typeTabs = [
                '' => 'All Assets',
                'desktop' => 'Desktops',
                'laptop' => 'Laptops',
                'server' => 'Servers',
                'switch' => 'Switches',
                'storage' => 'Storage Units',
                'other' => 'Other Devices',
            ];
        @endphp

        @foreach($typeTabs as $tabKey => $tabLabel)
            <a 
                href="{{ route('assets.index', array_merge(request()->except(['page', 'type', 'asset_type']), $tabKey ? ['type' => $tabKey] : [])) }}"
                class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all whitespace-nowrap {{ ($currentType === $tabKey || (!$currentType && $tabKey === '')) ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80' }}"
            >
                {{ $tabLabel }}
            </a>
        @endforeach
    </div>

    <!-- Search & Filter Controls -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
        <form method="GET" action="{{ route('assets.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
            @if(request('type'))
                <input type="hidden" name="type" value="{{ request('type') }}">
            @endif

            <!-- Search Input -->
            <div class="relative sm:col-span-2">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Search by tag, name, serial, or model..." 
                    class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>

            <!-- Status Filter -->
            <div>
                <select 
                    name="status" 
                    class="w-full py-2 px-3 text-xs bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    onchange="this.form.submit()"
                >
                    <option value="">All Statuses</option>
                    @foreach(\App\Enums\AssetStatus::cases() as $statusCase)
                        <option value="{{ $statusCase->value }}" {{ request('status') === $statusCase->value ? 'selected' : '' }}>
                            {{ $statusCase->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Buttons -->
            <div class="flex items-center space-x-2">
                <button 
                    type="submit" 
                    class="w-full py-2 px-4 text-xs font-semibold rounded-xl bg-slate-900 hover:bg-slate-800 text-white transition-colors"
                >
                    Filter
                </button>
                @if(request()->hasAny(['search', 'status', 'type', 'asset_type']))
                    <a 
                        href="{{ route('assets.index') }}" 
                        class="p-2 rounded-xl text-slate-400 hover:text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors"
                        title="Reset Filters"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50/75">
                    <tr>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Asset Tag & Name</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Type</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Serial / Model</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Assignee</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Location</th>
                        <th scope="col" class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($assets as $asset)
                        @php
                            $status = $asset->status instanceof \App\Enums\AssetStatus ? $asset->status : \App\Enums\AssetStatus::tryFrom($asset->status);
                            $type = $asset->asset_type instanceof \App\Enums\AssetType ? $asset->asset_type : \App\Enums\AssetType::tryFrom($asset->asset_type);
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <!-- Asset Tag & Name -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-mono text-xs font-bold shrink-0">
                                        {{ substr($asset->asset_tag ?? $asset->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <a href="{{ route('assets.show', $asset) }}" class="text-sm font-semibold text-slate-900 hover:text-indigo-600 transition-colors">
                                            {{ $asset->name }}
                                        </a>
                                        <div class="text-xs font-mono text-slate-400">{{ $asset->asset_tag ?? 'NO-TAG' }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Type -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 text-slate-700">
                                    {{ $type ? $type->label() : ($asset->asset_type ?? 'Other') }}
                                </span>
                            </td>

                            <!-- Status Badge -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusValue = $status ? $status->value : (string) $asset->status;
                                    $statusLabel = $status ? $status->label() : (string) $asset->status;
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold 
                                    {{ $statusValue === 'in_use' ? 'bg-blue-50 text-blue-700 border border-blue-200' : '' }}
                                    {{ $statusValue === 'in_stock' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : '' }}
                                    {{ $statusValue === 'under_maintenance' ? 'bg-amber-50 text-amber-700 border border-amber-200' : '' }}
                                    {{ $statusValue === 'decommissioned' ? 'bg-rose-50 text-rose-700 border border-rose-200' : '' }}
                                ">
                                    <span class="w-1.5 h-1.5 rounded-full mr-1.5
                                        {{ $statusValue === 'in_use' ? 'bg-blue-500' : '' }}
                                        {{ $statusValue === 'in_stock' ? 'bg-emerald-500' : '' }}
                                        {{ $statusValue === 'under_maintenance' ? 'bg-amber-500' : '' }}
                                        {{ $statusValue === 'decommissioned' ? 'bg-rose-500' : '' }}
                                    "></span>
                                    {{ $statusLabel }}
                                </span>
                            </td>

                            <!-- Serial Number & Model -->
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600">
                                <div class="font-mono">{{ $asset->serial_number ?? '-' }}</div>
                                <div class="text-slate-400 text-[11px]">{{ $asset->model_number ?? ($asset->manufacturer?->name ?? '') }}</div>
                            </td>

                            <!-- Assignee -->
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-700">
                                @if ($asset->assignedOfficial)
                                    <div class="flex items-center space-x-2">
                                        <div class="w-5 h-5 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center text-[10px] font-bold">
                                            {{ substr($asset->assignedOfficial->name, 0, 1) }}
                                        </div>
                                        <span class="font-medium text-slate-900">{{ $asset->assignedOfficial->name }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-400 italic">Unassigned (In Stock)</span>
                                @endif
                            </td>

                            <!-- Location -->
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">
                                {{ $asset->location?->name ?? '-' }}
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-medium space-x-2">
                                <a href="{{ route('assets.show', $asset) }}" class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors">
                                    View
                                </a>
                                @if (auth()->user()?->canManageInventory())
                                <a href="{{ route('assets.edit', $asset) }}" class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition-colors">
                                    Edit
                                </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                </div>
                                <h3 class="text-sm font-semibold text-slate-900">No assets matching criteria</h3>
                                <p class="text-xs text-slate-500 mt-1">Try adjusting your search filters or add a new asset.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($assets->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
            {{ $assets->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
