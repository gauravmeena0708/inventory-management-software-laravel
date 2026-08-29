<div class="space-y-6">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-indigo-600">Office directory</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">Offices &amp; locations</h1>
            <p class="mt-1 max-w-3xl text-sm text-slate-600">Browse the EPFO office structure. Buildings, floors, rooms and stores are maintained separately for exact asset placement.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a class="btn btn-secondary" href="{{ route('locations.index') }}">Manage rooms &amp; stores</a>
            @can('create', \App\Models\Location::class)
                <a class="btn btn-primary" href="{{ route('locations.create') }}">Add room or store</a>
            @endcan
        </div>
    </header>

    <form method="GET" class="card card-body flex flex-col gap-3 sm:flex-row" role="search" aria-label="Search office hierarchy">
        <label class="flex-1">
            <span class="text-sm font-semibold text-slate-700">Search offices</span>
            <input class="form-control mt-1" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Head Office, zone, regional office, NDC...">
        </label>
        <button class="btn btn-secondary self-end" type="submit">Search</button>
        @if (filled($filters['search'] ?? null))
            <a class="btn btn-secondary self-end" href="{{ route('organization.hierarchy') }}">Clear</a>
        @endif
    </form>

    <section class="card" aria-labelledby="office-tree-heading">
        <div class="card-header flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 id="office-tree-heading">Office hierarchy</h2>
                <p class="mt-1 text-sm font-normal text-slate-500">Head Office branches into field offices, specialist wings, data centres and training institutes.</p>
            </div>
            <span class="badge bg-success">{{ $units->count() }} offices</span>
        </div>
        <div class="card-body">
            @forelse ($units as $unit)
                @php
                    $indent = min(max($unit->depth - 1, 0), 4);
                    $typeLabel = match ($unit->unit_type) {
                        \App\Enums\OrganizationalUnitType::ROOT => 'Organisation',
                        \App\Enums\OrganizationalUnitType::HEAD_OFFICE => 'Head Office',
                        \App\Enums\OrganizationalUnitType::NDC => 'National Data Centre',
                        \App\Enums\OrganizationalUnitType::ALTERNATE_DATA_CENTRE => 'Alternate Data Centre',
                        \App\Enums\OrganizationalUnitType::ZONAL_OFFICE => 'Zonal Office',
                        \App\Enums\OrganizationalUnitType::REGIONAL_OFFICE => 'Regional Office',
                        \App\Enums\OrganizationalUnitType::DISTRICT_OFFICE => 'District Office',
                        \App\Enums\OrganizationalUnitType::SPECIAL_STATE_OFFICE => 'Special State Office',
                        \App\Enums\OrganizationalUnitType::VIGILANCE_HQ => 'Vigilance Wing',
                        \App\Enums\OrganizationalUnitType::VIGILANCE_ZVD => 'Zonal Vigilance Directorate',
                        \App\Enums\OrganizationalUnitType::INTERNAL_AUDIT_WING => 'Internal Audit Wing',
                        \App\Enums\OrganizationalUnitType::PDUNASS => 'National Academy',
                        \App\Enums\OrganizationalUnitType::ZTI => 'Zonal Training Institute',
                        \App\Enums\OrganizationalUnitType::HOLIDAY_HOME => 'Holiday Home',
                        default => str($unit->unit_type->value)->headline()->toString(),
                    };
                @endphp
                <div class="relative border-l-2 {{ $unit->unit_type === \App\Enums\OrganizationalUnitType::HEAD_OFFICE ? 'border-indigo-500 bg-indigo-50/60' : 'border-slate-200' }} py-3 pr-3" style="margin-left: {{ $indent * 1.5 }}rem; padding-left: 1rem">
                    <span class="absolute -left-[5px] top-5 h-2 w-2 rounded-full {{ $unit->unit_type === \App\Enums\OrganizationalUnitType::HEAD_OFFICE ? 'bg-indigo-600' : 'bg-slate-400' }}"></span>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                        <span class="font-bold text-slate-900">{{ $unit->name }}</span>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">{{ $typeLabel }}</span>
                        @if (filled(data_get($unit->metadata, 'city')))
                            <span class="text-xs text-slate-500">{{ data_get($unit->metadata, 'city') }}</span>
                        @endif
                    </div>
                    @if ($unit->parent)
                        <p class="mt-1 text-xs text-slate-400">Under {{ $unit->parent->name }}</p>
                    @endif
                </div>
            @empty
                <p class="py-6 text-center text-sm text-slate-500">No offices match the current search.</p>
            @endforelse
        </div>
    </section>

    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        Office hierarchy describes administrative ownership. Use <a class="font-bold underline" href="{{ route('locations.index') }}">Rooms &amp; stores</a> only when you need to record where an asset physically sits.
    </div>
</div>
