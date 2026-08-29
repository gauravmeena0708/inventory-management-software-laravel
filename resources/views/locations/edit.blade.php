@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <header><h1 class="text-2xl font-bold text-slate-900">Edit {{ $location->name }}</h1><p class="mt-1 text-sm text-slate-600">Move this location only to an authorized compatible parent.</p></header>
    <form action="{{ route('locations.update', $location) }}" method="POST" class="card card-body space-y-6">
        @csrf
        @method('PUT')
        @include('locations._form')
        <div class="flex gap-3"><button type="submit" class="btn btn-primary">Update location</button><a class="btn btn-secondary" href="{{ route('locations.show', $location) }}">Cancel</a></div>
    </form>
</div>
@endsection
