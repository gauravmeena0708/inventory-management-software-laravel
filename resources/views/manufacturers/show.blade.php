@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-white p-6 rounded shadow">
        <h1 class="text-2xl font-bold mb-4">{{ $manufacturer->name }}</h1>
        <div><strong>Support Contact:</strong> {{ $manufacturer->support_contact ?? 'N/A' }}</div>
        <div><strong>Website:</strong> {{ $manufacturer->website ?? 'N/A' }}</div>
    </div>
</div>
@endsection
