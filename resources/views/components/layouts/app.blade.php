<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Perbaikan SEO: Menambahkan meta description -->
    <meta name="description" content="Aplikasi Inventaris Stok Barang untuk memanajemen persediaan secara efisien.">
    
    <title>{{ $title ?? config('app.name') }} - {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
    @stack('head')
</head>
<body class="bg-slate-100 text-slate-800 antialiased">
<div class="min-h-screen md:flex">
    @auth
        <aside class="w-full shrink-0 bg-slate-900 p-4 text-slate-100 md:min-h-screen md:w-64">
            <div class="mb-6 text-lg font-bold">{{ config('app.name') }}</div>

            <div class="mb-6 flex items-center gap-3">
                @if (auth()->user()->profilePhotoUrl())
                    <img src="{{ auth()->user()->profilePhotoUrl() }}" alt="Foto profil" class="h-10 w-10 rounded-full object-cover">
                @else
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-500 font-semibold">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                @endif
                <div class="min-w-0">
                    <div class="truncate text-sm font-semibold">{{ auth()->user()->name }}</div>
                    <div class="text-xs uppercase text-slate-400">{{ auth()->user()->role }}</div>
                </div>
            </div>

            <nav class="space-y-1 text-sm">
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.users') }}" class="block rounded px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('admin.*') ? 'bg-slate-800' : '' }}">Daftar User</a>
                @else
                    <a href="{{ route('stocks.index') }}" class="block rounded px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('stocks.*') ? 'bg-slate-800' : '' }}">Stok Barang</a>
                @endif
                <a href="{{ route('profile') }}" class="block rounded px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('profile') ? 'bg-slate-800' : '' }}">Profil</a>
            </nav>

            <form method="POST" action="{{ route('logout') }}" class="mt-6">
                @csrf
                <button type="submit" class="w-full rounded bg-slate-700 px-3 py-2 text-left text-sm hover:bg-slate-600">Keluar</button>
            </form>
        </aside>
    @endauth

    <main class="min-w-0 flex-1 p-4 md:p-8">
        {{ $slot }}
    </main>
</div>
</body>
</html>