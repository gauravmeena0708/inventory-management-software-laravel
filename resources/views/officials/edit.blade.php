@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <header><h1 class="text-2xl font-bold text-slate-900">Edit {{ $official->name }}</h1><p class="mt-1 text-sm text-slate-500">Update this person’s inventory directory details.</p></header>
    <form action="{{ route('officials.update', $official) }}" method="POST" class="card card-body space-y-6">
        @csrf
        @method('PUT')
        @include('officials._form')
        <div class="flex gap-3"><button type="submit" class="btn btn-primary">Update person</button><a class="btn btn-secondary" href="{{ route('officials.show', $official) }}">Cancel</a></div>
    </form>
</div>
@endsection
