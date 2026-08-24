@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-6">Edit Manufacturer</h1>
    <form action="{{ route('manufacturers.update', $manufacturer) }}" method="POST" class="bg-white p-6 rounded shadow max-w-2xl">
        @csrf
        @method('PUT')
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">Name</label>
            <input type="text" name="name" value="{{ old('name', $manufacturer->name) }}" class="border rounded w-full py-2 px-3" required>
        </div>
        <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-4 rounded">Update Manufacturer</button>
    </form>
</div>
@endsection
