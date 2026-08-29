@extends('layouts.app')

@section('content')
@if (config('inventory.poc_ui_mode'))
    @include('dashboard-poc')
@else
<div class="space-y-6">
    <!-- Top Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Enterprise Operations Dashboard</h1>
            <p class="text-sm text-slate-500 mt-1">Real-time IT hardware lifecycle metrics, stock inventory, and contract obligations.</p>
        </div>
        <div class="flex items-center space-x-3">
            @if (auth()->user()?->canManageInventory())
            <a href="{{ route('assets.create') }}" class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Asset
            </a>
            @endif
            @if (auth()->user()?->canPostStockEntries())
            <a href="{{ route('consumables.index') }}" class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 shadow-xs transition-colors">
                <svg class="w-4 h-4 mr-1.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                Consumables
            </a>
            @endif
        </div>
    </div>

    <!-- 4 Major KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- KPI 1: Assets -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold tracking-wider text-slate-400 uppercase">Total IT Assets</span>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-3xl font-extrabold text-slate-900">{{ number_format($total_assets ?? 0) }}</div>
                <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span class="inline-flex items-center text-blue-600 font-medium">
                        <span class="w-2 h-2 rounded-full bg-blue-500 mr-1.5"></span>
                        {{ $assets_in_use ?? 0 }} In Use
                    </span>
                    <span class="inline-flex items-center text-emerald-600 font-medium">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 mr-1.5"></span>
                        {{ $assets_in_stock ?? 0 }} In Stock
                    </span>
                    @if (!empty($assets_decommissioned))
                    <span class="inline-flex items-center text-slate-400 font-medium">
                        {{ $assets_decommissioned }} Decom
                    </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- KPI 2: Expiring Agreements -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold tracking-wider text-slate-400 uppercase">Expiring Agreements</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-3xl font-extrabold text-slate-900">{{ number_format(($agreements_expiring_30_days ?? 0) + ($agreements_expiring_180_days ?? 0)) }}</div>
                <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span class="text-amber-600 font-medium">
                        {{ $agreements_expiring_30_days ?? 0 }} in &le;30d
                    </span>
                    <span class="text-slate-500">
                        {{ $agreements_expiring_180_days ?? 0 }} in &le;180d
                    </span>
                    @if (!empty($agreements_expired))
                    <span class="text-rose-600 font-bold">
                        {{ $agreements_expired }} Expired
                    </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- KPI 3: Consumables & Low Stock -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold tracking-wider text-slate-400 uppercase">Low Stock Consumables</span>
                <div class="w-10 h-10 rounded-xl {{ ($low_stock_consumables ?? 0) > 0 ? 'bg-rose-50 text-rose-600' : 'bg-emerald-50 text-emerald-600' }} flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-3xl font-extrabold {{ ($low_stock_consumables ?? 0) > 0 ? 'text-rose-600' : 'text-slate-900' }}">
                    {{ number_format($low_stock_consumables ?? 0) }}
                </div>
                <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span>{{ ($low_stock_consumables ?? 0) > 0 ? 'Requires immediate restock' : 'All items healthy' }}</span>
                    <span class="font-medium text-slate-700">{{ $total_consumables ?? 0 }} SKUs tracked</span>
                </div>
            </div>
        </div>

        <!-- KPI 4: Pending & Overdue Payments -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold tracking-wider text-slate-400 uppercase">Payment Obligations</span>
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-3xl font-extrabold text-slate-900">{{ number_format($pending_payments ?? 0) }}</div>
                <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span class="font-medium {{ ($overdue_payments ?? 0) > 0 ? 'text-rose-600 font-bold' : 'text-slate-500' }}">
                        {{ $overdue_payments ?? 0 }} Overdue
                    </span>
                    <span class="text-emerald-600 font-medium">
                        {{ $completed_payments ?? 0 }} Completed
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Asset Distribution Chips -->
    @if (!empty($assets_by_type))
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Hardware Fleet Breakdown</h2>
            <a href="{{ route('assets.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">View All Assets &rarr;</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
            @foreach(['desktop' => 'Desktops', 'laptop' => 'Laptops', 'server' => 'Servers', 'switch' => 'Switches', 'storage' => 'Storage Units'] as $typeKey => $typeTitle)
                <a href="{{ route('assets.index', ['type' => $typeKey]) }}" class="flex items-center justify-between p-3 rounded-xl bg-slate-50 hover:bg-indigo-50 border border-slate-200/60 hover:border-indigo-200 transition-all group">
                    <span class="text-xs font-medium text-slate-700 group-hover:text-indigo-900">{{ $typeTitle }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-white text-indigo-600 shadow-xs border border-slate-200/80">
                        {{ $assets_by_type[$typeKey] ?? 0 }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Main 2-Column Section: Action Center & Live Activity Feed -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Action Center (2 Cols on Large screens) -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></div>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Action Center &amp; Urgent Tasks</h2>
                    </div>
                    <span class="text-xs text-slate-400">Automated System Audits</span>
                </div>
                <div class="p-6 divide-y divide-slate-100 space-y-4">
                    <!-- Urgent Contract Renewal Alert -->
                    <div class="flex items-center justify-between pt-3 first:pt-0">
                        <div class="flex items-start space-x-3.5">
                            <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">Contract & AMC Expirations</h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    {{ $agreements_expiring_30_days ?? 0 }} contracts expiring within 30 days. Action required for renewals.
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('agreements.index', ['filter' => 'due']) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100 rounded-lg transition-colors">
                            Review Due
                        </a>
                    </div>

                    <!-- Low Stock Items Alert -->
                    <div class="flex items-center justify-between pt-4">
                        <div class="flex items-start space-x-3.5">
                            <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">Low Stock Reorders</h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    {{ $low_stock_consumables ?? 0 }} items have fallen at or below minimum reserve quantities.
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('consumables.index', ['low_stock' => 1]) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 rounded-lg transition-colors">
                            Restock SKUs
                        </a>
                    </div>

                    <!-- Overdue Payments Alert -->
                    <div class="flex items-center justify-between pt-4">
                        <div class="flex items-start space-x-3.5">
                            <div class="w-9 h-9 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">Overdue Milestone Invoices</h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    {{ $overdue_payments ?? 0 }} scheduled vendor payments have exceeded their due dates.
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('payments.index', ['status' => 'overdue']) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-purple-700 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors">
                            Process Payments
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live Activity Stream (1 Col) -->
        <div class="space-y-4">
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Live Activity Stream</h2>
                    <span class="text-xs font-medium text-emerald-600 flex items-center">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                        Audit Stream
                    </span>
                </div>
                <div class="p-6">
                    @if (isset($recent_activities) && $recent_activities->count() > 0)
                        <div class="flow-root">
                            <ul class="-mb-6">
                                @foreach ($recent_activities as $activity)
                                    <li class="relative pb-6">
                                        @if (!$loop->last)
                                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-slate-200" aria-hidden="true"></span>
                                        @endif
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="h-8 w-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center ring-4 ring-white text-xs font-bold">
                                                    {{ strtoupper(substr($activity->causer?->name ?? 'Sys', 0, 2)) }}
                                                </span>
                                            </div>
                                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                <div>
                                                    <p class="text-xs text-slate-800 font-medium">
                                                        <span class="font-semibold text-slate-900">{{ $activity->causer?->name ?? 'System' }}</span>
                                                        {{ $activity->description }}
                                                        <span class="font-semibold text-indigo-600">{{ class_basename($activity->subject_type ?? '') }}</span>
                                                    </p>
                                                </div>
                                                <div class="text-right text-[11px] whitespace-nowrap text-slate-400">
                                                    <time datetime="{{ $activity->created_at }}">{{ $activity->created_at?->diffForHumans() }}</time>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <p class="text-xs text-slate-500 font-medium">No recent audit activity records found.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
