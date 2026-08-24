@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-white p-6 rounded shadow">
        <h1 class="text-2xl font-bold mb-4">Stock Entry #{{ $entry->id }}</h1>
        <div class="grid grid-cols-2 gap-4">
            <div><strong>Consumable:</strong> {{ $entry->consumable?->name }}</div>
            <div><strong>Type:</strong> {{ $entry->type instanceof \App\Enums\StockEntryType ? $entry->type->label() : $entry->type }}</div>
            <div><strong>Quantity:</strong> {{ $entry->quantity }}</div>
            <div><strong>Balance After:</strong> {{ $entry->stock_after }}</div>
            <div><strong>Recipient:</strong> {{ $entry->recipient?->name ?? 'N/A' }}</div>
            <div><strong>Recorded By:</strong> {{ $entry->recorder?->name ?? 'System' }}</div>
        </div>
    </div>
</div>
@endsection
