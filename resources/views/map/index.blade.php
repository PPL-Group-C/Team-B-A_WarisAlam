<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jelajah Peta - WarisAlam</title>
    @vite(['resources/css/app.css', 'resources/js/map.js'])
</head>
<body class="min-h-screen bg-stone-50 font-sans text-stone-800 antialiased">
    <header class="border-b border-stone-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center gap-3 px-4 py-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-800 font-bold text-white">W</div>
            <div>
                <p class="font-semibold leading-tight text-emerald-900">WarisAlam</p>
                <p class="text-xs text-stone-500">Konservasi Warisan Budaya &amp; Alam Indonesia</p>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-6">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-emerald-900">Jelajah Peta</h1>
                <p class="text-sm text-stone-500">Peta situs cagar budaya dan alam beserta status ancamannya.</p>
            </div>
            <p class="rounded-full border border-emerald-200 bg-emerald-50 px-4 py-1.5 text-sm text-emerald-900">
                <b id="summary-total">0</b> Situs Terdata
                <span class="mx-1 text-stone-400">&bull;</span>
                <b id="summary-at-risk" class="text-red-600">0</b> <span class="text-red-600">Wilayah Rawan</span>
            </p>
        </div>

        <section class="relative overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
            <div id="site-map" data-sites-url="{{ route('map.sites') }}" class="h-[70vh] min-h-[420px] w-full bg-emerald-50"></div>

            <div id="map-loading" class="absolute inset-x-0 top-4 z-[1000] mx-auto w-fit rounded-lg bg-white px-4 py-2 text-sm text-stone-600 shadow">
                Memuat data situs&hellip;
            </div>

            <div id="map-empty" class="absolute inset-x-0 top-4 z-[1000] mx-auto hidden w-fit rounded-lg bg-white px-4 py-2 text-sm text-stone-600 shadow">
                Belum ada situs yang terdata.
            </div>

            <div id="map-error" class="absolute inset-x-0 top-4 z-[1000] mx-auto hidden w-fit rounded-lg bg-red-600 px-4 py-2 text-sm text-white shadow">
                Peta gagal dimuat. Periksa koneksi internet lalu muat ulang halaman.
            </div>

            <div class="absolute bottom-8 left-4 z-[1000] w-64 rounded-xl border border-stone-200 bg-white/95 p-4 text-sm shadow">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-emerald-900">Legenda Indeks F11</p>
                <ul class="space-y-1.5">
                    <li class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-full bg-[#059669]"></span>
                        <span><b>Aman</b> &middot; skor &lt; 5</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-full bg-[#f59e0b]"></span>
                        <span><b>Waspada</b> &middot; 5 &le; skor &lt; 15</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-full bg-[#dc2626]"></span>
                        <span><b>Terancam</b> &middot; skor &ge; 15</span>
                    </li>
                </ul>
            </div>
        </section>
    </main>
</body>
</html>
