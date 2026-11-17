@extends('layouts.app')

@section('title', $site->name.' — WarisAlam')

@push('head')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
@endpush

@php
    $location = collect([$site->city, $site->province])->filter()->implode(', ');
    $meter = max(0, min(100, $score / 20 * 100)); // skala tampilan 0–20+
    $scoreUpdatedAt = $site->score_updated_at ? \Illuminate\Support\Carbon::parse($site->score_updated_at)->locale('id') : null;
@endphp

@section('content')
<nav aria-label="Breadcrumb" class="mb-4 text-xs">
    <ol class="flex flex-wrap items-center gap-1.5">
        <li><a href="{{ url('/map') }}" class="text-forest hover:underline">Jelajahi Peta</a></li>
        <li aria-hidden="true">/</li>
        <li class="font-medium" aria-current="page">Detail Situs</li>
    </ol>
</nav>

{{-- Judul situs --}}
<header class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div class="min-w-0">
        <div class="mb-2 flex flex-wrap items-center gap-2 text-xs">
            <span class="inline-flex items-center gap-1 rounded-full bg-mint px-3 py-1 font-semibold text-forest">
                <i data-lucide="{{ $type->icon() }}" class="h-3.5 w-3.5"></i>{{ $type->label() }}
            </span>
            @if ($site->category)
                <span class="rounded-full border border-forest/20 px-3 py-1">{{ $site->category }}</span>
            @endif
        </div>
        <h1 class="text-2xl font-bold text-forest md:text-3xl">{{ $site->name }}</h1>
        <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
            @if ($location)
                <span class="inline-flex items-center gap-1"><i data-lucide="map-pin" class="h-4 w-4 text-forest"></i>{{ $location }}</span>
            @endif
            <span class="inline-flex items-center gap-1"><i data-lucide="file-badge" class="h-4 w-4 text-forest"></i>No. Registrasi {{ $site->registration_id }}</span>
        </p>
        @if ($site->description)
            <p class="mt-3 max-w-3xl text-sm leading-relaxed">{{ $site->description }}</p>
        @endif
    </div>

    <span class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold {{ $status->badgeClasses() }}">
        <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $status->color() }}"></span>
        Status: {{ $status->label() }}
    </span>
</header>

