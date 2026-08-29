@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-indigo-600">Enterprise Reporting &amp; MIS</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">Inventory report</h1>
            <p class="mt-1 text-sm text-slate-500">Live operational data, multi-dimensional HTML pivot tables, consumable balances, and multi-tab Excel workbooks.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @can('export', \App\Models\Asset::class)
                <a href="{{ route('assets.export', request()->query()) }}" class="btn btn-primary inline-flex items-center gap-2 shadow-sm">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export Multi-Tab Excel Workbook
                </a>
            @else
                <p class="text-sm text-slate-500 self-center">Your role can view this report but cannot export data.</p>
            @endcan
        </div>
    </div>

    <!-- Top KPI Metric Cards -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total assets</p>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($totalAssets) }}</p>
            <p class="mt-1 text-xs text-slate-400">All registered equipment</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600">Available</p>
            <p class="mt-2 text-3xl font-extrabold text-emerald-700">{{ number_format($availableAssets) }}</p>
            <p class="mt-1 text-xs text-slate-400">Ready in stock</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-blue-600">Assigned</p>
            <p class="mt-2 text-3xl font-extrabold text-blue-700">{{ number_format($assignedAssets) }}</p>
            <p class="mt-1 text-xs text-slate-400">With active custodians</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-amber-600">Maintenance</p>
            <p class="mt-2 text-3xl font-extrabold text-amber-700">{{ number_format($maintenanceAssets) }}</p>
            <p class="mt-1 text-xs text-slate-400">Under service/repair</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-purple-600">Total Valuation</p>
            <p class="mt-2 text-2xl font-extrabold text-purple-700">₹{{ number_format($totalValuation, 0) }}</p>
            <p class="mt-1 text-xs text-slate-400">Historical acquisition cost</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-rose-600">Low stock items</p>
            <p class="mt-2 text-3xl font-extrabold text-rose-700">{{ number_format($lowStockConsumables) }}</p>
            <p class="mt-1 text-xs text-slate-400">Consumables below min</p>
        </div>
    </div>

    <!-- Interactive Navigation Tabs -->
    <div x-data="{ tab: 'pivot', pivotDim: 'type' }" class="space-y-6">
        <div class="border-b border-slate-200">
            <nav class="-mb-px flex space-x-6 overflow-x-auto" aria-label="Tabs">
                <button
                    @click="tab = 'pivot'"
                    :class="tab === 'pivot' ? 'border-indigo-600 text-indigo-600 font-bold' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                    class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition-colors cursor-pointer flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    HTML Pivot Table (Category &times; Status)
                </button>
                <button
                    @click="tab = 'assets'"
                    :class="tab === 'assets' ? 'border-indigo-600 text-indigo-600 font-bold' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                    class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition-colors cursor-pointer"
                >
                    Live Asset Register ({{ $filteredAssets->total() }})
                </button>
                <button
                    @click="tab = 'consumables'"
                    :class="tab === 'consumables' ? 'border-indigo-600 text-indigo-600 font-bold' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                    class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition-colors cursor-pointer"
                >
                    Consumable Stock Levels ({{ $consumables->count() }})
                </button>
                <button
                    @click="tab = 'definitions'"
                    :class="tab === 'definitions' ? 'border-indigo-600 text-indigo-600 font-bold' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                    class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition-colors cursor-pointer"
                >
                    Official Report Catalog ({{ $reportDefinitions->count() }})
                </button>
            </nav>
        </div>

        <!-- TAB 1: HTML PIVOT TABLE MATRIX -->
        <div x-show="tab === 'pivot'" class="space-y-6">
            <!-- Pivot Dimension Selector -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Pivot Dimension (Rows):</span>
                    <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-1">
                        <button
                            type="button"
                            @click="pivotDim = 'type'"
                            :class="pivotDim === 'type' ? 'bg-white text-indigo-600 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="rounded-md px-3 py-1 text-xs transition-all cursor-pointer"
                        >
                            By Asset Type
                        </button>
                        <button
                            type="button"
                            @click="pivotDim = 'category'"
                            :class="pivotDim === 'category' ? 'bg-white text-indigo-600 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="rounded-md px-3 py-1 text-xs transition-all cursor-pointer"
                        >
                            By Asset Category
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    @can('export', \App\Models\Asset::class)
                        <a href="{{ route('assets.export') }}" class="btn btn-secondary text-xs inline-flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Export Multi-Sheet Excel
                        </a>
                    @endcan
                </div>
            </div>

            <!-- DIMENSION 1: Asset Type x Status Pivot Matrix -->
            <div x-show="pivotDim === 'type'" class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="font-bold text-slate-900">Asset Type &times; Lifecycle Status Pivot Matrix</h2>
                        <p class="text-xs text-slate-500">Cross-tabulation showing inventory distribution across primary hardware types and operational states.</p>
                    </div>
                    <span class="badge bg-indigo-100 text-indigo-800 border-indigo-200 text-xs font-semibold">Live Real-time Matrix</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead class="bg-slate-100 text-xs font-bold uppercase tracking-wider text-slate-700 border-b border-slate-300">
                            <tr>
                                <th class="px-5 py-3.5 border-r border-slate-200">Asset Type</th>
                                <th class="px-4 py-3.5 text-center bg-emerald-50/80 text-emerald-900 border-r border-emerald-200">In Stock</th>
                                <th class="px-4 py-3.5 text-center bg-blue-50/80 text-blue-900 border-r border-blue-200">In Use</th>
                                <th class="px-4 py-3.5 text-center bg-amber-50/80 text-amber-900 border-r border-amber-200">Maintenance</th>
                                <th class="px-4 py-3.5 text-center bg-purple-50/80 text-purple-900 border-r border-purple-200">In Transit</th>
                                <th class="px-4 py-3.5 text-center bg-orange-50/80 text-orange-900 border-r border-orange-200">Pending Disp.</th>
                                <th class="px-4 py-3.5 text-center bg-slate-200/70 text-slate-800 border-r border-slate-300">Decomm.</th>
                                <th class="px-4 py-3.5 text-right font-extrabold bg-indigo-50/80 text-indigo-950 border-r border-indigo-200">Total Units</th>
                                <th class="px-5 py-3.5 text-right font-extrabold bg-purple-100/70 text-purple-950">Total Valuation</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($typePivot as $row)
                                <tr class="hover:bg-slate-50/90 transition-colors">
                                    <td class="px-5 py-3.5 font-bold text-slate-900 border-r border-slate-200 flex items-center gap-2">
                                        <span>{{ $row['label'] }}</span>
                                    </td>
                                    <!-- In Stock -->
                                    <td class="px-4 py-3.5 text-center border-r border-slate-100 {{ $row['counts']['in_stock'] > 0 ? 'font-bold text-emerald-700 bg-emerald-50/30' : 'text-slate-300' }}">
                                        @if ($row['counts']['in_stock'] > 0)
                                            <a href="{{ route('reports.inventory', ['type' => $row['type']->value, 'status' => 'in_stock']) }}" class="hover:underline font-mono">
                                                {{ number_format($row['counts']['in_stock']) }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <!-- In Use -->
                                    <td class="px-4 py-3.5 text-center border-r border-slate-100 {{ $row['counts']['in_use'] > 0 ? 'font-bold text-blue-700 bg-blue-50/30' : 'text-slate-300' }}">
                                        @if ($row['counts']['in_use'] > 0)
                                            <a href="{{ route('reports.inventory', ['type' => $row['type']->value, 'status' => 'in_use']) }}" class="hover:underline font-mono">
                                                {{ number_format($row['counts']['in_use']) }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <!-- Maintenance -->
                                    <td class="px-4 py-3.5 text-center border-r border-slate-100 {{ $row['counts']['under_maintenance'] > 0 ? 'font-bold text-amber-700 bg-amber-50/30' : 'text-slate-300' }}">
                                        @if ($row['counts']['under_maintenance'] > 0)
                                            <a href="{{ route('reports.inventory', ['type' => $row['type']->value, 'status' => 'under_maintenance']) }}" class="hover:underline font-mono">
                                                {{ number_format($row['counts']['under_maintenance']) }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <!-- In Transit -->
                                    <td class="px-4 py-3.5 text-center border-r border-slate-100 {{ $row['counts']['in_transit'] > 0 ? 'font-bold text-purple-700 bg-purple-50/30' : 'text-slate-300' }}">
                                        @if ($row['counts']['in_transit'] > 0)
                                            <a href="{{ route('reports.inventory', ['type' => $row['type']->value, 'status' => 'in_transit']) }}" class="hover:underline font-mono">
                                                {{ number_format($row['counts']['in_transit']) }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <!-- Pending Disposal -->
                                    <td class="px-4 py-3.5 text-center border-r border-slate-100 {{ $row['counts']['pending_disposal'] > 0 ? 'font-bold text-orange-700 bg-orange-50/30' : 'text-slate-300' }}">
                                        @if ($row['counts']['pending_disposal'] > 0)
                                            <a href="{{ route('reports.inventory', ['type' => $row['type']->value, 'status' => 'pending_disposal']) }}" class="hover:underline font-mono">
                                                {{ number_format($row['counts']['pending_disposal']) }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <!-- Decommissioned -->
                                    <td class="px-4 py-3.5 text-center border-r border-slate-100 {{ $row['counts']['decommissioned'] > 0 ? 'font-semibold text-slate-700 bg-slate-100/50' : 'text-slate-300' }}">
                                        @if ($row['counts']['decommissioned'] > 0)
                                            <a href="{{ route('reports.inventory', ['type' => $row['type']->value, 'status' => 'decommissioned']) }}" class="hover:underline font-mono">
                                                {{ number_format($row['counts']['decommissioned']) }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <!-- Row Total Count -->
                                    <td class="px-4 py-3.5 text-right font-extrabold text-slate-900 bg-indigo-50/40 border-r border-indigo-100 font-mono">
                                        {{ number_format($row['total']) }}
                                    </td>
                                    <!-- Row Total Valuation -->
                                    <td class="px-5 py-3.5 text-right font-bold text-purple-900 bg-purple-50/40 font-mono">
                                        ₹{{ number_format($row['valuation'], 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <!-- GRAND TOTAL ROW -->
                        <tfoot class="bg-slate-900 text-white font-extrabold border-t-2 border-slate-900">
                            <tr>
                                <td class="px-5 py-4 uppercase tracking-wider text-xs border-r border-slate-800">GRAND TOTAL</td>
                                <td class="px-4 py-4 text-center font-mono text-emerald-400 border-r border-slate-800">{{ number_format($columnTotals['in_stock']) }}</td>
                                <td class="px-4 py-4 text-center font-mono text-blue-400 border-r border-slate-800">{{ number_format($columnTotals['in_use']) }}</td>
                                <td class="px-4 py-4 text-center font-mono text-amber-400 border-r border-slate-800">{{ number_format($columnTotals['under_maintenance']) }}</td>
                                <td class="px-4 py-4 text-center font-mono text-purple-400 border-r border-slate-800">{{ number_format($columnTotals['in_transit']) }}</td>
                                <td class="px-4 py-4 text-center font-mono text-orange-400 border-r border-slate-800">{{ number_format($columnTotals['pending_disposal']) }}</td>
                                <td class="px-4 py-4 text-center font-mono text-slate-300 border-r border-slate-800">{{ number_format($columnTotals['decommissioned']) }}</td>
                                <td class="px-4 py-4 text-right font-mono text-white text-base border-r border-slate-800">{{ number_format($totalAssets) }}</td>
                                <td class="px-5 py-4 text-right font-mono text-yellow-300 text-base">₹{{ number_format($totalValuation, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- DIMENSION 2: Asset Category x Status Pivot Matrix -->
            <div x-show="pivotDim === 'category'" class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="font-bold text-slate-900">Asset Category &times; Lifecycle Status Pivot Matrix</h2>
                        <p class="text-xs text-slate-500">Fine-grained breakdown of assets categorized into hardware families and functional classifications.</p>
                    </div>
                    <span class="badge bg-purple-100 text-purple-800 border-purple-200 text-xs font-semibold">{{ count($categoryPivot) }} Categories</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead class="bg-slate-100 text-xs font-bold uppercase tracking-wider text-slate-700 border-b border-slate-300">
                            <tr>
                                <th class="px-5 py-3.5 border-r border-slate-200">Category Name</th>
                                <th class="px-3 py-3.5 border-r border-slate-200">Family</th>
                                <th class="px-4 py-3.5 text-center bg-emerald-50/80 text-emerald-900 border-r border-emerald-200">In Stock</th>
                                <th class="px-4 py-3.5 text-center bg-blue-50/80 text-blue-900 border-r border-blue-200">In Use</th>
                                <th class="px-4 py-3.5 text-center bg-amber-50/80 text-amber-900 border-r border-amber-200">Maintenance</th>
                                <th class="px-4 py-3.5 text-center bg-purple-50/80 text-purple-900 border-r border-purple-200">In Transit</th>
                                <th class="px-4 py-3.5 text-center bg-orange-50/80 text-orange-900 border-r border-orange-200">Pending Disp.</th>
                                <th class="px-4 py-3.5 text-center bg-slate-200/70 text-slate-800 border-r border-slate-300">Decomm.</th>
                                <th class="px-4 py-3.5 text-right font-extrabold bg-indigo-50/80 text-indigo-950 border-r border-indigo-200">Total</th>
                                <th class="px-5 py-3.5 text-right font-extrabold bg-purple-100/70 text-purple-950">Valuation</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($categoryPivot as $catRow)
                                <tr class="hover:bg-slate-50/90 transition-colors">
                                    <td class="px-5 py-3.5 font-bold text-slate-900 border-r border-slate-200">
                                        <div>{{ $catRow['name'] }}</div>
                                        <div class="text-[11px] font-mono text-slate-400">{{ $catRow['code'] }}</div>
                                    </td>
                                    <td class="px-3 py-3.5 border-r border-slate-200">
                                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700">
                                            {{ $catRow['family'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 text-center border-r border-slate-100 {{ $catRow['counts']['in_stock'] > 0 ? 'font-bold text-emerald-700' : 'text-slate-300' }}">
                                        {{ $catRow['counts']['in_stock'] > 0 ? number_format($catRow['counts']['in_stock']) : '—' }}
                                    </td>
                                    <td class="px-4 py-3.5 text-center border-r border-slate-100 {{ $catRow['counts']['in_use'] > 0 ? 'font-bold text-blue-700' : 'text-slate-300' }}">
                                        {{ $catRow['counts']['in_use'] > 0 ? number_format($catRow['counts']['in_use']) : '—' }}
                                    </td>
                                    <td class="px-4 py-3.5 text-center border-r border-slate-100 {{ $catRow['counts']['under_maintenance'] > 0 ? 'font-bold text-amber-700' : 'text-slate-300' }}">
                                        {{ $catRow['counts']['under_maintenance'] > 0 ? number_format($catRow['counts']['under_maintenance']) : '—' }}
                                    </td>
                                    <td class="px-4 py-3.5 text-center border-r border-slate-100 {{ $catRow['counts']['in_transit'] > 0 ? 'font-bold text-purple-700' : 'text-slate-300' }}">
                                        {{ $catRow['counts']['in_transit'] > 0 ? number_format($catRow['counts']['in_transit']) : '—' }}
                                    </td>
                                    <td class="px-4 py-3.5 text-center border-r border-slate-100 {{ $catRow['counts']['pending_disposal'] > 0 ? 'font-bold text-orange-700' : 'text-slate-300' }}">
                                        {{ $catRow['counts']['pending_disposal'] > 0 ? number_format($catRow['counts']['pending_disposal']) : '—' }}
                                    </td>
                                    <td class="px-4 py-3.5 text-center border-r border-slate-100 {{ $catRow['counts']['decommissioned'] > 0 ? 'font-semibold text-slate-700' : 'text-slate-300' }}">
                                        {{ $catRow['counts']['decommissioned'] > 0 ? number_format($catRow['counts']['decommissioned']) : '—' }}
                                    </td>
                                    <td class="px-4 py-3.5 text-right font-extrabold text-slate-900 bg-indigo-50/30 border-r border-indigo-100 font-mono">
                                        {{ number_format($catRow['total']) }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-bold text-purple-900 bg-purple-50/30 font-mono">
                                        ₹{{ number_format($catRow['valuation'], 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 2: LIVE ASSET REGISTER TABLE WITH FILTERS -->
        <div x-show="tab === 'assets'" class="space-y-6">
            <!-- Filter & Search Controls -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-5 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Filter &amp; Search Inventory</h2>
                        <p class="mt-1 text-sm text-slate-500">Filter the live HTML table and export filtered registers.</p>
                    </div>
                    @can('export', \App\Models\Asset::class)
                        <a href="{{ route('assets.export', request()->query()) }}" class="btn btn-secondary text-xs">
                            Export Excel report
                        </a>
                    @endcan
                </div>
                <form method="GET" action="{{ route('reports.inventory') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <label class="text-sm font-semibold text-slate-700">Asset type
                        <select name="type" class="form-control mt-1">
                            <option value="">All types</option>
                            @foreach (\App\Enums\AssetType::cases() as $type)
                                <option value="{{ $type->value }}" @selected(($filters['type'] ?? '') == $type->value)>{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm font-semibold text-slate-700">Status
                        <select name="status" class="form-control mt-1">
                            <option value="">All statuses</option>
                            @foreach (\App\Enums\AssetStatus::cases() as $status)
                                <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') == $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm font-semibold text-slate-700">Location
                        <select name="location_id" class="form-control mt-1">
                            <option value="">All locations</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}" @selected(($filters['location_id'] ?? '') == $location->id)>{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm font-semibold text-slate-700">Search
                        <input name="search" class="form-control mt-1" value="{{ $filters['search'] ?? '' }}" placeholder="Tag, serial, model or name">
                    </label>
                    <div class="md:col-span-2 xl:col-span-4 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5">
                        <button class="btn btn-primary" type="submit">Apply Filter</button>
                        @if (filled(array_filter($filters ?? [])))
                            <a class="btn btn-secondary" href="{{ route('reports.inventory') }}">Clear Filters</a>
                        @endif
                        <a class="btn btn-secondary" href="{{ route('assets.index') }}">Open asset register</a>
                        <a class="btn btn-secondary" href="{{ route('stock.index') }}">View stock history</a>
                    </div>
                </form>
            </div>

            <!-- HTML Data Table -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="font-bold text-slate-900">Current Filtered Assets (HTML View)</h2>
                        <p class="text-xs text-slate-500">Showing page {{ $filteredAssets->currentPage() }} of {{ $filteredAssets->lastPage() }} ({{ $filteredAssets->total() }} total assets)</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-3">Asset Tag</th>
                                <th class="px-6 py-3">Name &amp; Category</th>
                                <th class="px-6 py-3">Serial No.</th>
                                <th class="px-6 py-3">Location / Custodian</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3 text-right">Cost</th>
                                <th class="px-6 py-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($filteredAssets as $asset)
                                <tr class="hover:bg-slate-50/75 transition-colors">
                                    <td class="px-6 py-4 font-mono font-bold text-indigo-600">
                                        <a href="{{ route('assets.show', $asset) }}" class="hover:underline">
                                            {{ $asset->asset_tag ?? 'AST-'.$asset->id }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-900">{{ $asset->name }}</div>
                                        <div class="text-xs text-slate-500">
                                            {{ $asset->category?->name ?? $asset->asset_type?->label() ?? 'General' }}
                                            @if($asset->manufacturer) &middot; {{ $asset->manufacturer->name }} @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 font-mono text-xs text-slate-600">
                                        {{ $asset->serial_number ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-slate-900">{{ $asset->location?->name ?? 'Unassigned Location' }}</div>
                                        <div class="text-xs text-slate-500">{{ $asset->assignedOfficial?->name ?? 'No Custodian' }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        @php
                                            $color = match ($asset->status->value) {
                                                'in_stock' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                'in_use' => 'bg-blue-50 text-blue-700 border-blue-200',
                                                'under_maintenance' => 'bg-amber-50 text-amber-700 border-amber-200',
                                                'in_transit' => 'bg-purple-50 text-purple-700 border-purple-200',
                                                'pending_disposal' => 'bg-orange-50 text-orange-700 border-orange-200',
                                                'disposed' => 'bg-slate-100 text-slate-600 border-slate-300',
                                                default => 'bg-slate-50 text-slate-700 border-slate-200',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $color }}">
                                            {{ $asset->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right font-medium text-slate-900">
                                        {{ $asset->purchase_cost ? '₹'.number_format($asset->purchase_cost, 2) : '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <a href="{{ route('assets.show', $asset) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-sm text-slate-500">
                                        No assets match the current report filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($filteredAssets->hasPages())
                    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                        {{ $filteredAssets->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- TAB 3: Consumables & Stock Levels Table -->
        <div x-show="tab === 'consumables'" class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="font-bold text-slate-900">Consumable Stock Status &amp; Reorder Alerts</h2>
                        <p class="text-xs text-slate-500">Live consumable inventory levels and safety buffer monitoring.</p>
                    </div>
                    <a href="{{ route('stock.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Open Full Stock Ledger &rarr;</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-3">Item / SKU</th>
                                <th class="px-6 py-3">Unit</th>
                                <th class="px-6 py-3 text-right">In Stock</th>
                                <th class="px-6 py-3 text-right">Min Threshold</th>
                                <th class="px-6 py-3 text-center">Stock Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($consumables as $item)
                                @php $isLow = $item->min_quantity && ($item->in_stock <= $item->min_quantity); @endphp
                                <tr class="hover:bg-slate-50/75">
                                    <td class="px-6 py-4">
                                        <a href="{{ route('consumables.show', $item) }}" class="font-semibold text-indigo-600 hover:underline">
                                            {{ $item->name }}
                                        </a>
                                        @if($item->sku) <span class="text-xs text-slate-400 font-mono ml-1">({{ $item->sku }})</span> @endif
                                    </td>
                                    <td class="px-6 py-4 text-slate-600">{{ $item->unit ?? 'Units' }}</td>
                                    <td class="px-6 py-4 text-right font-bold {{ $isLow ? 'text-rose-600' : 'text-slate-900' }}">
                                        {{ number_format($item->in_stock) }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-slate-500">
                                        {{ $item->min_quantity ? number_format($item->min_quantity) : '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if ($isLow)
                                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                                Low Stock Alert
                                            </span>
                                        @else
                                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                Adequate
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-sm text-slate-500">No consumable records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 4: Official Report Catalog -->
        <div x-show="tab === 'definitions'" class="space-y-6">
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($reportDefinitions as $def)
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="rounded bg-indigo-50 px-2 py-0.5 font-mono text-xs font-bold text-indigo-700">{{ $def->code }}</span>
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">{{ $def->category->label() }}</span>
                            </div>
                            <h3 class="mt-3 font-bold text-slate-900">{{ $def->name }}</h3>
                            <p class="mt-1 text-xs text-slate-500 leading-relaxed">{{ $def->description }}</p>
                        </div>
                        <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                            <span>Formats: {{ implode(', ', array_map('strtoupper', $def->allowed_formats ?? ['HTML', 'XLSX'])) }}</span>
                            <span class="font-semibold text-emerald-600">Certified &amp; Signed</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
