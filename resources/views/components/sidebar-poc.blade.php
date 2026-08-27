<aside
    id="sidebar"
    class="fixed inset-y-0 left-0 z-40 flex w-64 shrink-0 flex-col border-r border-slate-800 bg-slate-900 text-slate-300 shadow-xl transition-transform duration-300 ease-in-out lg:static lg:inset-auto lg:translate-x-0"
    :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }"
>
    <div class="flex h-16 items-center justify-between border-b border-slate-800 bg-slate-950/70 px-5">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 text-white">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 shadow-md shadow-indigo-600/30">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </span>
            <span>
                <span class="block text-sm font-extrabold tracking-tight">Inventory POC</span>
                <span class="block text-[10px] font-medium uppercase tracking-widest text-slate-400">Asset &amp; stock</span>
            </span>
        </a>
        <button type="button" @click="sidebarOpen = false" class="rounded-md p-1 text-slate-400 hover:text-white lg:hidden" aria-label="Close sidebar">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    @php
        $pocLinks = [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => ['dashboard', 'home'], 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3'],
            ['label' => 'Assets', 'route' => 'assets.index', 'active' => ['assets.*'], 'icon' => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
            ['label' => 'Consumables & Stock', 'route' => 'consumables.index', 'active' => ['consumables.*', 'stock.*', 'entries.*'], 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
            ['label' => 'People', 'route' => 'officials.index', 'active' => ['officials.*'], 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
            ['label' => 'Offices & Locations', 'route' => 'organization.hierarchy', 'active' => ['organization.hierarchy', 'locations.*'], 'icon' => 'M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z'],
            ['label' => 'Inventory Report', 'route' => 'reports.inventory', 'active' => ['reports.*'], 'icon' => 'M9 17v-6m4 6V7m4 10v-3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
        ];
    @endphp

    <nav class="flex-1 overflow-y-auto px-4 py-5" aria-label="POC navigation">
        <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Core workflows</p>
        <ul class="space-y-1">
            @foreach ($pocLinks as $link)
                @php($isActive = collect($link['active'])->contains(fn ($pattern) => request()->routeIs($pattern)))
                <li>
                    <a href="{{ route($link['route']) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition-colors {{ $isActive ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="h-5 w-5 shrink-0 {{ $isActive ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $link['icon'] }}"/></svg>
                        <span>{{ $link['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>

        @if (auth()->user()?->isAdmin())
            <details class="mt-8 border-t border-slate-800 pt-4">
                <summary class="cursor-pointer select-none px-3 text-xs font-semibold text-slate-500 hover:text-slate-300">Advanced modules</summary>
                <div class="mt-2 space-y-1 pl-3 text-xs">
                    <a class="block rounded-lg px-3 py-2 hover:bg-slate-800 hover:text-white" href="{{ route('agreements.index') }}">Agreements &amp; payments</a>
                    <a class="block rounded-lg px-3 py-2 hover:bg-slate-800 hover:text-white" href="{{ route('manufacturers.index') }}">Manufacturers</a>
                    <a class="block rounded-lg px-3 py-2 hover:bg-slate-800 hover:text-white" href="{{ route('files.index') }}">File registry</a>
                    <a class="block rounded-lg px-3 py-2 hover:bg-slate-800 hover:text-white" href="{{ route('tasks.index') }}">Tasks</a>
                    <a class="block rounded-lg px-3 py-2 hover:bg-slate-800 hover:text-white" href="{{ route('developers.index') }}">Developer records</a>
                </div>
            </details>
        @endif
    </nav>

    <div class="border-t border-slate-800 p-4">
        <p class="px-2 text-xs text-slate-500">Focused proof-of-concept interface</p>
    </div>
</aside>
