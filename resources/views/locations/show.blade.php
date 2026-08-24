@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-white p-6 rounded shadow">
        <h1 class="text-2xl font-bold mb-4">{{ $location->name }}</h1>
        <div><strong>Building:</strong> {{ $location->building ?? 'N/A' }}</div>
        <div><strong>Floor:</strong> {{ $location->floor ?? 'N/A' }}</div>
    </div>
</div>
@endsection
