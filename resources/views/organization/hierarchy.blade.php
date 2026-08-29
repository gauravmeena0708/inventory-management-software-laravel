@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="{
    viewMode: 'tree',
    openNodes: {},
    searchTerm: '{{ addslashes($filters['search'] ?? '') }}',
    init() {
        const hasSearch = this.searchTerm.trim().length > 0;
        @foreach ($units as $unit)
            @if ($unit->depth <= 2)
                this.openNodes[{{ $unit->id }}] = true;
            @else
                this.openNodes[{{ $unit->id }}] = hasSearch;
            @endif
        @endforeach
    },
    toggle(id) {
        this.openNodes[id] = !this.openNodes[id];
    },
    expandAll() {
        @foreach ($units as $unit)
            this.openNodes[{{ $unit->id }}] = true;
        @endforeach
    },
    collapseAll() {
        @foreach ($units as $unit)
            @if ($unit->depth <= 1)
                this.openNodes[{{ $unit->id }}] = true;
            @else
                this.openNodes[{{ $unit->id }}] = false;
            @endif
        @endforeach
    },
    isOpen(id) {
        return !!this.openNodes[id];
    }
}">
    <!-- Header -->
    <header class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-indigo-700">{{ $activeContext?->name ?? 'All authorized memberships' }}</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">Organization &amp; Physical Hierarchy</h1>
            <p class="mt-1 text-sm text-slate-600">Browse expandable/collapsible administrative offices, authorized sites, buildings, floors, rooms, and racks.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <div class="inline-flex rounded-lg border border-slate-200 bg-white p-1 shadow-sm">
                <button
                    type="button"
                    @click="viewMode = 'tree'"
                    :class="viewMode === 'tree' ? 'bg-indigo-600 text-white font-semibold' : 'text-slate-600 hover:text-slate-900'"
                    class="rounded-md px-3 py-1.5 text-xs transition-colors flex items-center gap-1.5 cursor-pointer"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                    Tree View
                </button>
                <button
                    type="button"
                    @click="viewMode = 'graph'"
                    :class="viewMode === 'graph' ? 'bg-indigo-600 text-white font-semibold' : 'text-slate-600 hover:text-slate-900'"
                    class="rounded-md px-3 py-1.5 text-xs transition-colors flex items-center gap-1.5 cursor-pointer"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l5 5 5-5M12 4v13"/></svg>
                    Org Chart / Graph
                </button>
            </div>
            <a class="btn btn-secondary text-xs" href="{{ route('locations.index') }}">Manage rooms &amp; stores</a>
            @can('create', \App\Models\Location::class)
                <a class="btn btn-primary text-xs" href="{{ route('locations.create') }}">Add room or store</a>
            @endcan
        </div>
    </header>

    <!-- Search & Filter Controls -->
    <form method="GET" class="card card-body grid gap-3 md:grid-cols-[1fr_1fr_auto]" role="search" aria-label="Search physical hierarchy">
        <div>
            <label class="block text-sm font-semibold text-slate-700" for="hierarchy-search">Hierarchy search</label>
            <input id="hierarchy-search" class="form-control mt-1" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, code, building, floor, room or office">
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
        <div class="flex gap-2 self-end">
            <button class="btn btn-primary" type="submit">Filter</button>
            @if (filled($filters['search'] ?? null) || filled($filters['site_id'] ?? null))
                <a class="btn btn-secondary" href="{{ route('organization.hierarchy') }}">Reset</a>
            @endif
        </div>
    </form>

    <!-- SECTION 1: Expandable/Collapsible Administrative Office Hierarchy Tree -->
    <section class="card shadow-sm overflow-hidden" aria-labelledby="organizational-units-heading">
        <div class="card-header flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 id="organizational-units-heading" class="font-bold text-slate-900">Authorized organizational units</h2>
                <p class="mt-0.5 text-xs text-slate-500">Interactive expandable/collapsible administrative hierarchy with parent-child structure.</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="expandAll()" class="btn btn-secondary text-xs inline-flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    Expand All
                </button>
                <button type="button" @click="collapseAll()" class="btn btn-secondary text-xs inline-flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                    Collapse All
                </button>
                <span class="badge bg-success font-semibold">{{ $units->count() }} Units</span>
            </div>
        </div>

        <!-- TREE VIEW -->
        <div x-show="viewMode === 'tree'" class="card-body p-4 space-y-1.5">
            @php
                $childrenByParent = [];
                foreach ($units as $u) {
                    $pid = $u->parent_id ?? 0;
                    $childrenByParent[$pid][] = $u;
                }
            @endphp

            @if ($units->isEmpty())
                <p class="py-4 text-center text-sm text-slate-500">No active organizational unit is available in this context.</p>
            @else
                <ul class="space-y-1.5" role="list">
                    @foreach ($units as $unit)
                        @php
                            $hasChildren = isset($childrenByParent[$unit->id]) && count($childrenByParent[$unit->id]) > 0;
                            $childCount = $hasChildren ? count($childrenByParent[$unit->id]) : 0;
                            $indent = min(max($unit->depth - 1, 0), 5);

                            $ancestorCheck = '';
                            if ($unit->parent_id) {
                                $ancestorCheck = 'isOpen('.$unit->parent_id.')';
                            }
                        @endphp
                        <li
                            @if($ancestorCheck) x-show="{{ $ancestorCheck }}" x-transition @endif
                            class="rounded-xl border border-slate-100 bg-white hover:border-slate-300 hover:bg-slate-50/70 p-3 transition-all flex flex-wrap items-center justify-between gap-3"
                            style="margin-left: {{ $indent * 1.5 }}rem"
                        >
                            <div class="flex items-center gap-3">
                                @if ($hasChildren)
                                    <button
                                        type="button"
                                        @click="toggle({{ $unit->id }})"
                                        class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white hover:bg-indigo-50 hover:border-indigo-300 text-slate-600 hover:text-indigo-600 transition-colors cursor-pointer"
                                        :title="isOpen({{ $unit->id }}) ? 'Click to collapse' : 'Click to expand'"
                                    >
                                        <svg
                                            class="h-4 w-4 transition-transform duration-200"
                                            :class="isOpen({{ $unit->id }}) ? 'rotate-90 text-indigo-600' : 'text-slate-400'"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </button>
                                @else
                                    <div class="flex h-7 w-7 items-center justify-center text-slate-300">
                                        <span class="h-1.5 w-1.5 rounded-full bg-slate-300"></span>
                                    </div>
                                @endif

                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-bold text-slate-900 text-sm">{{ $unit->name }}</span>
                                        <span class="font-mono text-xs text-slate-500">{{ $unit->code }}</span>
                                        <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600">
                                            {{ str_replace('_', ' ', $unit->unit_type->value) }}
                                        </span>
                                    </div>
                                    @if ($unit->parent)
                                        <p class="text-[11px] text-slate-400 mt-0.5">Parent: {{ $unit->parent->name }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                @if ($hasChildren)
                                    <button
                                        type="button"
                                        @click="toggle({{ $unit->id }})"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors cursor-pointer"
                                    >
                                        <span x-text="isOpen({{ $unit->id }}) ? 'Collapse' : 'Expand'"></span>
                                        <span class="bg-white px-1.5 py-0.2 rounded-full text-[10px] font-bold text-indigo-600 shadow-xs">{{ $childCount }}</span>
                                    </button>
                                @endif
                                <a
                                    href="{{ route('reports.inventory', ['search' => $unit->name]) }}"
                                    class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 px-2 py-1"
                                >
                                    View Assets &rarr;
                                </a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <!-- GRAPH / CHART VIEW -->
        <div x-show="viewMode === 'graph'" class="card-body p-6 overflow-x-auto">
            <div class="min-w-[700px] flex flex-col items-center space-y-8">
                @php $rootUnits = $units->filter(fn ($u) => empty($u->parent_id) || $u->depth <= 1); @endphp
                <div class="flex flex-wrap justify-center gap-6">
                    @foreach ($rootUnits as $root)
                        <div class="rounded-2xl border-2 border-indigo-500 bg-indigo-50/80 p-5 shadow-sm text-center max-w-sm">
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-indigo-600 text-white uppercase tracking-wider">
                                Head Office / Root
                            </span>
                            <h3 class="mt-2 text-base font-bold text-slate-900">{{ $root->name }}</h3>
                            <p class="text-xs text-slate-500 font-mono">{{ $root->code }}</p>
                            @php $subCount = $units->where('parent_id', $root->id)->count(); @endphp
                            @if ($subCount > 0)
                                <div class="mt-3 pt-3 border-t border-indigo-200/60">
                                    <span class="text-xs font-semibold text-indigo-700">{{ $subCount }} Subordinate Divisions</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="h-8 w-0.5 bg-slate-300"></div>

                @php $level2Units = $units->filter(fn ($u) => $u->depth === 2); @endphp
                @if ($level2Units->count() > 0)
                    <div class="w-full">
                        <p class="text-center text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Zonal Offices &amp; Specialist Wings</p>
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            @foreach ($level2Units as $l2)
                                @php $l3Count = $units->where('parent_id', $l2->id)->count(); @endphp
                                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-indigo-300 transition-colors">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="rounded bg-purple-50 px-2 py-0.5 text-[11px] font-bold text-purple-700">{{ $l2->code }}</span>
                                        <span class="text-[11px] font-semibold text-slate-500">{{ $l3Count }} Sub-offices</span>
                                    </div>
                                    <h4 class="mt-2 text-sm font-bold text-slate-900">{{ $l2->name }}</h4>
                                    <p class="text-xs text-slate-400">{{ data_get($l2->metadata, 'city') ?? 'Regional Division' }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <!-- SECTION 2: Physical Location Tree -->
    <section class="space-y-4" aria-labelledby="physical-tree-heading">
        <div>
            <h2 id="physical-tree-heading" class="text-lg font-bold text-slate-900">Physical location tree</h2>
            <p class="mt-1 text-sm text-slate-600">Structured tree of sites, buildings, floors, rooms and racks.</p>
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

    <!-- SECTION 3: Structured Location List -->
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
@endsection
