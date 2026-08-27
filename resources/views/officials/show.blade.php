@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-5 flex items-center justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Person</p><h1 class="mt-1 text-2xl font-bold">{{ $official->name }}</h1></div>@can('update', $official)<a class="btn btn-primary" href="{{ route('officials.edit', $official) }}">Edit person</a>@endcan</div>
        <div class="grid grid-cols-2 gap-4">
            <div><strong>Designation:</strong> {{ $official->designation ?? 'N/A' }}</div>
            <div><strong>Department:</strong> {{ $official->department ?? 'N/A' }}</div>
            <div><strong>Location:</strong> {{ $official->location?->name ?? 'N/A' }}</div>
            @if(!empty($canViewSensitive))
            <div><strong>Email:</strong> {{ $official->email ?? 'N/A' }}</div>
            <div><strong>Phone:</strong> {{ $official->phone ?? 'N/A' }}</div>
            @endif
        </div>
    </div>
    <div class="card">
        <div class="card-header">Assigned assets</div>
        <div class="card-body">
            <table class="table"><thead><tr><th>Asset</th><th>Tag</th><th>Status</th><th>Location</th></tr></thead><tbody>
            @forelse ($official->assets as $personAsset)
                <tr><td><a href="{{ route('assets.show', $personAsset) }}">{{ $personAsset->name }}</a></td><td>{{ $personAsset->asset_tag ?? '—' }}</td><td>{{ $personAsset->status instanceof \App\Enums\AssetStatus ? $personAsset->status->label() : str($personAsset->status)->replace('_', ' ')->title() }}</td><td>{{ $personAsset->location?->name ?? '—' }}</td></tr>
            @empty
                <tr><td colspan="4" class="text-center text-slate-500">No assets are currently assigned.</td></tr>
            @endforelse
            </tbody></table>
        </div>
    </div>
</div>
@endsection
