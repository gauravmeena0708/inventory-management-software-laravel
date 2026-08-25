@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Officials</h1>
            <p class="mt-1 text-sm text-slate-500">Manage personnel, departments, locations, and assigned assets.</p>
        </div>
        @can('create', \App\Models\Official::class)
            <a class="btn btn-primary" href="{{ route('officials.create') }}">Add official</a>
        @endcan
    </div>

    <form method="GET" class="card card-body grid gap-4 sm:grid-cols-[1fr_14rem_auto]">
        <input class="form-control" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name, designation, department or email">
        <select class="form-control" name="location_id">
            <option value="">All locations</option>
            @foreach($locations as $location)
                <option value="{{ $location->id }}" @selected(($filters['location_id'] ?? '') == $location->id)>{{ $location->name }}</option>
            @endforeach
        </select>
        <button class="btn btn-secondary" type="submit">Filter</button>
    </form>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead><tr><th>Name</th><th>Designation</th><th>Department</th><th>Location</th><th>Assets</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($officials as $official)
                    <tr>
                        <td><a href="{{ route('officials.show', $official) }}">{{ $official->title ? $official->title.' ' : '' }}{{ $official->name }}</a></td>
                        <td>{{ $official->designation ?? '—' }}</td>
                        <td>{{ $official->department ?? '—' }}</td>
                        <td>{{ $official->location?->name ?? 'Unassigned' }}</td>
                        <td>{{ $official->assets_count }}</td>
                        <td class="whitespace-nowrap">
                            <a href="{{ route('officials.show', $official) }}">View</a>
                            @can('update', $official)<span class="text-slate-300"> · </span><a href="{{ route('officials.edit', $official) }}">Edit</a>@endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-slate-500">No officials found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($officials->hasPages())<div class="card-footer">{{ $officials->links() }}</div>@endif
    </div>
</div>
@endsection
