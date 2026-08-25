@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div><h1 class="text-2xl font-bold text-slate-900">Developers</h1><p class="mt-1 text-sm text-slate-500">Manage development personnel, reporting lines, categories, and status.</p></div>
        @can('create', \App\Models\Developer::class)<a class="btn btn-primary" href="{{ route('developers.create') }}">Add developer</a>@endcan
    </div>
    <form method="GET" class="card card-body grid gap-4 sm:grid-cols-[1fr_12rem_14rem_auto]">
        <input class="form-control" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search developer or remarks">
        <select class="form-control" name="status"><option value="">All statuses</option><option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option><option value="discontinued" @selected(($filters['status'] ?? '') === 'discontinued')>Discontinued</option></select>
        <select class="form-control" name="category_id"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') == $category->id)>{{ $category->name }}</option>@endforeach</select>
        <button class="btn btn-secondary" type="submit">Filter</button>
    </form>
    <div class="card"><div class="card-body"><table class="table table-striped">
        <thead><tr><th>Name</th><th>Reports to</th><th>Category</th><th>Status</th>@if($canViewSensitive)<th>Salary</th>@endif<th>Actions</th></tr></thead>
        <tbody>@forelse($developers as $developer)<tr>
            <td><a href="{{ route('developers.show', $developer) }}">{{ $developer->name }}</a></td>
            <td>{{ $developer->reportingUser?->name ?? 'Unassigned' }}</td><td>{{ $developer->category?->name ?? 'Uncategorized' }}</td>
            <td><span class="badge {{ $developer->status === 'active' ? 'bg-success' : 'badge-warning' }}">{{ ucfirst($developer->status ?? 'active') }}</span></td>
            @if($canViewSensitive)<td>{{ $developer->salary ?: '—' }}</td>@endif
            <td class="whitespace-nowrap"><a href="{{ route('developers.show', $developer) }}">View</a>@can('update', $developer)<span class="text-slate-300"> · </span><a href="{{ route('developers.edit', $developer) }}">Edit</a>@endcan</td>
        </tr>@empty<tr><td colspan="{{ $canViewSensitive ? 6 : 5 }}" class="text-center text-slate-500">No developers found.</td></tr>@endforelse</tbody>
    </table></div>@if($developers->hasPages())<div class="card-footer">{{ $developers->links() }}</div>@endif</div>
</div>
@endsection
