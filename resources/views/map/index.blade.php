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

        <form id="map-filters" class="mb-4 grid gap-2 rounded-2xl border border-stone-200 bg-white p-3 shadow-sm md:grid-cols-[minmax(0,2fr)_repeat(3,minmax(0,1fr))_auto]" onsubmit="return false">
            <label class="sr-only" for="filter-search">Cari situs</label>
            <input id="filter-search" type="search" autocomplete="off" placeholder="Cari nama situs (misal: Borobudur, Komodo)…"
                class="w-full rounded-lg border border-stone-200 bg-stone-50 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">

            <label class="sr-only" for="filter-type">Tipe situs</label>
            <select id="filter-type" class="rounded-lg border border-stone-200 bg-stone-50 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none">
                <option value="">Semua Tipe</option>
                <option value="cultural">Cagar Budaya</option>
                <option value="natural">Cagar Alam</option>
            </select>

            <label class="sr-only" for="filter-province">Provinsi</label>
            <select id="filter-province" class="rounded-lg border border-stone-200 bg-stone-50 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none">
                <option value="">Semua Provinsi</option>
            </select>

            <label class="sr-only" for="filter-status">Status ancaman</label>
            <select id="filter-status" class="rounded-lg border border-stone-200 bg-stone-50 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none">
                <option value="">Semua Status Ancaman</option>
                <option value="safe">Aman</option>
                <option value="watch">Waspada</option>
                <option value="threatened">Terancam</option>
            </select>

            <button id="filter-clear" type="reset" class="rounded-lg border border-emerald-700 px-4 py-2 text-sm font-medium text-emerald-800 hover:bg-emerald-50">
                Reset
            </button>
        </form>

        <p id="filter-count" class="-mt-2 mb-3 text-xs text-stone-500" aria-live="polite"></p>

        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_340px]">
        <section class="relative overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
            <div id="site-map" data-sites-url="{{ route('map.sites') }}" class="h-[70vh] min-h-[420px] w-full bg-emerald-50"></div>

            <div id="map-loading" class="absolute inset-x-0 top-4 z-[1000] mx-auto w-fit rounded-lg bg-white px-4 py-2 text-sm text-stone-600 shadow">
                Memuat data situs&hellip;
            </div>

            <div id="map-empty" class="absolute inset-x-0 top-4 z-[1000] mx-auto hidden w-fit rounded-lg bg-white px-4 py-2 text-sm text-stone-600 shadow">
                Belum ada situs yang terdata.
            </div>

            <p class="pointer-events-none absolute right-3 top-3 z-[1000] hidden rounded-full bg-white/90 px-3 py-1 text-xs text-stone-600 shadow md:block">
                Geser: dua jari / drag &middot; Zoom: cubit / Ctrl + scroll
            </p>

            <div id="map-no-results" role="status" class="absolute inset-x-0 top-4 z-[1000] mx-auto hidden w-fit rounded-lg bg-white px-4 py-2 text-sm text-stone-700 shadow">
                <b>No sites found.</b> Tidak ada situs yang cocok dengan filter. Coba ubah atau reset filter.
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

        <aside class="flex max-h-[70vh] min-h-[420px] flex-col overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm" aria-labelledby="site-list-title">
            <div class="border-b border-stone-100 p-4">
                <h2 id="site-list-title" class="font-semibold text-emerald-900">Katalog Situs Terpantau</h2>
                <div class="mt-2 space-y-1.5">
                    <p class="text-xs text-stone-500">Urutkan prioritas penanganan:</p>
                    <div id="site-sort" role="radiogroup" aria-label="Urutan katalog" class="grid grid-cols-3 rounded-lg bg-stone-100 p-0.5 text-xs font-medium">
                        <button type="button" role="radio" data-sort="risk" aria-checked="true" class="site-sort__btn">Paling Rawan</button>
                        <button type="button" role="radio" data-sort="nearest" aria-checked="false" class="site-sort__btn">Terdekat</button>
                        <button type="button" role="radio" data-sort="name" aria-checked="false" class="site-sort__btn">A&ndash;Z</button>
                    </div>
                </div>
                <p id="site-sort-note" class="mt-2 hidden text-xs text-amber-700"></p>
            </div>

            <ol id="site-list" class="flex-1 space-y-2 overflow-y-auto p-3"></ol>

            <p id="site-list-empty" class="hidden p-6 text-center text-sm text-stone-500">No sites found.</p>
        </aside>
        </div>
    </main>
</body>
</html>
