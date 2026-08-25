@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <nav class="mb-4" aria-label="Breadcrumb">
        <ol class="flex flex-wrap items-center gap-2 text-sm text-slate-600">
            <li><a class="font-semibold text-indigo-700 hover:underline" href="{{ route('organization.hierarchy') }}">Hierarchy</a></li>
            @if ($location->site)<li aria-hidden="true">/</li><li>{{ $location->site->name }}</li>@endif
            @foreach (($breadcrumbs ?? collect()) as $breadcrumb)
                <li aria-hidden="true">/</li><li>@if ($breadcrumb->id === $location->id)<span aria-current="page">{{ $breadcrumb->name }}</span>@else<a class="font-semibold text-indigo-700 hover:underline" href="{{ route('locations.show', $breadcrumb) }}">{{ $breadcrumb->name }}</a>@endif</li>
            @endforeach
        </ol>
    </nav>
    <div class="bg-white p-6 rounded shadow">
        <div class="flex flex-wrap items-start justify-between gap-3"><h1 class="text-2xl font-bold mb-4">{{ $location->name }}</h1>@can('update', $location)<a class="btn btn-secondary" href="{{ route('locations.edit', $location) }}">Edit location</a>@endcan</div>
        <div><strong>Type:</strong> {{ str_replace('_', ' ', $location->location_type->value) }}</div>
        <div><strong>Site:</strong> {{ $location->site?->name ?? 'Unmapped' }}</div>
        <div><strong>Building:</strong> {{ $location->building ?? 'N/A' }}</div>
        <div><strong>Floor:</strong> {{ $location->floor ?? 'N/A' }}</div>
        <p class="mt-4 text-sm text-slate-600"><a class="font-semibold text-indigo-700 hover:underline" href="{{ route('organization.hierarchy', ['site_id' => $location->site_id]) }}">Browse this site as a structured tree or searchable list</a>. A map is not required for location operations.</p>
    </div>

    <section class="mt-6 rounded bg-white p-6 shadow" aria-labelledby="spatial-maps-heading">
        <div class="flex items-center justify-between gap-4"><div><h2 id="spatial-maps-heading" class="text-xl font-bold">Private spatial maps</h2><p class="mt-1 text-sm text-slate-600">Map files and precise coordinates require separate authorization.</p></div></div>
        @if (($spatialMaps ?? collect())->isEmpty())
            <p class="mt-4 text-sm text-slate-600">No current map is available. The structured location record remains usable.</p>
        @else
            <ul class="mt-4 divide-y divide-slate-200 rounded border border-slate-200">@foreach ($spatialMaps as $spatialMap)<li class="flex items-center justify-between gap-4 p-3"><span>{{ $spatialMap->map_type->value }} · version {{ $spatialMap->version }}</span><a class="font-semibold text-indigo-700 hover:underline" href="{{ route('spatial-maps.show', $spatialMap) }}">Open authorized map</a></li>@endforeach</ul>
        @endif

        @if ($canManageSpatialMaps ?? false)
            <form class="mt-6 grid gap-4 rounded border border-slate-200 bg-slate-50 p-4" method="POST" action="{{ route('locations.spatial-maps.store', $location) }}" enctype="multipart/form-data">
                @csrf
                <h3 class="font-semibold">Upload first map version</h3>
                <label class="text-sm font-medium">Map file<input class="mt-1 block w-full" type="file" name="file" required accept="image/png,image/jpeg,image/webp,image/svg+xml,.geojson,.json,.gltf,.glb"></label>
                <label class="text-sm font-medium">Map type<select class="form-control mt-1" name="map_type" required><option value="image">Raster image</option><option value="svg">SVG</option><option value="geojson">GeoJSON</option><option value="3d_model">3D model (storage only)</option></select></label>
                <div class="grid gap-4 md:grid-cols-3">
                    <label class="text-sm font-medium">Coordinate system<input class="form-control mt-1" name="coordinate_system" placeholder="LOCAL_METRES"></label>
                    <label class="text-sm font-medium">Units per pixel<input class="form-control mt-1" type="number" min="0" step="any" name="scale"></label>
                    <label class="text-sm font-medium">SHA-256 (optional verification)<input class="form-control mt-1" name="checksum" pattern="[A-Fa-f0-9]{64}"></label>
                </div>
                <button class="btn btn-primary w-fit" type="submit">Upload privately</button>
            </form>
        @endif
    </section>
</div>
@endsection
