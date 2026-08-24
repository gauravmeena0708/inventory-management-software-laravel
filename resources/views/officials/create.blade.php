@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-6">Create Official</h1>
    <form action="{{ route('officials.store') }}" method="POST" class="bg-white p-6 rounded shadow max-w-2xl">
        @csrf
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">Name</label>
            <input type="text" name="name" class="border rounded w-full py-2 px-3" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">Designation</label>
            <input type="text" name="designation" class="border rounded w-full py-2 px-3">
        </div>
        <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-4 rounded">Save Official</button>
    </form>
</div>
@endsection
