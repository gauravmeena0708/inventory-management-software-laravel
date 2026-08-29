<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'NDC Inventory Management') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS (Play CDN for reliable styling in all environments) -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }

        /*
         * Legacy view compatibility
         *
         * A few resource screens still use the Bootstrap class names from the
         * original application. Keep them visually aligned with the current
         * Tailwind interface without loading a second, conflicting framework.
         */
        .col { width: 100%; }
        .card {
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            background: #fff;
            box-shadow: 0 1px 2px rgb(15 23 42 / 0.04);
        }
        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            color: #0f172a;
            font-size: 1.125rem;
            font-weight: 700;
        }
        .card-body { padding: 1.5rem; }
        .card-footer {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: 1rem 1.5rem;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            color: #334155;
            font-size: .875rem;
        }
        .table th {
            padding: .75rem 1rem;
            border-bottom: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #475569;
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .05em;
            text-align: left;
            text-transform: uppercase;
        }
        .table td {
            padding: .875rem 1rem;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .table tbody tr:last-child td { border-bottom: 0; }
        .table tbody tr:hover { background: #f8fafc; }
        .table-striped tbody tr:nth-child(even),
        .table-stripped tbody tr:nth-child(even) { background: #fafafa; }
        .table a { color: #4f46e5; font-weight: 600; }
        .table a:hover { color: #3730a3; text-decoration: underline; }
        .table-bordered { border: 1px solid #e2e8f0; }
        .table-bordered th,
        .table-bordered td { border-right: 1px solid #e2e8f0; }
        .table-bordered th:last-child,
        .table-bordered td:last-child { border-right: 0; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label {
            display: block;
            margin-bottom: .4rem;
            color: #334155;
            font-size: .875rem;
            font-weight: 600;
        }
        .form-control {
            display: block;
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: .75rem;
            background: #f8fafc;
            padding: .625rem .875rem;
            color: #0f172a;
            font-size: .875rem;
            line-height: 1.5;
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .form-control:focus {
            outline: 0;
            border-color: #6366f1;
            background: #fff;
            box-shadow: 0 0 0 3px rgb(99 102 241 / .14);
        }
        .form-control.is-invalid { border-color: #e11d48; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            border: 1px solid transparent;
            border-radius: .75rem;
            padding: .625rem 1rem;
            font-size: .875rem;
            font-weight: 700;
            line-height: 1.25rem;
            text-decoration: none;
            transition: background .15s ease, border-color .15s ease, color .15s ease;
            cursor: pointer;
        }
        .btn-primary { background: #4f46e5; color: #fff; }
        .btn-primary:hover { background: #4338ca; }
        .btn-secondary { border-color: #cbd5e1; background: #fff; color: #334155; }
        .btn-secondary:hover { background: #f8fafc; }
        .alert {
            margin-bottom: 1.25rem;
            border: 1px solid;
            border-radius: .75rem;
            padding: .875rem 1rem;
            font-size: .875rem;
        }
        .alert-danger { border-color: #fecdd3; background: #fff1f2; color: #be123c; }
        .text-danger { color: #be123c; }
        .bg-success { background: #ecfdf5; color: #047857; }
        .badge {
            display: inline-flex;
            align-items: center;
            border-radius: 9999px;
            padding: .2rem .6rem;
            font-size: .75rem;
            font-weight: 700;
        }
        .badge-warning { background: #fef3c7; color: #92400e; }

        @media (max-width: 767px) {
            .card-body { overflow-x: auto; padding: 1rem; }
            .card-header, .card-footer { padding-left: 1rem; padding-right: 1rem; }
            .table { min-width: 42rem; }
        }
    </style>

    @stack('styles')
</head>
<body class="h-full bg-slate-50 text-slate-800 antialiased" x-data="{ sidebarOpen: false }">
    <div id="app" class="min-h-full flex">
        @auth
            <!-- Mobile Sidebar Backdrop -->
            <div 
                x-show="sidebarOpen" 
                @click="sidebarOpen = false" 
                x-transition:enter="transition-opacity ease-linear duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-300"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-30 bg-slate-900/60 backdrop-blur-xs lg:hidden"
                style="display: none;"
            ></div>

            <!-- Responsive Sidebar -->
            <x-sidebar />

            <!-- Main Layout Content Area -->
            <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
                <x-topbar />

                <main class="flex-1 overflow-y-auto px-4 sm:px-6 lg:px-8 py-6">
                    <div class="max-w-7xl mx-auto">
                        <x-flash />
                        @yield('content')
                    </div>
                </main>
            </div>
        @else
            <!-- Guest Fullscreen Container (e.g., Login) -->
            <main class="flex-1 flex flex-col min-h-screen">
                <div class="flex-1 flex items-center justify-center p-4 sm:p-6 lg:p-8">
                    <div class="w-full max-w-md">
                        <x-flash />
                        @yield('content')
                    </div>
                </div>
            </main>
        @endauth
    </div>

    @stack('scripts')
</body>
</html>
