@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Edit Asset: {{ $asset->name }}</h1>
            <p class="text-sm text-slate-500 mt-1">Update hardware configuration, deployment location, and procurement metadata.</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('assets.show', $asset) }}" class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 shadow-xs transition-colors">
                &larr; View Asset
            </a>
        </div>
    </div>

    <!-- Edit Form -->
    <form action="{{ route('assets.update', $asset) }}" method="POST" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs divide-y divide-slate-100" x-data="{ assetType: '{{ old('asset_type', $asset->asset_type instanceof \App\Enums\AssetType ? $asset->asset_type->value : $asset->asset_type) }}' }">
        @csrf
        @method('PUT')

        <!-- Core Details -->
        <div class="p-6 space-y-6">
            <h2 class="text-base font-bold text-slate-900 flex items-center space-x-2">
                <span class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold">1</span>
                <span>Core Identification</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Asset Name -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Asset Name / Title <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $asset->name) }}" required class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('name')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Asset Type -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Asset Type <span class="text-rose-500">*</span></label>
                    @php
                        $curType = $asset->asset_type instanceof \App\Enums\AssetType ? $asset->asset_type->value : $asset->asset_type;
                    @endphp
                    <select name="asset_type" x-model="assetType" required class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach(\App\Enums\AssetType::cases() as $typeCase)
                            <option value="{{ $typeCase->value }}" {{ old('asset_type', $curType) === $typeCase->value ? 'selected' : '' }}>
                                {{ $typeCase->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('asset_type')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Asset Tag -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Barcode / Asset Tag</label>
                    <input type="text" name="asset_tag" value="{{ old('asset_tag', $asset->asset_tag) }}" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                    @error('asset_tag')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Serial Number -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Hardware Serial Number</label>
                    <input type="text" name="serial_number" value="{{ old('serial_number', $asset->serial_number) }}" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                    @error('serial_number')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Model Number -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Model Number / Part Code</label>
                    <input type="text" name="model_number" value="{{ old('model_number', $asset->model_number) }}" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('model_number')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Manufacturer -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Manufacturer / OEM</label>
                    <select name="manufacturer_id" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Select Manufacturer</option>
                        @foreach($manufacturers as $mfg)
                            <option value="{{ $mfg->id }}" {{ old('manufacturer_id', $asset->manufacturer_id) == $mfg->id ? 'selected' : '' }}>
                                {{ $mfg->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('manufacturer_id')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Location -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Deployment Location</label>
                    <select name="location_id" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Select Location</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ old('location_id', $asset->location_id) == $loc->id ? 'selected' : '' }}>
                                {{ $loc->name }} {{ $loc->building ? "({$loc->building})" : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('location_id')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Lifecycle Status</label>
                    @php
                        $curStatus = $asset->status instanceof \App\Enums\AssetStatus ? $asset->status->value : $asset->status;
                    @endphp
                    <select name="status" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach(\App\Enums\AssetStatus::cases() as $statusCase)
                            <option value="{{ $statusCase->value }}" {{ old('status', $curStatus) === $statusCase->value ? 'selected' : '' }}>
                                {{ $statusCase->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <!-- Technical & Network Specifications -->
        <div class="p-6 space-y-6">
            <h2 class="text-base font-bold text-slate-900 flex items-center space-x-2">
                <span class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold">2</span>
                <span>Networking & System Specs</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">IP Address</label>
                    <input type="text" name="ip_address" value="{{ old('ip_address', $asset->ip_address) }}" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">MAC Address</label>
                    <input type="text" name="mac_address" value="{{ old('mac_address', $asset->mac_address) }}" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Operating System</label>
                    <input type="text" name="operating_system" value="{{ old('operating_system', $asset->operating_system) }}" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Hardware Description & Specifications</label>
                    <textarea name="description" rows="3" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('description', $asset->description) }}</textarea>
                </div>
            </div>
        </div>

        <!-- Procurement & Warranty Lifecycle -->
        <div class="p-6 space-y-6">
            <h2 class="text-base font-bold text-slate-900 flex items-center space-x-2">
                <span class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold">3</span>
                <span>Procurement & Lifecycle Management</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Purchase Date</label>
                    <input type="date" name="purchase_date" value="{{ old('purchase_date', $asset->purchase_date?->format('Y-m-d')) }}" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Purchase Cost</label>
                    <div class="flex">
                        <span class="inline-flex items-center px-3 text-xs font-semibold text-slate-500 bg-slate-100 border border-r-0 border-slate-200 rounded-l-xl">{{ $asset->currency ?? 'INR' }}</span>
                        <input type="number" step="0.01" name="purchase_cost" value="{{ old('purchase_cost', $asset->purchase_cost) }}" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-r-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Warranty Expiry</label>
                    <input type="date" name="warranty_expiry" value="{{ old('warranty_expiry', $asset->warranty_expiry?->format('Y-m-d')) }}" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">AMC End Date</label>
                    <input type="date" name="amc_end" value="{{ old('amc_end', $asset->amc_end?->format('Y-m-d')) }}" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">End of Support</label>
                    <input type="date" name="end_of_support" value="{{ old('end_of_support', $asset->end_of_support?->format('Y-m-d')) }}" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Contract Reference</label>
                    <input type="text" name="contract_reference" value="{{ old('contract_reference', $asset->contract_reference) }}" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="px-6 py-4 bg-slate-50/70 flex items-center justify-end space-x-3 rounded-b-2xl">
            <a href="{{ route('assets.show', $asset) }}" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 hover:bg-slate-200 transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-5 py-2.5 text-xs font-bold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-colors">
                Save Changes
            </button>
        </div>
    </form>
</div>
@endsection
