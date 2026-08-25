@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><h1 class="text-2xl font-bold text-slate-900">File registry</h1><p class="mt-1 text-sm text-slate-500">Track electronic and physical file references.</p></div><a class="btn btn-primary" href="{{ route('files.create') }}">Add file</a></div>
    <form method="GET" class="card card-body grid gap-4 sm:grid-cols-[1fr_14rem_auto]"><input class="form-control" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name, subject or file number"><input class="form-control" name="division" value="{{ $filters['division'] ?? '' }}" placeholder="Division"><button class="btn btn-secondary" type="submit">Filter</button></form>
    <div class="card"><div class="card-body"><table class="table table-striped">
        <thead><tr><th>Name</th><th>E-file</th><th>Physical file</th><th>Subject</th><th>Division</th><th>Attachments</th><th>Actions</th></tr></thead>
        <tbody>@forelse($files as $file)<tr><td><a href="{{ route('files.show', $file) }}">{{ $file->name }}</a></td><td>{{ $file->efile_number ?? '—' }}</td><td>{{ $file->physical_number ?? $file->physical_name ?? '—' }}</td><td>{{ $file->subject ?? '—' }}</td><td>{{ $file->division ?? '—' }}</td><td>{{ $file->attachments->count() }}</td><td class="whitespace-nowrap"><a href="{{ route('files.show', $file) }}">View</a><span class="text-slate-300"> · </span><a href="{{ route('files.edit', $file) }}">Edit</a></td></tr>@empty<tr><td colspan="7" class="text-center text-slate-500">No file records found.</td></tr>@endforelse</tbody>
    </table></div>@if($files->hasPages())<div class="card-footer">{{ $files->links() }}</div>@endif</div>
</div>
@endsection
