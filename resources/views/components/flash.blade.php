@if (session('success') || session('status') || session('error') || session('warning') || $errors->any())
<div class="mb-6 space-y-3" x-data="{ show: true }" x-show="show" x-transition:leave="transition ease-in duration-300 transform opacity-100" x-transition:leave-end="opacity-0 scale-95">
    @if (session('success'))
    <div class="flex items-center justify-between p-4 text-sm font-medium text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl shadow-xs" role="alert">
        <div class="flex items-center space-x-3">
            <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
        <button type="button" @click="show = false" class="text-emerald-500 hover:text-emerald-700 p-1 rounded-lg focus:outline-none">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>
    @endif

    @if (session('status'))
    <div class="flex items-center justify-between p-4 text-sm font-medium text-blue-800 bg-blue-50 border border-blue-200 rounded-xl shadow-xs" role="alert">
        <div class="flex items-center space-x-3">
            <svg class="w-5 h-5 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>{{ session('status') }}</span>
        </div>
        <button type="button" @click="show = false" class="text-blue-500 hover:text-blue-700 p-1 rounded-lg focus:outline-none">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>
    @endif

    @if (session('warning'))
    <div class="flex items-center justify-between p-4 text-sm font-medium text-amber-800 bg-amber-50 border border-amber-200 rounded-xl shadow-xs" role="alert">
        <div class="flex items-center space-x-3">
            <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <span>{{ session('warning') }}</span>
        </div>
        <button type="button" @click="show = false" class="text-amber-500 hover:text-amber-700 p-1 rounded-lg focus:outline-none">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>
    @endif

    @if (session('error'))
    <div class="flex items-center justify-between p-4 text-sm font-medium text-rose-800 bg-rose-50 border border-rose-200 rounded-xl shadow-xs" role="alert">
        <div class="flex items-center space-x-3">
            <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
        <button type="button" @click="show = false" class="text-rose-500 hover:text-rose-700 p-1 rounded-lg focus:outline-none">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>
    @endif

    @if ($errors->any())
    <div class="p-4 text-sm font-medium text-rose-800 bg-rose-50 border border-rose-200 rounded-xl shadow-xs" role="alert">
        <div class="flex items-start justify-between">
            <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-rose-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div>
                    <div class="font-semibold text-rose-900 mb-1">Please correct the following errors:</div>
                    <ul class="list-disc list-inside space-y-1 text-rose-700 font-normal">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" @click="show = false" class="text-rose-500 hover:text-rose-700 p-1 rounded-lg focus:outline-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
    </div>
    @endif
</div>
@endif
