<aside 
    id="sidebar"
    class="fixed inset-y-0 left-0 z-40 w-64 bg-slate-900 text-slate-300 flex flex-col transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-auto shrink-0 shadow-xl border-r border-slate-800"
    :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }"
>
    <!-- Brand / Header -->
    <div class="h-16 flex items-center justify-between px-6 bg-slate-950/70 border-b border-slate-800">
        <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 text-white font-bold text-lg tracking-tight">
            <div class="w-9 h-9 rounded-lg bg-indigo-600 flex items-center justify-center text-white shadow-md shadow-indigo-600/30">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <span class="truncate">{{ config('app.name', 'NDC Inventory') }}</span>
        </a>
        <button 
            type="button" 
            @click="sidebarOpen = false" 
            class="lg:hidden text-slate-400 hover:text-white p-1 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
            aria-label="Close sidebar"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <!-- Navigation List -->
    <div class="flex-1 overflow-y-auto px-4 py-4 space-y-6 scrollbar-thin scrollbar-thumb-slate-700">
        <!-- Main Section -->
        <div>
            <div class="px-3 mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">Core</div>
            <ul class="space-y-1">
                <li>
                    <a 
                        href="{{ route('dashboard') }}" 
                        class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('dashboard') || request()->routeIs('home') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- IT Assets Section -->
        <div x-data="{ open: {{ request()->routeIs('assets.*') || request()->is('assets*') || request()->is('desktops*') || request()->is('laptops*') || request()->is('servers*') || request()->is('switches*') || request()->is('storages*') ? 'true' : 'false' }} }">
            <div class="flex items-center justify-between px-3 mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400 cursor-pointer select-none" @click="open = !open">
                <span>IT Assets</span>
                <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-90': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
            <ul class="space-y-1" x-show="open" x-collapse>
                <li>
                    <a 
                        href="{{ route('assets.index') }}" 
                        class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('assets.index') && !request()->filled('type') ? 'bg-indigo-600/90 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <svg class="w-5 h-5 flex-shrink-0 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span>All Assets</span>
                    </a>
                </li>
                <li>
                    <a 
                        href="{{ route('assets.index', ['type' => 'desktop']) }}" 
                        class="flex items-center space-x-3 px-3 py-1.5 pl-9 rounded-lg text-xs font-medium transition-colors {{ request()->input('type') === 'desktop' ? 'text-indigo-400 font-semibold bg-slate-800/60' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}"
                    >
                        <span>Desktops</span>
                    </a>
                </li>
                <li>
                    <a 
                        href="{{ route('assets.index', ['type' => 'laptop']) }}" 
                        class="flex items-center space-x-3 px-3 py-1.5 pl-9 rounded-lg text-xs font-medium transition-colors {{ request()->input('type') === 'laptop' ? 'text-indigo-400 font-semibold bg-slate-800/60' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}"
                    >
                        <span>Laptops</span>
                    </a>
                </li>
                <li>
                    <a 
                        href="{{ route('assets.index', ['type' => 'server']) }}" 
                        class="flex items-center space-x-3 px-3 py-1.5 pl-9 rounded-lg text-xs font-medium transition-colors {{ request()->input('type') === 'server' ? 'text-indigo-400 font-semibold bg-slate-800/60' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}"
                    >
                        <span>Servers</span>
                    </a>
                </li>
                <li>
                    <a 
                        href="{{ route('assets.index', ['type' => 'switch']) }}" 
                        class="flex items-center space-x-3 px-3 py-1.5 pl-9 rounded-lg text-xs font-medium transition-colors {{ request()->input('type') === 'switch' ? 'text-indigo-400 font-semibold bg-slate-800/60' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}"
                    >
                        <span>Switches</span>
                    </a>
                </li>
                <li>
                    <a 
                        href="{{ route('assets.index', ['type' => 'storage']) }}" 
                        class="flex items-center space-x-3 px-3 py-1.5 pl-9 rounded-lg text-xs font-medium transition-colors {{ request()->input('type') === 'storage' ? 'text-indigo-400 font-semibold bg-slate-800/60' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}"
                    >
                        <span>Storage Units</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Consumables & Stock Section -->
        <div>
            <div class="px-3 mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">Inventory & Stock</div>
            <ul class="space-y-1">
                <li>
                    <a 
                        href="{{ route('consumables.index') }}" 
                        class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('consumables.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <svg class="w-5 h-5 flex-shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        <span>Consumables</span>
                    </a>
                </li>
                <li>
                    <a 
                        href="{{ route('stock.index') }}" 
                        class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('stock.*') || request()->routeIs('entries.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <svg class="w-5 h-5 flex-shrink-0 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        <span>Stock Ledger</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Agreements & Payments Section -->
        <div>
            <div class="px-3 mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">Contracts & Finance</div>
            <ul class="space-y-1">
                <li>
                    <a 
                        href="{{ route('agreements.index') }}" 
                        class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('agreements.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <svg class="w-5 h-5 flex-shrink-0 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Agreements & AMC</span>
                    </a>
                </li>
                <li>
                    <a 
                        href="{{ route('payments.index') }}" 
                        class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('payments.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <svg class="w-5 h-5 flex-shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Payment Schedules</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Personnel Section -->
        <div>
            <div class="px-3 mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">Personnel</div>
            <ul class="space-y-1">
                <li>
                    <a 
                        href="{{ route('officials.index') }}" 
                        class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('officials.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <svg class="w-5 h-5 flex-shrink-0 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span>Officials</span>
                    </a>
                </li>
                <li>
                    <a 
                        href="{{ route('developers.index') }}" 
                        class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('developers.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <svg class="w-5 h-5 flex-shrink-0 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                        <span>Developers</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Master Data Section -->
        <div>
            <div class="px-3 mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">Master Data</div>
            <ul class="space-y-1">
                <li>
                    <a 
                        href="{{ route('locations.index') }}" 
                        class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('locations.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <svg class="w-5 h-5 flex-shrink-0 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Locations</span>
                    </a>
                </li>
                <li>
                    <a 
                        href="{{ route('manufacturers.index') }}" 
                        class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('manufacturers.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <svg class="w-5 h-5 flex-shrink-0 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <span>Manufacturers</span>
                    </a>
                </li>
                <li>
                    <a 
                        href="{{ route('files.index') }}" 
                        class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('files.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <svg class="w-5 h-5 flex-shrink-0 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                        <span>File Registry</span>
                    </a>
                </li>
                <li>
                    <a 
                        href="{{ route('tasks.index') }}" 
                        class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('tasks.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <svg class="w-5 h-5 flex-shrink-0 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        <span>Tasks</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- User Mini Profile / Footer in sidebar -->
    <div class="p-4 bg-slate-950/80 border-t border-slate-800">
        <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-full bg-slate-800 text-slate-200 flex items-center justify-center font-bold text-sm border border-slate-700">
                {{ auth()->check() ? strtoupper(substr(auth()->user()->name, 0, 2)) : 'ND' }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-white truncate">{{ auth()->user()?->name ?? 'Guest User' }}</p>
                <p class="text-xs text-slate-400 truncate">{{ auth()->user()?->email ?? '' }}</p>
            </div>
        </div>
    </div>
</aside>
