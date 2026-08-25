@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div><h1 class="text-2xl font-bold text-slate-900">Locations</h1><p class="mt-1 text-sm text-slate-500">Maintain buildings, floors, rooms, and personnel placement.</p></div>
        <a class="btn btn-primary" href="{{ route('locations.create') }}">Add location</a>
    </div>
    <form method="GET" class="card card-body flex gap-3"><input class="form-control" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search location, building, floor or sublocation"><button class="btn btn-secondary" type="submit">Search</button></form>
    <div class="card"><div class="card-body"><table class="table table-striped">
        <thead><tr><th>Name</th><th>Building</th><th>Floor</th><th>Sublocation</th><th>Officials</th><th>Actions</th></tr></thead>
        <tbody>@forelse($locations as $location)<tr>
            <td><a href="{{ route('locations.show', $location) }}">{{ $location->name }}</a></td><td>{{ $location->building ?? '—' }}</td><td>{{ $location->floor ?? '—' }}</td><td>{{ $location->sublocation ?? '—' }}</td><td>{{ $location->officials_count }}</td>
            <td class="whitespace-nowrap"><a href="{{ route('locations.show', $location) }}">View</a><span class="text-slate-300"> · </span><a href="{{ route('locations.edit', $location) }}">Edit</a></td>
        </tr>@empty<tr><td colspan="6" class="text-center text-slate-500">No locations found.</td></tr>@endforelse</tbody>
    </table></div>@if($locations->hasPages())<div class="card-footer">{{ $locations->links() }}</div>@endif</div>
</div>
@endsection
