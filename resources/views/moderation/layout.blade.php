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
                colors: {
                    forest: { DEFAULT: '#05452b', 700: '#0a5a38' },
                    mint: { DEFAULT: '#e8f4ec', 200: '#d6ebdd' },
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
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
        <a href="/" class="flex items-center gap-3">
            <span class="grid h-9 w-9 place-items-center rounded-full bg-mint text-forest"><i data-lucide="landmark" class="h-5 w-5"></i></span>
            <span class="leading-tight">
                <span class="block text-base font-bold text-forest">WarisAlam</span>
                <span class="hidden text-[11px] sm:block">Konservasi Warisan Budaya &amp; Alam Indonesia</span>
            </span>
        </a>

        @auth
            <div class="flex items-center gap-2">
                <span class="grid h-9 w-9 place-items-center rounded-full bg-forest text-sm font-semibold text-white">
                    {{ str(auth()->user()->name)->substr(0, 1)->upper() }}
                </span>
                <span class="hidden text-xs leading-tight sm:block">
                    <span class="block font-semibold">{{ auth()->user()->name }}</span>
                    <span class="block text-forest">Moderator</span>
                </span>
            </div>
        @endauth
    </div>

    <nav class="border-t border-forest/10">
        <ul class="mx-auto flex max-w-7xl gap-8 overflow-x-auto px-4 text-sm sm:px-6">
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

<footer class="mt-12 border-t border-forest/10 bg-mint">
    <div class="mx-auto max-w-7xl px-4 py-4 text-xs sm:px-6">&copy; {{ date('Y') }} WarisAlam &bull; Kolaborasi Pemantauan Budaya &amp; Konservasi Alam Indonesia</div>
</footer>

@stack('scripts')
<script>document.addEventListener('DOMContentLoaded', () => lucide.createIcons());</script>
</body>
</html>
