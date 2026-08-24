@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-white p-6 rounded shadow">
        <h1 class="text-2xl font-bold mb-4">{{ $official->name }}</h1>
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
</div>
@endsection
