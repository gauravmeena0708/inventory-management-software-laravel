@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="{ assignModal: false, returnModal: false, decommissionModal: false }">
    @php
        $status = $asset->status instanceof \App\Enums\AssetStatus ? $asset->status : \App\Enums\AssetStatus::tryFrom($asset->status);
        $type = $asset->asset_type instanceof \App\Enums\AssetType ? $asset->asset_type : \App\Enums\AssetType::tryFrom($asset->asset_type);
        $statusValue = $status ? $status->value : (string) $asset->status;
        $statusLabel = $status ? $status->label() : (string) $asset->status;
    @endphp

    <!-- Top Header Banner -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-start space-x-4">
            <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-mono text-xl font-bold shrink-0 border border-indigo-100">
                {{ substr($asset->asset_tag ?? $asset->name, 0, 2) }}
            </div>
            <div>
                <div class="flex items-center space-x-3">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $asset->name }}</h1>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold 
                        {{ $statusValue === 'in_use' ? 'bg-blue-50 text-blue-700 border border-blue-200' : '' }}
                        {{ $statusValue === 'in_stock' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : '' }}
                        {{ $statusValue === 'under_maintenance' ? 'bg-amber-50 text-amber-700 border border-amber-200' : '' }}
                        {{ $statusValue === 'decommissioned' ? 'bg-rose-50 text-rose-700 border border-rose-200' : '' }}
                    ">
                        <span class="w-1.5 h-1.5 rounded-full mr-1.5
                            {{ $statusValue === 'in_use' ? 'bg-blue-500' : '' }}
                            {{ $statusValue === 'in_stock' ? 'bg-emerald-500' : '' }}
                            {{ $statusValue === 'under_maintenance' ? 'bg-amber-500' : '' }}
                            {{ $statusValue === 'decommissioned' ? 'bg-rose-500' : '' }}
                        "></span>
                        {{ $statusLabel }}
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 mt-1 font-medium">
                    <span class="font-mono text-slate-700 font-semibold">Tag: {{ $asset->asset_tag ?? 'N/A' }}</span>
                    <span>&bull;</span>
                    <span>Type: {{ $type ? $type->label() : ($asset->asset_type ?? 'Hardware') }}</span>
                    <span>&bull;</span>
                    <span>Location: {{ $asset->location?->name ?? 'Unspecified' }}</span>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap items-center gap-2">
            @if (auth()->user()?->canAssignAssets())
                @if ($statusValue !== 'in_use' && $statusValue !== 'decommissioned')
                    <button 
                        type="button" 
                        @click="assignModal = true" 
                        class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-colors"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                        Assign Asset
                    </button>
                @elseif ($statusValue === 'in_use')
                    <button 
                        type="button" 
                        @click="returnModal = true" 
                        class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 shadow-xs transition-colors"
                    >
                        <svg class="w-4 h-4 mr-1.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                        Return to Stock
                    </button>
                @endif
            @endif

            @if (auth()->user()?->canManageInventory())
                <a 
                    href="{{ route('assets.edit', $asset) }}" 
                    class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 shadow-xs transition-colors"
                >
                    <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </a>

                @if ($statusValue !== 'decommissioned')
                    <button 
                        type="button" 
                        @click="decommissionModal = true" 
                        class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-xl text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-colors"
                    >
                        Decommission
                    </button>
                @endif
            @endif
        </div>
    </div>

    <!-- 3-Column Information Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Hardware & Specifications Card -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-900 border-b border-slate-100 pb-3 flex items-center space-x-2">
                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                </svg>
                <span>Hardware Specifications</span>
            </h2>
            <dl class="divide-y divide-slate-100 text-xs">
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-500 font-medium">Manufacturer</dt>
                    <dd class="text-slate-900 font-semibold">{{ $asset->manufacturer?->name ?? ($asset->manufacturer_name_legacy ?? '-') }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-500 font-medium">Model / Part</dt>
                    <dd class="text-slate-900 font-semibold">{{ $asset->model_number ?? ($asset->part_code ?? '-') }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-500 font-medium">Serial Number</dt>
                    <dd class="text-slate-900 font-mono font-semibold">{{ $asset->serial_number ?? '-' }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-500 font-medium">IP Address</dt>
                    <dd class="text-slate-900 font-mono font-semibold">{{ $asset->ip_address ?? '-' }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-500 font-medium">MAC Address</dt>
                    <dd class="text-slate-900 font-mono font-semibold">{{ $asset->mac_address ?? '-' }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-500 font-medium">Operating System</dt>
                    <dd class="text-slate-900 font-semibold">{{ $asset->operating_system ?? '-' }}</dd>
                </div>
            </dl>
            @if ($asset->description)
                <div class="pt-3 border-t border-slate-100">
                    <span class="text-xs font-semibold text-slate-500">Technical Details:</span>
                    <p class="text-xs text-slate-700 mt-1 whitespace-pre-line bg-slate-50 p-3 rounded-xl">{{ $asset->description }}</p>
                </div>
            @endif
        </div>

        <!-- Current Assignment & Custody Card -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-900 border-b border-slate-100 pb-3 flex items-center space-x-2">
                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span>Current Custody & Location</span>
            </h2>
            @if ($asset->assignedOfficial)
                <div class="p-4 rounded-xl bg-blue-50/70 border border-blue-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm">
                            {{ substr($asset->assignedOfficial->name, 0, 2) }}
                        </div>
                        <div>
                            <div class="text-sm font-bold text-slate-900">{{ $asset->assignedOfficial->name }}</div>
                            <div class="text-xs text-slate-500">{{ $asset->assignedOfficial->designation ?? 'Official' }} &bull; {{ $asset->assignedOfficial->department ?? 'General' }}</div>
                        </div>
                    </div>
                </div>
                <dl class="divide-y divide-slate-100 text-xs">
                    <div class="py-2.5 flex justify-between">
                        <dt class="text-slate-500 font-medium">Email</dt>
                        <dd class="text-slate-900">{{ $asset->assignedOfficial->email ?? '-' }}</dd>
                    </div>
                    <div class="py-2.5 flex justify-between">
                        <dt class="text-slate-500 font-medium">Phone</dt>
                        <dd class="text-slate-900">{{ $asset->assignedOfficial->phone ?? '-' }}</dd>
                    </div>
                    <div class="py-2.5 flex justify-between">
                        <dt class="text-slate-500 font-medium">Location</dt>
                        <dd class="text-slate-900 font-semibold">{{ $asset->location?->name ?? 'Headquarters' }}</dd>
                    </div>
                </dl>
            @else
                <div class="p-6 text-center rounded-xl bg-slate-50 border border-dashed border-slate-200">
                    <svg class="w-8 h-8 text-slate-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <p class="text-xs font-semibold text-slate-700">Currently in Inventory Stock</p>
                    <p class="text-[11px] text-slate-500 mt-0.5">Available for deployment to staff or infrastructure.</p>
                </div>
            @endif
        </div>

        <!-- Procurement & Warranties Card -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-900 border-b border-slate-100 pb-3 flex items-center space-x-2">
                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <span>Procurement & AMC</span>
            </h2>
            <dl class="divide-y divide-slate-100 text-xs">
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-500 font-medium">Purchase Date</dt>
                    <dd class="text-slate-900 font-semibold">{{ $asset->purchase_date?->format('d M Y') ?? '-' }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-500 font-medium">Purchase Cost</dt>
                    <dd class="text-slate-900 font-semibold">{{ $asset->currency ?? 'INR' }} {{ number_format($asset->purchase_cost ?? 0, 2) }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-500 font-medium">Warranty Expiry</dt>
                    <dd class="font-semibold {{ $asset->warranty_expiry && $asset->warranty_expiry < now() ? 'text-rose-600' : 'text-slate-900' }}">
                        {{ $asset->warranty_expiry?->format('d M Y') ?? '-' }}
                    </dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-500 font-medium">AMC End Date</dt>
                    <dd class="font-semibold {{ $asset->amc_end && $asset->amc_end < now() ? 'text-rose-600' : 'text-slate-900' }}">
                        {{ $asset->amc_end?->format('d M Y') ?? '-' }}
                    </dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-500 font-medium">Contract Ref</dt>
                    <dd class="text-slate-900 font-semibold">{{ $asset->contract_reference ?? '-' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Assignment History Timeline -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Assignment & Custody History</h2>
            <span class="text-xs text-slate-500">{{ $asset->assignments->count() }} records</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50/75">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Official</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Assigned Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Assigned By</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Returned Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Condition & Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-xs">
                    @forelse ($asset->assignments as $assignment)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-3.5 whitespace-nowrap font-semibold text-slate-900">
                                {{ $assignment->official?->name ?? 'Official #' . $assignment->official_id }}
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-slate-600">
                                {{ $assignment->assigned_at?->format('d M Y') ?? '-' }}
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-slate-600">
                                {{ $assignment->assignedBy?->name ?? 'System' }}
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                @if ($assignment->returned_at)
                                    <span class="text-slate-600">{{ $assignment->returned_at->format('d M Y') }}</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700">Active</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-slate-600">
                                {{ $assignment->remarks ?? ($assignment->condition_out ?? '-') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-400 text-xs">
                                No historical assignment records for this asset.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal 1: Assign Asset -->
    <div x-show="assignModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs" @click="assignModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200">
                <form action="{{ route('assets.assign', $asset) }}" method="POST">
                    @csrf
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h3 class="text-base font-bold text-slate-900">Assign Asset to Personnel</h3>
                            <button type="button" @click="assignModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Recipient Official <span class="text-rose-500">*</span></label>
                            <select name="official_id" required class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Select Official</option>
                                @foreach(\App\Models\Official::orderBy('name')->get() as $off)
                                    <option value="{{ $off->id }}">{{ $off->name }} ({{ $off->designation ?? 'Official' }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Assignment Date</label>
                            <input type="date" name="assigned_at" value="{{ date('Y-m-d') }}" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Condition at Handover</label>
                            <input type="text" name="condition_out" placeholder="e.g., Brand new, Excellent, Minor scuffs" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Remarks / Handover Notes</label>
                            <textarea name="remarks" rows="2" placeholder="Assignment purpose, workstation, ticket number..." class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 flex items-center justify-end space-x-3">
                        <button type="button" @click="assignModal = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 hover:bg-slate-200">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-xs font-bold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm">Confirm Assignment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal 2: Return Asset -->
    <div x-show="returnModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs" @click="returnModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200">
                <form action="{{ route('assets.return', $asset) }}" method="POST">
                    @csrf
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h3 class="text-base font-bold text-slate-900">Return Asset to Stock</h3>
                            <button type="button" @click="returnModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Return Date</label>
                            <input type="date" name="returned_at" value="{{ date('Y-m-d') }}" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Condition on Return</label>
                            <input type="text" name="condition_in" placeholder="e.g., Good working condition, Formatted" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Return Remarks</label>
                            <textarea name="remarks" rows="2" placeholder="Returned after resignation/project end..." class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 flex items-center justify-end space-x-3">
                        <button type="button" @click="returnModal = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 hover:bg-slate-200">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-xs font-bold rounded-xl text-white bg-amber-600 hover:bg-amber-700 shadow-sm">Confirm Return</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal 3: Decommission Asset -->
    <div x-show="decommissionModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs" @click="decommissionModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200">
                <form action="{{ route('assets.decommission', $asset) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h3 class="text-base font-bold text-rose-900">Decommission Asset</h3>
                            <button type="button" @click="decommissionModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Reason for Decommissioning <span class="text-rose-500">*</span></label>
                            <textarea name="reason" rows="3" required placeholder="End-of-life, hardware failure, unrepairable motherboard, written off..." class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500"></textarea>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 flex items-center justify-end space-x-3">
                        <button type="button" @click="decommissionModal = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 hover:bg-slate-200">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-xs font-bold rounded-xl text-white bg-rose-600 hover:bg-rose-700 shadow-sm">Decommission Asset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
