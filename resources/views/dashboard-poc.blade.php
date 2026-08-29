<div class="space-y-7">
    <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-indigo-600">Inventory overview</p>
            <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-slate-900">Dashboard</h1>
            <p class="mt-2 text-sm text-slate-500">See what is available, what is assigned, and what needs attention.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if (auth()->user()?->canManageInventory())
                <a class="btn btn-primary" href="{{ route('assets.create') }}">Add asset</a>
            @endif
            @if (auth()->user()?->canPostStockEntries())
                <a class="btn btn-secondary" href="{{ route('consumables.index') }}">Receive or issue stock</a>
            @endif
        </div>
    </header>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Inventory summary">
        @foreach ([
            ['label' => 'Total assets', 'value' => $total_assets ?? 0, 'note' => 'All tracked equipment', 'color' => 'indigo'],
            ['label' => 'Available assets', 'value' => $assets_in_stock ?? 0, 'note' => 'Ready to assign', 'color' => 'emerald'],
            ['label' => 'Assigned assets', 'value' => $assets_in_use ?? 0, 'note' => 'Currently with people', 'color' => 'blue'],
            ['label' => 'Low stock items', 'value' => $low_stock_consumables ?? 0, 'note' => 'Require attention', 'color' => 'rose'],
        ] as $metric)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $metric['label'] }}</p>
                    <span class="h-2.5 w-2.5 rounded-full bg-{{ $metric['color'] }}-500"></span>
                </div>
                <p class="mt-3 text-3xl font-extrabold text-slate-900">{{ number_format($metric['value']) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $metric['note'] }}</p>
            </div>
        @endforeach
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4" aria-label="Quick actions">
        @if (auth()->user()?->canManageInventory())
            <a href="{{ route('assets.create') }}" class="group rounded-2xl border border-indigo-200 bg-indigo-50 p-5 transition hover:border-indigo-300 hover:bg-indigo-100">
                <p class="font-bold text-indigo-900">Add an asset</p>
                <p class="mt-1 text-xs text-indigo-700">Register equipment and optionally assign it.</p>
            </a>
        @endif
        <a href="{{ route('assets.index', ['status' => 'in_stock']) }}" class="group rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-indigo-200 hover:bg-indigo-50">
            <p class="font-bold text-slate-900">Assign an asset</p>
            <p class="mt-1 text-xs text-slate-500">Choose from equipment currently in stock.</p>
        </a>
        @if (auth()->user()?->canPostStockEntries())
            <a href="{{ route('consumables.index') }}" class="group rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-emerald-200 hover:bg-emerald-50">
                <p class="font-bold text-slate-900">Receive stock</p>
                <p class="mt-1 text-xs text-slate-500">Open an item and record the received quantity.</p>
            </a>
            <a href="{{ route('consumables.index') }}" class="group rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-blue-200 hover:bg-blue-50">
                <p class="font-bold text-slate-900">Issue stock</p>
                <p class="mt-1 text-xs text-slate-500">Issue consumables to a person.</p>
            </a>
        @endif
    </section>

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div><h2 class="font-bold text-slate-900">Recent assignments</h2><p class="text-xs text-slate-500">Latest asset custody changes</p></div>
                <a class="text-xs font-bold text-indigo-600 hover:text-indigo-800" href="{{ route('assets.index') }}">View assets</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse (($recent_assignments ?? collect()) as $assignment)
                    <a href="{{ route('assets.show', $assignment->asset) }}" class="flex items-center justify-between gap-4 px-5 py-4 hover:bg-slate-50">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-slate-900">{{ $assignment->asset?->name ?? 'Deleted asset' }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $assignment->official?->name ?? 'Unknown person' }} · {{ $assignment->asset?->asset_tag ?? 'No tag' }}</p>
                        </div>
                        <div class="text-right text-xs text-slate-500">
                            <p>{{ $assignment->assigned_at?->format('d M Y') ?? '—' }}</p>
                            <p class="mt-1 font-semibold {{ $assignment->returned_at ? 'text-slate-500' : 'text-blue-600' }}">{{ $assignment->returned_at ? 'Returned' : 'Assigned' }}</p>
                        </div>
                    </a>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-slate-500">No assignments recorded yet.</p>
                @endforelse
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div><h2 class="font-bold text-slate-900">Recent stock activity</h2><p class="text-xs text-slate-500">Latest received and issued quantities</p></div>
                <a class="text-xs font-bold text-indigo-600 hover:text-indigo-800" href="{{ route('stock.index') }}">View history</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse (($recent_stock_entries ?? collect()) as $entry)
                    @php($entryType = $entry->type instanceof \App\Enums\StockEntryType ? $entry->type : \App\Enums\StockEntryType::tryFrom($entry->type))
                    <a href="{{ $entry->consumable ? route('consumables.show', $entry->consumable) : route('stock.index') }}" class="flex items-center justify-between gap-4 px-5 py-4 hover:bg-slate-50">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-slate-900">{{ $entry->consumable?->name ?? 'Deleted item' }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $entry->recipient?->name ?? $entry->remarks ?? 'Inventory stock' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-extrabold {{ $entryType?->isInbound() ? 'text-emerald-600' : 'text-blue-600' }}">{{ $entryType?->isInbound() ? '+' : '-' }}{{ $entry->quantity }}</p>
                            <p class="text-xs text-slate-500">{{ $entryType?->label() ?? $entry->type }}</p>
                        </div>
                    </a>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-slate-500">No stock transactions recorded yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
