@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div><h1 class="text-2xl font-bold text-slate-900">Locations</h1><p class="mt-1 text-sm text-slate-500">Maintain buildings, floors, rooms, racks, and personnel placement.</p></div>
        <div class="flex gap-2"><a class="btn btn-secondary" href="{{ route('organization.hierarchy') }}">Hierarchy tree</a>@can('create', \App\Models\Location::class)<a class="btn btn-primary" href="{{ route('locations.create') }}">Add location</a>@endcan</div>
    </div>
    <form method="GET" class="card card-body grid gap-3 md:grid-cols-[1fr_1fr_auto]" role="search" aria-label="Search locations">
        <label><span class="text-sm font-semibold text-slate-700">Search</span><input class="form-control mt-1" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Location, building, floor or sublocation"></label>
        <label><span class="text-sm font-semibold text-slate-700">Authorized site</span><select class="form-control mt-1" name="site_id"><option value="">All authorized sites</option>@foreach($sites as $site)<option value="{{ $site->id }}" @selected(($filters['site_id'] ?? '') == $site->id)>{{ $site->name }}</option>@endforeach</select></label>
        <button class="btn btn-secondary self-end" type="submit">Search</button>
    </form>
    <div class="card"><div class="card-body"><table class="table table-striped">
        <thead><tr><th>Name</th><th>Building</th><th>Floor</th><th>Sublocation</th><th>Officials</th><th>Actions</th></tr></thead>
        <tbody>@forelse($locations as $location)<tr>
            <td><a href="{{ route('locations.show', $location) }}">{{ $location->name }}</a></td><td>{{ $location->building ?? '—' }}</td><td>{{ $location->floor ?? '—' }}</td><td>{{ $location->sublocation ?? '—' }}</td><td>{{ $location->officials_count }}</td>
            <td class="whitespace-nowrap"><a href="{{ route('locations.show', $location) }}">View</a>@can('update', $location)<span class="text-slate-300"> · </span><a href="{{ route('locations.edit', $location) }}">Edit</a>@endcan</td>
        </tr>@empty<tr><td colspan="6" class="text-center text-slate-500">No authorized locations found.</td></tr>@endforelse</tbody>
    </table></div>@if($locations->hasPages())<div class="card-footer">{{ $locations->links() }}</div>@endif</div>
</div>
@endsection
