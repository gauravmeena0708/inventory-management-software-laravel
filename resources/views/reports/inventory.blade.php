@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div>
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-indigo-600">Reports</p>
        <h1 class="mt-1 text-2xl font-bold text-slate-900">Inventory report</h1>
        <p class="mt-1 text-sm text-slate-500">Review the current inventory position and export a filtered asset register.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ([
            ['label' => 'Total assets', 'value' => $totalAssets],
            ['label' => 'Available', 'value' => $availableAssets],
            ['label' => 'Assigned', 'value' => $assignedAssets],
            ['label' => 'Maintenance', 'value' => $maintenanceAssets],
            ['label' => 'Low stock items', 'value' => $lowStockConsumables],
        ] as $metric)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $metric['label'] }}</p>
                <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($metric['value']) }}</p>
            </div>
        @endforeach
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-5">
            <h2 class="text-lg font-bold text-slate-900">Export asset register</h2>
            <p class="mt-1 text-sm text-slate-500">Choose optional filters. The spreadsheet uses the same secured inventory scope as the Assets screen.</p>
        </div>
        <form method="GET" action="{{ route('assets.export') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <label class="text-sm font-semibold text-slate-700">Asset type
                <select name="type" class="form-control mt-1">
                    <option value="">All types</option>
                    @foreach (\App\Enums\AssetType::cases() as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-semibold text-slate-700">Status
                <select name="status" class="form-control mt-1">
                    <option value="">All statuses</option>
                    @foreach (\App\Enums\AssetStatus::cases() as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-semibold text-slate-700">Location
                <select name="location_id" class="form-control mt-1">
                    <option value="">All locations</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-semibold text-slate-700">Search
                <input name="search" class="form-control mt-1" placeholder="Tag, serial, model or name">
            </label>
            <div class="md:col-span-2 xl:col-span-4 flex flex-wrap gap-3 border-t border-slate-100 pt-5">
                @can('export', \App\Models\Asset::class)
                    <button class="btn btn-primary" type="submit">Export Excel report</button>
                @else
                    <p class="text-sm text-slate-500">Your role can view this report but cannot export data.</p>
                @endcan
                <a class="btn btn-secondary" href="{{ route('assets.index') }}">Open asset register</a>
                <a class="btn btn-secondary" href="{{ route('stock.index') }}">View stock history</a>
            </div>
        </form>
    </div>
</div>
@endsection
