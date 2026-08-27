@extends('layouts.app')

@section('content')
@if (config('inventory.poc_ui_mode'))
    @include('organization.hierarchy-poc')
@else
<div class="space-y-6">
    <header>
        <p class="text-sm font-semibold text-indigo-700">{{ $activeContext?->name ?? 'All authorized memberships' }}</p>
        <h1 class="mt-1 text-2xl font-bold text-slate-900">Organization and physical hierarchy</h1>
        <p class="mt-1 text-sm text-slate-600">Browse authorized units, sites, buildings, floors, rooms, and racks without requiring a spatial map.</p>
    </header>

    <form method="GET" class="card card-body grid gap-3 md:grid-cols-[1fr_1fr_auto]" role="search" aria-label="Search physical hierarchy">
        <div>
            <label class="block text-sm font-semibold text-slate-700" for="hierarchy-search">Location search</label>
            <input id="hierarchy-search" class="form-control mt-1" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, code, building, floor, or room">
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700" for="hierarchy-site">Authorized site</label>
            <select id="hierarchy-site" class="form-control mt-1" name="site_id">
                <option value="">All authorized sites</option>
                @foreach ($sites as $site)
                    <option value="{{ $site->id }}" @selected(($filters['site_id'] ?? '') == $site->id)>{{ $site->name }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-secondary self-end" type="submit">Filter</button>
    </form>

    <section class="card card-body" aria-labelledby="organizational-units-heading">
        <h2 id="organizational-units-heading" class="text-lg font-bold text-slate-900">Authorized organizational units</h2>
        @if ($units->isEmpty())
            <p class="mt-3 text-sm text-slate-600">No active organizational unit is available in this context.</p>
        @else
            <ul class="mt-3 space-y-2" role="list">
                @foreach ($units as $unit)
                    <li class="rounded-lg border border-slate-200 px-3 py-2" style="margin-left: {{ min($unit->depth, 6) * 1.25 }}rem">
                        <span class="font-semibold text-slate-900">{{ $unit->name }}</span>
                        <span class="ml-2 text-xs text-slate-500">{{ $unit->code }} · {{ str_replace('_', ' ', $unit->unit_type->value) }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="space-y-4" aria-labelledby="physical-tree-heading">
        <div>
            <h2 id="physical-tree-heading" class="text-lg font-bold text-slate-900">Physical location tree</h2>
            <p class="mt-1 text-sm text-slate-600">This structured tree is the accessible alternative when a map is unavailable or cannot be used.</p>
        </div>

        @forelse ($sites as $site)
            @php
                $siteLocations = $locations->where('site_id', $site->id)->values();
                $siteLocationIds = $siteLocations->pluck('id');
                $roots = $siteLocations->filter(fn ($location) => ! $location->parent_id || ! $siteLocationIds->contains($location->parent_id));
            @endphp
            @if (! request()->filled('site_id') || request()->integer('site_id') === $site->id)
                <article class="card card-body" aria-labelledby="site-{{ $site->id }}-heading">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 id="site-{{ $site->id }}-heading" class="text-base font-bold text-slate-900">{{ $site->name }}</h3>
                            <p class="text-sm text-slate-500">{{ $site->code }}{{ $site->address ? ' · '.$site->address : '' }}</p>
                        </div>
                        <span class="badge bg-success">{{ $siteLocations->count() }} locations</span>
                    </div>

                    @if ($roots->isEmpty())
                        <p class="mt-4 text-sm text-slate-600">No matching structured locations were found at this site.</p>
                    @else
                        <ul class="mt-4 space-y-2" role="tree" aria-label="{{ $site->name }} physical locations">
                            @foreach ($roots as $node)
                                @include('locations._tree-node', ['node' => $node, 'siteLocations' => $siteLocations])
                            @endforeach
                        </ul>
                    @endif
                </article>
            @endif
        @empty
            <div class="card card-body text-sm text-slate-600">No authorized active sites are available in this context.</div>
        @endforelse
    </section>

    <section class="card" aria-labelledby="structured-list-heading">
        <div class="card-header">
            <h2 id="structured-list-heading">Structured location list</h2>
        </div>
        <div class="card-body overflow-x-auto">
            <table class="table">
                <thead><tr><th>Name</th><th>Type</th><th>Site</th><th>Parent</th><th>Level</th></tr></thead>
                <tbody>
                    @forelse ($locations as $location)
                        <tr>
                            <td><a href="{{ route('locations.show', $location) }}">{{ $location->name }}</a></td>
                            <td>{{ str_replace('_', ' ', $location->location_type->value) }}</td>
                            <td>{{ $location->site?->name ?? 'Unmapped' }}</td>
                            <td>{{ $location->parent?->name ?? 'Site root' }}</td>
                            <td>{{ $location->level_number ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-slate-500">No matching authorized locations found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endif
@endsection