<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
    <div class="min-w-0 space-y-8">

        {{-- PBI-13: Galeri foto --}}
        <section id="galeri" x-data="siteGallery(@js($photos->map(fn ($p) => ['url' => $p->url(), 'caption' => $p->caption, 'credit' => $p->credit])->values()))">
            <x-section-bar icon="images" title="Galeri Foto">{{ $photos->count() }} foto</x-section-bar>

            @if ($photos->isEmpty())
                <div class="grid place-items-center rounded-2xl border border-dashed border-forest/20 bg-white px-6 py-12 text-center text-sm">
                    <i data-lucide="image-off" class="mb-2 h-8 w-8 text-forest/50"></i>
                    <p class="font-medium">Belum ada foto untuk situs ini.</p>
                    <p class="text-xs">Foto ditambahkan oleh admin atau moderator.</p>
                </div>
            @else
                <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
                    @foreach ($photos as $i => $photo)
                        <button type="button" @click="show({{ $i }})"
                                class="group relative overflow-hidden rounded-xl bg-mint text-left focus:outline-none focus:ring-2 focus:ring-forest {{ $i === 0 ? 'col-span-2 row-span-2' : '' }}">
                            <img src="{{ $photo->url() }}" alt="{{ $photo->caption ?? $site->name }}" loading="lazy"
                                 class="aspect-[4/3] h-full w-full object-cover transition group-hover:scale-105">
                            @if ($photo->caption)
                                <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent px-3 pb-2 pt-6 text-xs font-medium text-white">{{ $photo->caption }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>

                {{-- Lightbox --}}
                <div x-show="open" x-cloak x-transition.opacity @keydown.escape.window="open = false"
                     @keydown.arrow-right.window="next()" @keydown.arrow-left.window="prev()"
                     class="fixed inset-0 z-[2000] grid place-items-center bg-black/85 p-4" role="dialog" aria-modal="true" aria-label="Foto situs">
                    <button type="button" @click="open = false" class="absolute right-4 top-4 rounded-full bg-white/10 p-2 text-white hover:bg-white/20" aria-label="Tutup">
                        <i data-lucide="x" class="h-6 w-6"></i>
                    </button>
                    <button type="button" @click="prev()" x-show="photos.length > 1" class="absolute left-4 rounded-full bg-white/10 p-2 text-white hover:bg-white/20" aria-label="Foto sebelumnya">
                        <i data-lucide="chevron-left" class="h-6 w-6"></i>
                    </button>
                    <figure class="max-w-4xl">
                        <img :src="photos[index]?.url" :alt="photos[index]?.caption ?? ''" class="max-h-[78vh] w-auto rounded-lg">
                        <figcaption class="mt-3 text-center text-sm text-white">
                            <span x-text="photos[index]?.caption"></span>
                            <span class="block text-xs text-white/60" x-text="photos[index]?.credit"></span>
                        </figcaption>
                    </figure>
                    <button type="button" @click="next()" x-show="photos.length > 1" class="absolute right-4 rounded-full bg-white/10 p-2 text-white hover:bg-white/20" aria-label="Foto berikutnya">
                        <i data-lucide="chevron-right" class="h-6 w-6"></i>
                    </button>
                </div>
            @endif
        </section>

        {{-- PBI-13: Sejarah --}}
        <section id="sejarah">
            <x-section-bar icon="scroll-text" title="Sejarah Situs"></x-section-bar>
            @if (filled($site->history))
                <div class="space-y-3 rounded-2xl bg-white p-5 text-sm leading-relaxed shadow-sm">
                    @foreach (preg_split('/\R{2,}/', trim($site->history)) as $paragraph)
                        <p>{!! nl2br(e($paragraph)) !!}</p>
                    @endforeach
                </div>
            @else
                <p class="rounded-2xl bg-white p-5 text-sm shadow-sm">Sejarah situs belum diisi.</p>
            @endif
        </section>

        {{-- PBI-13: Fakta --}}
        <section id="fakta">
            <x-section-bar icon="list-checks" title="Fakta Situs"></x-section-bar>
            @php
                $factRows = collect([
                    ['Tipe', $type->label()],
                    ['Kategori', $site->category],
                    ['Lokasi', $location],
                    ['Nomor Registrasi', $site->registration_id],
                    ...array_map(fn ($f) => [$f['label'], $f['value']], $facts),
                ])->filter(fn ($row) => filled($row[1]))->values();
            @endphp
            <dl class="grid gap-px overflow-hidden rounded-2xl bg-forest/10 text-sm shadow-sm sm:grid-cols-2">
                @foreach ($factRows as [$label, $value])
                    <div class="bg-white px-5 py-3 {{ $loop->last && $loop->count % 2 === 1 ? 'sm:col-span-2' : '' }}">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-forest">{{ $label }}</dt>
                        <dd class="mt-0.5">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        {{-- PBI-15: Riwayat laporan terverifikasi --}}
        <section id="riwayat-laporan">
            <x-section-bar icon="history" title="Riwayat Laporan Terverifikasi">{{ $reportStats['total'] }} laporan</x-section-bar>

            @if ($reports->isEmpty())
                <div class="grid place-items-center rounded-2xl border border-dashed border-forest/20 bg-white px-6 py-10 text-center text-sm">
                    <i data-lucide="shield-check" class="mb-2 h-8 w-8 text-forest/50"></i>
                    <p class="font-medium">Belum ada laporan kerusakan terverifikasi.</p>
                    <p class="text-xs">Laporan warga tampil di sini setelah lolos verifikasi moderator.</p>
                </div>
            @else
                <ol class="space-y-3">
                    @foreach ($reports as $report)
                        @include('sites.partials.report-card', ['report' => $report, 'site' => $site])
                    @endforeach
                </ol>
                <div class="mt-4">{{ $reports->links() }}</div>
            @endif
        </section>
    </div>

    {{-- Sidebar --}}
    <aside class="space-y-5 lg:sticky lg:top-4 lg:self-start">

        {{-- PBI-14: Status & skor --}}
        <section aria-labelledby="status-title" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-forest/10">
            <h2 id="status-title" class="mb-3 flex items-center gap-2 text-sm font-semibold text-forest">
                <i data-lucide="activity" class="h-4 w-4"></i>Kondisi Situs Saat Ini
            </h2>

            <div class="flex items-center gap-3">
                <span class="grid h-12 w-12 place-items-center rounded-full {{ $status->badgeClasses() }}">
                    <i data-lucide="{{ $status->icon() }}" class="h-6 w-6"></i>
                </span>
                <div>
                    <p class="text-lg font-bold" style="color: {{ $status->color() }}">{{ $status->label() }}</p>
                    <p class="text-xs">{{ $status->description() }}</p>
                </div>
            </div>

            <div class="mt-4">
                <div class="flex items-end justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wide text-forest">Skor Ancaman</span>
                    <span><b class="text-2xl" data-testid="threat-score">{{ number_format($score, 1, ',', '.') }}</b></span>
                </div>
                <div class="relative mt-2 h-2.5 rounded-full"
                     style="background: linear-gradient(90deg, #059669 0 25%, #f59e0b 25% 75%, #dc2626 75% 100%)"
                     role="meter" aria-valuemin="0" aria-valuemax="20" aria-valuenow="{{ $score }}" aria-label="Skor ancaman">
                    <span class="absolute top-1/2 h-4 w-4 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white shadow" style="left: {{ $meter }}%; background: {{ $status->color() }}"></span>
                </div>
                <div class="mt-1 flex justify-between text-[10px]"><span>0</span><span>5</span><span>15</span><span>20+</span></div>
            </div>

            <ul class="mt-4 space-y-1 text-xs">
                @foreach (\App\Enums\SiteStatus::cases() as $level)
                    <li class="flex items-center gap-2 {{ $level === $status ? 'font-semibold' : '' }}">
                        <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $level->color() }}"></span>
                        {{ $level->label() }} &middot; {{ $level->scoreRange() }}
                    </li>
                @endforeach
            </ul>

            <p class="mt-3 text-[11px] leading-relaxed">
                Skor dihitung dari laporan terverifikasi: severity &times; bobot verifikasi &times; 0,5<sup>(umur hari / 30)</sup>.
                @if ($scoreUpdatedAt)
                    Diperbarui {{ $scoreUpdatedAt->diffForHumans() }}.
                @endif
            </p>

            <dl class="mt-4 grid grid-cols-3 gap-2 text-center">
                <div class="rounded-lg bg-mint px-2 py-2">
                    <dt class="text-[10px] uppercase tracking-wide">Pengadopsi</dt>
                    <dd class="text-lg font-bold text-forest" data-testid="adopters-count">{{ $adoptersCount }}</dd>
                </div>
                <div class="rounded-lg bg-mint px-2 py-2">
                    <dt class="text-[10px] uppercase tracking-wide">Ditangani</dt>
                    <dd class="text-lg font-bold text-forest">{{ $reportStats['handling'] }}</dd>
                </div>
                <div class="rounded-lg bg-mint px-2 py-2">
                    <dt class="text-[10px] uppercase tracking-wide">Selesai</dt>
                    <dd class="text-lg font-bold text-forest">{{ $reportStats['resolved'] }}</dd>
                </div>
            </dl>
        </section>

        {{-- Aksi --}}
        <section class="space-y-2">
            @if (Route::has('reports.create'))
                <a href="{{ route('reports.create') }}"
                   class="flex w-full items-center justify-center gap-2 rounded-lg bg-forest px-4 py-2.5 text-sm font-semibold text-white hover:bg-forest-700">
                    <i data-lucide="siren" class="h-4 w-4"></i>Laporkan Kerusakan
                </a>
            @endif
            <button type="button" disabled title="Fitur adopsi hadir di Sprint 2"
                    class="flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-lg border border-forest/30 px-4 py-2.5 text-sm font-semibold text-forest/60">
                <i data-lucide="heart-handshake" class="h-4 w-4"></i>Adopsi Situs (segera hadir)
            </button>
        </section>

        {{-- Lokasi --}}
        <section aria-labelledby="lokasi-title" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-forest/10">
            <h2 id="lokasi-title" class="flex items-center gap-2 px-5 pt-4 text-sm font-semibold text-forest">
                <i data-lucide="map" class="h-4 w-4"></i>Lokasi
            </h2>
            <div id="site-location-map" class="mt-3 h-48 w-full bg-mint"
                 data-lat="{{ $site->latitude }}" data-lng="{{ $site->longitude }}" data-color="{{ $status->color() }}"></div>
            <p class="flex items-center justify-between gap-2 px-5 py-3 text-xs">
                <span>{{ number_format((float) $site->latitude, 5) }}, {{ number_format((float) $site->longitude, 5) }}</span>
                <a href="{{ url('/map') }}" class="font-semibold text-forest hover:underline">Buka di peta &rarr;</a>
            </p>
        </section>
    </aside>
</div>
@endsection

@push('scripts')
<script>
    function siteGallery(photos) {
        return {
            photos,
            open: false,
            index: 0,
            show(i) { this.index = i; this.open = true; },
            next() { if (this.open) this.index = (this.index + 1) % this.photos.length; },
            prev() { if (this.open) this.index = (this.index - 1 + this.photos.length) % this.photos.length; },
        };
    }

    document.addEventListener('DOMContentLoaded', () => {
        const el = document.getElementById('site-location-map');
        if (!el || !window.L) return;

        const point = [parseFloat(el.dataset.lat), parseFloat(el.dataset.lng)];
        const map = L.map(el, { scrollWheelZoom: false, zoomControl: false }).setView(point, 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(map);
        L.circleMarker(point, { radius: 9, color: '#fff', weight: 3, fillColor: el.dataset.color, fillOpacity: 1 }).addTo(map);
    });
</script>
@endpush
