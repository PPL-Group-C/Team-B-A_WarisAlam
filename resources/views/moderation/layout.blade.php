{{--
    Layout SEMENTARA untuk modul Moderasi (F08).
    Setelah layout utama dari tim fondasi tersedia, cukup ganti
    @extends('moderation.layout') di moderation/index.blade.php.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Moderasi') · WarisAlam</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Prototype (CDN) mengikuti layouts/app.blade.php; pindah ke Vite bersama tim fondasi. --}}
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script>
        tailwind.config = {
            theme: { extend: {
                // Palet dari Figma "WARIS ALAM" (frame Moderasi F08).
                colors: {
                    forest: { DEFAULT: '#04442d', 700: '#0a5a38' },
                    mint: { DEFAULT: '#e8f7ec', 100: '#e2f2e7', 200: '#ddece1' },
                    leaf: { DEFAULT: '#cdebc5', ink: '#344d31' },
                    sage: '#516b4d',
                    jade: { DEFAULT: '#b5f0cf', ink: '#002113' },
                    danger: { DEFAULT: '#ba1a1a', bg: '#ffdad6', ink: '#93000a' },
                    ink: { DEFAULT: '#111e18', soft: '#404943', muted: '#707973' },
                },
                fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
            } },
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.469.0/dist/umd/lucide.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="min-h-screen bg-[#fbfdfb] font-sans text-[#0f2a1d] antialiased">

<header class="border-b border-forest/10 bg-white">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-8">
        <a href="/" class="flex items-center gap-3">
            <span class="grid h-9 w-9 place-items-center rounded-full bg-mint text-forest"><i data-lucide="landmark" class="h-5 w-5"></i></span>
            <span class="leading-tight">
                <span class="block text-base font-bold text-forest">WarisAlam</span>
                <span class="hidden text-[11px] sm:block">Konservasi Warisan Budaya &amp; Alam Indonesia</span>
            </span>
        </a>

        {{-- Pencarian global (sementara diarahkan ke pencarian antrean moderasi sampai fitur pencarian situs tersedia). --}}
        <form method="GET" action="{{ route('moderation.index') }}" class="hidden max-w-md flex-1 md:block" role="search">
            <label class="relative block">
                <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-forest/60"></i>
                <input type="search" name="q" value="{{ request('q') }}"
                       placeholder="Cari situs cagar budaya & warisan alam (misal: Borobudur, Sangiran)"
                       class="w-full rounded-lg border border-forest/15 bg-mint/50 py-2 pl-9 pr-3 text-xs outline-none focus:ring-2 focus:ring-forest">
            </label>
        </form>

        @auth
            <div class="flex items-center gap-3">
                {{-- Notifikasi: tampilan saja, isinya dari fitur notifikasi (Fasya). --}}
                <span class="relative grid h-9 w-9 place-items-center rounded-full text-forest hover:bg-mint" title="Notifikasi">
                    <i data-lucide="bell" class="h-5 w-5"></i>
                </span>
                <div class="flex items-center gap-2">
                    <span class="grid h-9 w-9 place-items-center rounded-full bg-forest text-sm font-semibold text-white">
                        {{ str(auth()->user()->name)->substr(0, 1)->upper() }}
                    </span>
                    <span class="hidden text-xs leading-tight sm:block">
                        <span class="block font-semibold">{{ auth()->user()->name }}</span>
                        <span class="block text-forest">Moderator</span>
                    </span>
                    <i data-lucide="chevron-down" class="hidden h-4 w-4 text-forest/60 sm:block"></i>
                </div>
            </div>
        @endauth
    </div>

    <nav class="border-t border-forest/10">
        <ul class="mx-auto flex max-w-7xl gap-8 overflow-x-auto px-4 text-sm sm:px-8 lg:justify-between">
            @foreach ([
                ['Jelajah Peta', '#', false],
                ['Lapor Kerusakan', Route::has('reports.create') ? route('reports.create') : '#', false],
                ['Detail Situs', '#', false],
                ['Laporan Saya', '#', false],
                ['Adopsi & Check-in', '#', false],
                ['Moderasi', route('moderation.index'), true],
                ['Petugas BPK/BKSDA', '#', false],
                ['Admin & Statistik', '#', false],
            ] as [$label, $href, $active])
                <li>
                    <a href="{{ $href }}"
                       class="block whitespace-nowrap border-b-2 py-3 {{ $active ? 'border-forest font-semibold text-forest' : 'border-transparent hover:text-forest' }}">
                        {{ $label }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
</header>

@yield('content')

{{-- Footer sesuai desain Figma (isi sama dengan layouts/app.blade.php). --}}
<footer class="mx-auto mt-12 max-w-7xl bg-mint" data-testid="site-footer">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 text-sm sm:px-6 md:grid-cols-[2fr_1fr_1fr]">
        <div>
            <p class="mb-2 flex items-center gap-2 text-lg font-bold text-forest">
                <span class="grid h-7 w-7 place-items-center rounded-full bg-white"><i data-lucide="landmark" class="h-4 w-4"></i></span>
                WarisAlam Nusantara
            </p>
            <p class="max-w-md text-xs leading-relaxed">Sistem Informasi Terpadu Pemantauan &amp; Pelestarian Cagar Budaya dan Warisan Alam Berbasis Partisipasi Publik. Dirancang untuk mempercepat respon pemulihan kerusakan dan mitigasi ancaman lingkungan cagar budaya Indonesia.</p>
            <div class="mt-3 flex flex-wrap gap-2 text-[11px] font-semibold">
                <span class="inline-flex items-center gap-1 rounded bg-mint-200 px-2 py-1"><i data-lucide="landmark" class="h-3 w-3"></i> Kolaborasi Balai Pelestarian Kebudayaan (BPK)</span>
                <span class="inline-flex items-center gap-1 rounded bg-mint-200 px-2 py-1"><i data-lucide="trees" class="h-3 w-3"></i> BKSDA KLHK RI</span>
            </div>
        </div>
        <div>
            <p class="mb-2 text-base font-bold text-forest">Modul Navigasi</p>
            <ul class="space-y-1 text-xs">
                <li>Katalog Cagar Budaya</li>
                <li>Peta Kerusakan Spasial</li>
                <li>Standar SOP Pelaporan Cepat</li>
                <li>Buku Panduan Relawan</li>
            </ul>
        </div>
        <div>
            <p class="mb-2 text-base font-bold text-forest">Bantuan</p>
            <p class="text-xs font-semibold">Pusat Layanan Darurat Cagar:</p>
            <p class="text-sm font-bold text-forest">0800-1-WARISAN (Bebas Pulsa)</p>
        </div>
    </div>
    <div class="mx-auto flex max-w-7xl flex-col gap-2 border-t border-forest/10 px-4 py-4 text-xs sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <p>&copy; {{ date('Y') }} WarisAlam &bull; Kolaborasi Pemantauan Budaya &amp; Konservasi Alam Indonesia.</p>
        <ul class="flex flex-wrap gap-x-4 gap-y-1 font-medium">
            <li>Kebijakan Privasi Data Geospasial</li>
            <li>Lisensi Sains Terbuka</li>
            <li>Kontak Pengelola</li>
        </ul>
    </div>
</footer>

@stack('scripts')
<script>document.addEventListener('DOMContentLoaded', () => lucide.createIcons());</script>
</body>
</html>
