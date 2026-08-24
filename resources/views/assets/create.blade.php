@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Add New IT Asset</h1>
            <p class="text-sm text-slate-500 mt-1">Register a new physical hardware asset into the organizational registry.</p>
        </div>
        <a href="{{ route('assets.index') }}" class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 shadow-xs transition-colors">
            &larr; Back to Assets
        </a>
    </div>

    <!-- Create Form -->
    <form action="{{ route('assets.store') }}" method="POST" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs divide-y divide-slate-100" x-data="{ assetType: '{{ old('asset_type', 'laptop') }}' }">
        @csrf

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
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g., Dell Latitude 7420 Developer Machine" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('name')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Asset Type -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Asset Type <span class="text-rose-500">*</span></label>
                    <select name="asset_type" x-model="assetType" required class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach(\App\Enums\AssetType::cases() as $typeCase)
                            <option value="{{ $typeCase->value }}" {{ old('asset_type', 'laptop') === $typeCase->value ? 'selected' : '' }}>
                                {{ $typeCase->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('asset_type')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Asset Tag -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Barcode / Asset Tag</label>
                    <input type="text" name="asset_tag" value="{{ old('asset_tag') }}" placeholder="e.g., NDC-LAP-2026-001" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                    @error('asset_tag')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Serial Number -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Hardware Serial Number</label>
                    <input type="text" name="serial_number" value="{{ old('serial_number') }}" placeholder="e.g., 5CG9241XYZ" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                    @error('serial_number')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Model Number -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Model Number / Part Code</label>
                    <input type="text" name="model_number" value="{{ old('model_number') }}" placeholder="e.g., Latitude 7420" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('model_number')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Manufacturer -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Manufacturer / OEM</label>
                    <select name="manufacturer_id" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Select Manufacturer</option>
                        @foreach($manufacturers as $mfg)
                            <option value="{{ $mfg->id }}" {{ old('manufacturer_id') == $mfg->id ? 'selected' : '' }}>
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
                            <option value="{{ $loc->id }}" {{ old('location_id') == $loc->id ? 'selected' : '' }}>
                                {{ $loc->name }} {{ $loc->building ? "({$loc->building})" : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('location_id')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Initial Assignee -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Initial Assignee (Optional)</label>
                    <select name="assigned_official_id" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Unassigned (In Stock)</option>
                        @foreach($officials as $official)
                            <option value="{{ $official->id }}" {{ old('assigned_official_id') == $official->id ? 'selected' : '' }}>
                                {{ $official->name }} ({{ $official->designation ?? 'Official' }})
                            </option>
                        @endforeach
                    </select>
                    @error('assigned_official_id')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Initial Status</label>
                    <select name="status" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach(\App\Enums\AssetStatus::cases() as $statusCase)
                            <option value="{{ $statusCase->value }}" {{ old('status', 'in_stock') === $statusCase->value ? 'selected' : '' }}>
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
                    <input type="text" name="ip_address" value="{{ old('ip_address') }}" placeholder="192.168.1.100" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">MAC Address</label>
                    <input type="text" name="mac_address" value="{{ old('mac_address') }}" placeholder="00:1A:2B:3C:4D:5E" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Operating System</label>
                    <input type="text" name="operating_system" value="{{ old('operating_system') }}" placeholder="Ubuntu 24.04 / Windows 11" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Hardware Description & Details</label>
                    <textarea name="description" rows="3" placeholder="Processor (CPU), RAM, NVMe Storage specs, display ports, switch capacity..." class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('description') }}</textarea>
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
                    <input type="date" name="purchase_date" value="{{ old('purchase_date') }}" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Purchase Cost</label>
                    <div class="flex">
                        <span class="inline-flex items-center px-3 text-xs font-semibold text-slate-500 bg-slate-100 border border-r-0 border-slate-200 rounded-l-xl">INR</span>
                        <input type="number" step="0.01" name="purchase_cost" value="{{ old('purchase_cost') }}" placeholder="0.00" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-r-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Warranty Expiry</label>
                    <input type="date" name="warranty_expiry" value="{{ old('warranty_expiry') }}" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">AMC End Date</label>
                    <input type="date" name="amc_end" value="{{ old('amc_end') }}" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">End of Support</label>
                    <input type="date" name="end_of_support" value="{{ old('end_of_support') }}" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Contract Reference</label>
                    <input type="text" name="contract_reference" value="{{ old('contract_reference') }}" placeholder="e.g., GEM/2026/PO/4491" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="px-6 py-4 bg-slate-50/70 flex items-center justify-end space-x-3 rounded-b-2xl">
            <a href="{{ route('assets.index') }}" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 hover:bg-slate-200 transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-5 py-2.5 text-xs font-bold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-colors">
                Save & Register Asset
            </button>
        </div>
    </form>
</div>
@endsection
