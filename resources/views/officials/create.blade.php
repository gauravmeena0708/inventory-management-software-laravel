@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <header><h1 class="text-2xl font-bold text-slate-900">Add person</h1><p class="mt-1 text-sm text-slate-500">Add someone who can receive assets or consumable stock.</p></header>
    <form action="{{ route('officials.store') }}" method="POST" class="card card-body space-y-6">
        @csrf
        @include('officials._form')
        <div class="flex gap-3"><button type="submit" class="btn btn-primary">Save person</button><a class="btn btn-secondary" href="{{ route('officials.index') }}">Cancel</a></div>
    </form>
</div>
@endsection
