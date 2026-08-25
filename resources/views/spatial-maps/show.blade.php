@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data='{ map: @json($payload) }'>
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-sm text-slate-600">{{ $map->location->name }}</p>
            <h1 class="text-2xl font-bold">{{ $map->map_type->value }} map · version {{ $map->version }}</h1>
            <p class="mt-1 text-sm text-slate-600">
                {{ $map->coordinate_system ?? 'Uncalibrated coordinate system' }}
                @if ($map->scale) · {{ $map->scale }} units per pixel @endif
            </p>
        </div>
        <a class="btn btn-secondary" href="{{ route('spatial-maps.download', $map) }}">Download</a>
    </header>

    @if (in_array($map->map_type->value, ['image', 'svg'], true))
        <section class="overflow-auto rounded border border-slate-200 bg-white p-4" aria-label="Calibrated floor map">
            <div class="relative inline-block max-w-full">
                <img class="block max-w-full" src="{{ route('spatial-maps.content', $map) }}" alt="Authorized map of {{ $map->location->name }}">
                <template x-for="marker in map.assets.filter(item => item.pixel)" :key="marker.id">
                    <button
                        type="button"
                        class="absolute -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white bg-indigo-600 px-2 py-1 text-xs font-bold text-white shadow"
                        :style="`left:${marker.pixel.x}px; top:${marker.pixel.y}px`"
                        :title="`${marker.asset_tag}: ${marker.name}`"
                        x-text="marker.asset_tag"
                    ></button>
                </template>
            </div>
        </section>
    @elseif ($map->map_type->value === '3d_model')
        <div class="rounded border border-amber-200 bg-amber-50 p-4 text-amber-900">
            This 3D model is stored and versioned for future use. A 3D viewer is intentionally not included.
        </div>
    @else
        <div class="rounded border border-slate-200 bg-white p-4 text-slate-700">
            GeoJSON is stored privately. Use the structured hierarchy below or download the authorized source.
        </div>
    @endif

    <section class="rounded border border-slate-200 bg-white p-5" aria-labelledby="map-locations-heading">
        <h2 id="map-locations-heading" class="text-lg font-bold">Location hierarchy</h2>
        <div class="mt-3 overflow-x-auto">
            <table class="table">
                <thead><tr><th>Name</th><th>Type</th><th>Parent</th><th>Local X/Y/Z</th></tr></thead>
                <tbody>
                    <template x-for="location in map.locations" :key="location.id">
                        <tr>
                            <td x-text="location.name"></td>
                            <td x-text="location.type"></td>
                            <td x-text="location.parent_id ?? '—'"></td>
                            <td x-text="[location.coordinates.x, location.coordinates.y, location.coordinates.z].map(value => value ?? '—').join(' / ')"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </section>

    <section class="rounded border border-slate-200 bg-white p-5" aria-labelledby="map-assets-heading">
        <h2 id="map-assets-heading" class="text-lg font-bold">Visible asset markers</h2>
        <p class="mt-1 text-sm text-slate-600">Relocations submitted from a map are processed only by the asset-placement service and its policies.</p>
        <div class="mt-3 overflow-x-auto">
            <table class="table">
                <thead><tr><th>Asset tag</th><th>Name</th><th>Location</th><th>Local X/Y/Z</th></tr></thead>
                <tbody>
                    <template x-for="asset in map.assets" :key="asset.id">
                        <tr>
                            <td x-text="asset.asset_tag"></td>
                            <td x-text="asset.name"></td>
                            <td x-text="asset.location_id"></td>
                            <td x-text="[asset.coordinates.x, asset.coordinates.y, asset.coordinates.z].map(value => value ?? '—').join(' / ')"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </section>

    @can('update', $map)
        <section class="rounded border border-slate-200 bg-white p-5">
            <h2 class="text-lg font-bold">Supersede this version</h2>
            <form class="mt-3 grid gap-3" method="POST" action="{{ route('spatial-maps.supersede', $map) }}" enctype="multipart/form-data">
                @csrf
                <input type="file" name="file" required>
                <div class="grid gap-3 md:grid-cols-2">
                    <input class="form-control" name="coordinate_system" value="{{ $map->coordinate_system }}" placeholder="Coordinate system">
                    <input class="form-control" type="number" min="0" step="any" name="scale" value="{{ $map->scale }}" placeholder="Units per pixel">
                </div>
                <button class="btn btn-primary w-fit" type="submit">Upload next version</button>
            </form>

            <form class="mt-4" method="POST" action="{{ route('spatial-maps.destroy', $map) }}">
                @csrf
                @method('DELETE')
                <button class="btn border-rose-300 bg-white text-rose-700" type="submit">Delete this map</button>
            </form>
        </section>
    @endcan
</div>
@endsection
