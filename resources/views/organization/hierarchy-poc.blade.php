<div class="space-y-6" x-data="{
    viewMode: 'tree',
    openNodes: {},
    searchTerm: '{{ addslashes($filters['search'] ?? '') }}',
    init() {
        // Expand root and second level by default, or all if search term present
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
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-indigo-600">Enterprise Structure</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">Offices &amp; Organizational Hierarchy</h1>
            <p class="mt-1 max-w-3xl text-sm text-slate-600">Interactive expandable/collapsible organizational tree and administrative ownership structure.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <div class="inline-flex rounded-lg border border-slate-200 bg-white p-1 shadow-sm">
                <button
                    type="button"
                    @click="viewMode = 'tree'"
                    :class="viewMode === 'tree' ? 'bg-indigo-600 text-white font-semibold' : 'text-slate-600 hover:text-slate-900'"
                    class="rounded-md px-3 py-1.5 text-xs transition-colors flex items-center gap-1.5"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                    Tree View
                </button>
                <button
                    type="button"
                    @click="viewMode = 'graph'"
                    :class="viewMode === 'graph' ? 'bg-indigo-600 text-white font-semibold' : 'text-slate-600 hover:text-slate-900'"
                    class="rounded-md px-3 py-1.5 text-xs transition-colors flex items-center gap-1.5"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l5 5 5-5M12 4v13"/></svg>
                    Org Chart / Graph
                </button>
            </div>
            <a class="btn btn-secondary text-xs" href="{{ route('locations.index') }}">Rooms &amp; Physical Stores</a>
        </div>
    </header>

    <!-- Search & Expand Controls -->
    <div class="card card-body flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex flex-1 items-center gap-2" role="search">
            <div class="relative flex-1">
                <input class="form-control text-sm pl-9" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search Head Office, Zonal Office, Regional Office, NDC, city...">
                <svg class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <button class="btn btn-primary text-xs" type="submit">Search</button>
            @if (filled($filters['search'] ?? null))
                <a class="btn btn-secondary text-xs" href="{{ route('organization.hierarchy') }}">Reset</a>
            @endif
        </form>

        <div class="flex items-center gap-2 border-t sm:border-t-0 pt-2 sm:pt-0 border-slate-100">
            <button type="button" @click="expandAll()" class="btn btn-secondary text-xs inline-flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                Expand All
            </button>
            <button type="button" @click="collapseAll()" class="btn btn-secondary text-xs inline-flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                Collapse All
            </button>
        </div>
    </div>

    <!-- VIEW 1: Interactive Expandable/Collapsible Tree List -->
    <section x-show="viewMode === 'tree'" class="card shadow-sm overflow-hidden" aria-labelledby="office-tree-heading">
        <div class="card-header flex items-center justify-between">
            <div>
                <h2 id="office-tree-heading" class="font-bold text-slate-900">Interactive Office Hierarchy Tree</h2>
                <p class="mt-0.5 text-xs text-slate-500">Click any office node or toggle arrow to expand/collapse subordinate field offices.</p>
            </div>
            <span class="badge bg-success font-semibold">{{ $units->count() }} Total Offices</span>
        </div>
        <div class="card-body p-4 space-y-1">
            @php
                // Build a nested lookup mapping parent_id to child units
                $childrenByParent = [];
                $allUnitsById = $units->keyBy('id');
                foreach ($units as $u) {
                    $pid = $u->parent_id ?? 0;
                    $childrenByParent[$pid][] = $u;
                }
            @endphp

            @forelse ($units as $unit)
                @php
                    $hasChildren = isset($childrenByParent[$unit->id]) && count($childrenByParent[$unit->id]) > 0;
                    $childCount = $hasChildren ? count($childrenByParent[$unit->id]) : 0;
                    $indent = min(max($unit->depth - 1, 0), 5);

                    $typeBadgeColor = match ($unit->unit_type) {
                        \App\Enums\OrganizationalUnitType::HEAD_OFFICE, \App\Enums\OrganizationalUnitType::ROOT => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                        \App\Enums\OrganizationalUnitType::ZONAL_OFFICE => 'bg-purple-100 text-purple-800 border-purple-200',
                        \App\Enums\OrganizationalUnitType::REGIONAL_OFFICE => 'bg-blue-100 text-blue-800 border-blue-200',
                        \App\Enums\OrganizationalUnitType::NDC, \App\Enums\OrganizationalUnitType::ALTERNATE_DATA_CENTRE => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                        \App\Enums\OrganizationalUnitType::INTERNAL_AUDIT_WING, \App\Enums\OrganizationalUnitType::VIGILANCE_HQ => 'bg-amber-100 text-amber-800 border-amber-200',
                        default => 'bg-slate-100 text-slate-700 border-slate-200',
                    };

                    $typeLabel = match ($unit->unit_type) {
                        \App\Enums\OrganizationalUnitType::ROOT => 'Organisation',
                        \App\Enums\OrganizationalUnitType::HEAD_OFFICE => 'Head Office',
                        \App\Enums\OrganizationalUnitType::NDC => 'National Data Centre',
                        \App\Enums\OrganizationalUnitType::ALTERNATE_DATA_CENTRE => 'Alternate Data Centre',
                        \App\Enums\OrganizationalUnitType::ZONAL_OFFICE => 'Zonal Office',
                        \App\Enums\OrganizationalUnitType::REGIONAL_OFFICE => 'Regional Office',
                        \App\Enums\OrganizationalUnitType::DISTRICT_OFFICE => 'District Office',
                        \App\Enums\OrganizationalUnitType::VIGILANCE_HQ => 'Vigilance Wing',
                        \App\Enums\OrganizationalUnitType::INTERNAL_AUDIT_WING => 'Internal Audit Wing',
                        default => str($unit->unit_type->value)->headline()->toString(),
                    };

                    // Parent visibility expression
                    $ancestorCheck = '';
                    if ($unit->parent_id) {
                        // Check if immediate parent is open
                        $ancestorCheck = 'isOpen('.$unit->parent_id.')';
                    }
                @endphp

                <div
                    @if($ancestorCheck) x-show="{{ $ancestorCheck }}" x-transition @endif
                    class="rounded-xl border border-slate-100 bg-white hover:border-slate-300 hover:bg-slate-50/70 p-3 transition-all flex flex-wrap items-center justify-between gap-3"
                    style="margin-left: {{ $indent * 1.75 }}rem"
                >
                    <div class="flex items-center gap-3">
                        @if ($hasChildren)
                            <button
                                type="button"
                                @click="toggle({{ $unit->id }})"
                                class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white hover:bg-indigo-50 hover:border-indigo-300 text-slate-600 hover:text-indigo-600 transition-colors"
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
                                <span class="font-mono text-[11px] text-slate-400">({{ $unit->code }})</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold border {{ $typeBadgeColor }}">
                                    {{ $typeLabel }}
                                </span>
                                @if (filled(data_get($unit->metadata, 'city')))
                                    <span class="text-xs text-slate-500 font-medium">&bull; {{ data_get($unit->metadata, 'city') }}</span>
                                @endif
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
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors"
                            >
                                <span x-text="isOpen({{ $unit->id }}) ? 'Collapse' : 'Expand'"></span>
                                <span class="bg-white px-1.5 py-0.2 rounded-full text-[10px] font-bold text-indigo-600 shadow-xs">{{ $childCount }}</span>
                            </button>
                        @endif
                        <a
                            href="{{ route('reports.inventory', ['search' => $unit->name]) }}"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 px-2 py-1"
                            title="View inventory reports for this unit"
                        >
                            View Inventory &rarr;
                        </a>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-sm text-slate-500">
                    No offices match your search criteria.
                </div>
            @endforelse
        </div>
    </section>

    <!-- VIEW 2: Graphical Organization Chart -->
    <section x-show="viewMode === 'graph'" class="card shadow-sm overflow-x-auto p-6" aria-labelledby="org-chart-heading">
        <div class="mb-4">
            <h2 id="org-chart-heading" class="font-bold text-slate-900">Visual Organization Hierarchy Chart</h2>
            <p class="text-xs text-slate-500">Tier-by-tier visual structure of administrative divisions.</p>
        </div>

        <div class="min-w-[800px] flex flex-col items-center space-y-8">
            <!-- Level 1: Root / Head Office -->
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

            <!-- Connector Down -->
            <div class="h-8 w-0.5 bg-slate-300"></div>

            <!-- Level 2: Zonal / Specialized Wings -->
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
                                <p class="text-xs text-slate-400">{{ data_get($l2->metadata, 'city') ?? 'Regional Hub' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>

    <!-- Guidance Note -->
    <div class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-4 text-xs text-indigo-900 flex items-start gap-3">
        <svg class="h-5 w-5 text-indigo-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div>
            <span class="font-bold">Administrative Ownership vs. Physical Placement:</span>
            Office hierarchy maintains administrative and custody ownership across organizations. Physical placement down to buildings, floors, rooms, and IT stores is managed in <a class="font-bold underline hover:text-indigo-700" href="{{ route('locations.index') }}">Rooms &amp; Physical Stores</a>.
        </div>
    </div>
</div>
