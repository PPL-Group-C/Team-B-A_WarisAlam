<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'WarisAlam')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Prototype setup (CDN). For production, move Tailwind to Vite: npm i -D tailwindcss and port this config. --}}
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script>
        tailwind.config = {
            theme: { extend: {
                colors: {
                    forest: { DEFAULT: '#05452b', 700: '#0a5a38' },
                    mint: { DEFAULT: '#e8f4ec', 200: '#d6ebdd' },
                },
                fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
            } },
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.469.0/dist/umd/lucide.min.js"></script>
    @stack('head')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="bg-[#fbfdfb] font-sans text-[#0f2a1d] antialiased">

<header class="border-b border-forest/10 bg-white">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-6 px-6 py-3">
        <a href="/" class="flex items-center gap-3">
            <span class="grid h-9 w-9 place-items-center rounded-full bg-mint text-forest"><i data-lucide="landmark" class="h-5 w-5"></i></span>
            <span class="leading-tight">
                <span class="block text-base font-bold text-forest">WarisAlam</span>
                <span class="block text-[11px]">Konservasi Warisan Budaya &amp; Alam Indonesia</span>
            </span>
        </a>

        <div class="relative hidden max-w-md flex-1 md:block">
            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2"></i>
            <input type="search" placeholder="Cari situs cagar budaya &amp; warisan alam (misal: Borobudur, Sangiran)"
                   class="w-full rounded-lg bg-mint py-2 pl-9 pr-3 text-xs outline-none focus:ring-2 focus:ring-forest">
        </div>

        <div class="flex items-center gap-5">
            <button type="button" class="relative" aria-label="Notifikasi">
                <i data-lucide="bell" class="h-5 w-5"></i>
            </button>
            <div class="flex items-center gap-2">
                <span class="grid h-9 w-9 place-items-center rounded-full bg-forest text-sm font-semibold text-white">
                    {{ str(auth()->user()->name ?? 'W')->substr(0, 1)->upper() }}
                </span>
                <span class="hidden text-xs leading-tight sm:block">
                    <span class="block font-semibold">{{ auth()->user()->name ?? 'Relawan' }}</span>
                    <span class="block text-forest">Relawan</span>
                </span>
            </div>
        </div>
    </div>

    {{-- Gate the last three links by role (@can / middleware) once roles exist. --}}
    <nav class="border-t border-forest/10">
        <ul class="mx-auto flex max-w-6xl gap-8 overflow-x-auto px-6 text-sm">
            @foreach ([
                ['Jelajahi Peta', '#', false],
                ['Lapor Kerusakan', route('reports.create'), request()->routeIs('reports.*')],
                ['Detail Situs', '#', false],
                ['Laporan Saya', '#', false],
                ['Adopsi & Check-in', '#', false],
                ['Moderasi', '#', false],
                ['Petugas BPK/BKSDA', '#', false],
                ['Admin & Statistik', '#', false],
            ] as [$label, $href, $active])
                <li>
                    <a href="{{ $href }}"
                       class="block whitespace-nowrap border-b-2 py-3 {{ $active ? 'border-forest font-semibold text-forest' : 'border-transparent' }}">
                        {{ $label }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
</header>

<main class="mx-auto max-w-6xl px-6 py-6">
    @yield('content')
</main>

<footer class="mt-12 bg-mint">
    <div class="mx-auto grid max-w-6xl gap-8 px-6 py-10 text-sm md:grid-cols-[2fr_1fr_1fr]">
        <div>
            <p class="mb-2 flex items-center gap-2 text-lg font-bold text-forest"><i data-lucide="landmark" class="h-5 w-5"></i>WarisAlam Nusantara</p>
            <p class="max-w-md text-xs leading-relaxed">Sistem Informasi Terpadu Pemantauan &amp; Pelestarian Cagar Budaya dan Warisan Alam Berbasis Partisipasi Publik. Dirancang untuk mempercepat respon pemulihan kerusakan dan mitigasi ancaman lingkungan cagar budaya Indonesia.</p>
        </div>
        <div>
            <p class="mb-2 text-base font-bold text-forest">Modul Navigasi</p>
            <ul class="space-y-1 text-xs"><li>Katalog Cagar Budaya</li><li>Peta Kerusakan Spasial</li><li>Standar SOP Pelaporan Cepat</li><li>Buku Panduan Relawan</li></ul>
        </div>
        <div>
            <p class="mb-2 text-base font-bold text-forest">Bantuan</p>
            <p class="text-xs">Pusat Layanan Darurat Cagar:</p>
            <p class="text-xs font-semibold text-forest">0800-1-WARISAN (Bebas Pulsa)</p>
        </div>
    </div>
    <div class="mx-auto max-w-6xl border-t border-forest/10 px-6 py-4 text-xs">&copy; {{ date('Y') }} WarisAlam &bull; Kolaborasi Pemantauan Budaya &amp; Konservasi Alam Indonesia</div>
</footer>

@stack('scripts')
<script>document.addEventListener('DOMContentLoaded', () => lucide.createIcons());</script>
</body>
</html>
