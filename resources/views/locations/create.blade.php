@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <header><h1 class="text-2xl font-bold text-slate-900">Create structured location</h1><p class="mt-1 text-sm text-slate-600">Only sites and parents writable in the active organizational context are available.</p></header>
    <form action="{{ route('locations.store') }}" method="POST" class="card card-body space-y-6">
        @csrf
        @include('locations._form')
        <div class="flex gap-3"><button type="submit" class="btn btn-primary">Save location</button><a class="btn btn-secondary" href="{{ route('organization.hierarchy') }}">Cancel</a></div>
    </form>
</div>
@endsection
