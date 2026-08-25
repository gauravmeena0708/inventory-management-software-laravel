<header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Left Side: Mobile Menu Button & Global Search -->
            <div class="flex items-center space-x-4 flex-1 max-w-2xl">
                <button 
                    type="button" 
                    @click="sidebarOpen = !sidebarOpen" 
                    class="lg:hidden p-2 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    aria-label="Open sidebar"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <!-- Global Search Form -->
                <form action="{{ route('assets.index') }}" method="GET" class="w-full max-w-md">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ request('search') }}"
                            placeholder="Search assets by tag, serial, model, or name..." 
                            class="w-full pl-10 pr-4 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                        >
                    </div>
                </form>
            </div>

            <!-- Right Side: Organization context, User Info & Logout -->
            <div class="flex items-center space-x-3 sm:space-x-4">
                @auth
                    @inject('organizationalNavigation', 'App\Services\Organization\OrganizationalNavigation')
                    @inject('organizationalContext', 'App\Services\Organization\OrganizationalContext')
                    @php
                        $contextOptions = $organizationalNavigation->contexts(auth()->user());
                        $activeOrganizationalContext = $organizationalContext->getActiveContext(auth()->user());
                    @endphp
                    @if ($contextOptions->count() > 1)
                        <form method="POST" action="{{ route('organizational-context.update') }}" class="hidden md:block" data-testid="organizational-context-switcher">
                            @csrf
                            <label class="sr-only" for="organizational-context">Active organizational context</label>
                            <select
                                id="organizational-context"
                                name="organizational_unit_id"
                                class="max-w-56 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                onchange="this.form.submit()"
                            >
                                <option value="" disabled @selected(! $activeOrganizationalContext)>Select organizational context</option>
                                @foreach ($contextOptions as $contextOption)
                                    <option value="{{ $contextOption->id }}" @selected($activeOrganizationalContext?->id === $contextOption->id)>{{ $contextOption->name }}</option>
                                @endforeach
                            </select>
                            <noscript><button class="btn btn-secondary ml-1" type="submit">Switch</button></noscript>
                        </form>
                    @elseif ($contextOptions->count() === 1)
                        <a href="{{ route('organization.hierarchy') }}" class="hidden max-w-48 truncate text-xs font-semibold text-slate-600 hover:text-indigo-700 md:block" title="Active organizational context">
                            {{ $activeOrganizationalContext?->name ?? $contextOptions->first()->name }}
                        </a>
                    @endif
                    <!-- User Profile Dropdown / Card -->
                    <div class="relative" x-data="{ dropdownOpen: false }">
                        <button 
                            @click="dropdownOpen = !dropdownOpen" 
                            type="button" 
                            class="flex items-center space-x-3 p-1.5 rounded-xl hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                        >
                            <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                            </div>
                            <div class="hidden sm:block text-left">
                                <div class="text-xs font-semibold text-slate-800 flex items-center space-x-1.5">
                                    <span>{{ auth()->user()->name }}</span>
                                    @php
                                        $role = auth()->user()->role;
                                        $roleLabel = $role instanceof \App\Enums\UserRole ? $role->label() : (string) $role;
                                        $roleVal = $role instanceof \App\Enums\UserRole ? $role->value : (string) $role;
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold 
                                        {{ $roleVal === 'admin' ? 'bg-purple-100 text-purple-700' : '' }}
                                        {{ $roleVal === 'inventory_manager' ? 'bg-indigo-100 text-indigo-700' : '' }}
                                        {{ $roleVal === 'stock_operator' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                        {{ $roleVal === 'finance_operator' ? 'bg-amber-100 text-amber-700' : '' }}
                                        {{ $roleVal === 'auditor' ? 'bg-cyan-100 text-cyan-700' : '' }}
                                        {{ $roleVal === 'viewer' ? 'bg-slate-100 text-slate-700' : '' }}
                                    ">
                                        {{ $roleLabel }}
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-500 truncate max-w-[140px]">{{ auth()->user()->email }}</div>
                            </div>
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <!-- Dropdown Menu -->
                        <div 
                            x-show="dropdownOpen" 
                            @click.away="dropdownOpen = false" 
                            x-transition:enter="transition ease-out duration-100" 
                            x-transition:enter-start="transform opacity-0 scale-95" 
                            x-transition:enter-end="transform opacity-100 scale-100" 
                            x-transition:leave="transition ease-in duration-75" 
                            x-transition:leave-start="transform opacity-100 scale-100" 
                            x-transition:leave-end="transform opacity-0 scale-95" 
                            class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-slate-100 py-2 z-50 divide-y divide-slate-100"
                            style="display: none;"
                        >
                            <div class="px-4 py-2">
                                <p class="text-xs text-slate-400">Signed in as</p>
                                <p class="text-sm font-semibold text-slate-800 truncate">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-slate-500 truncate">{{ auth()->user()->email }}</p>
                            </div>
                            <div class="py-1">
                                <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-2 text-xs text-slate-700 hover:bg-slate-50">
                                    <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                    </svg>
                                    Dashboard
                                </a>
                                @if (auth()->user()->canManageInventory())
                                <a href="{{ route('assets.create') }}" class="flex items-center px-4 py-2 text-xs text-slate-700 hover:bg-slate-50">
                                    <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Create New Asset
                                </a>
                                @endif
                            </div>
                            <div class="py-1">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button 
                                        type="submit" 
                                        class="w-full flex items-center px-4 py-2 text-xs text-rose-600 hover:bg-rose-50 font-medium transition-colors"
                                    >
                                        <svg class="w-4 h-4 mr-2 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                        </svg>
                                        Sign Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center px-4 py-2 text-xs font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
                        Sign In
                    </a>
                @endauth
            </div>
        </div>
    </div>
</header>
