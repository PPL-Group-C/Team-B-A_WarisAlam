@extends('layouts.app')

@section('title', 'Lapor Kerusakan — WarisAlam')

@push('head')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/exifr@7.1.3/dist/lite.umd.js"></script>
@endpush

@section('content')
<form method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data"
      x-data="reportForm(@js($initial))"
      @keydown.enter="if ($event.target.tagName === 'INPUT') $event.preventDefault()"
      class="space-y-8">
    @csrf

    @if (session('status'))
        <div class="rounded-lg bg-forest px-5 py-3 text-sm text-white" role="status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800" role="alert">
            <p class="mb-1 font-semibold">Laporan belum bisa disimpan:</p>
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Stepper --}}
    <ol class="grid grid-cols-2 gap-4 rounded-2xl bg-white p-4 shadow-sm lg:grid-cols-4">
        <template x-for="(s, i) in steps" :key="i">
            <li class="flex items-center gap-3" :class="s.state === 'todo' && 'opacity-60'">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full text-sm font-semibold"
                      :class="s.state === 'todo' ? 'bg-mint text-forest/60' : 'bg-forest text-white'"
                      x-text="s.state === 'done' ? '✓' : i + 1"></span>
                <span class="leading-tight">
                    <span class="block text-[11px] font-semibold text-forest" x-text="`LANGKAH ${String(i + 1).padStart(2, '0')}`"></span>
                    <span class="block text-sm font-semibold" x-text="s.title"></span>
                    <span class="block text-[11px] text-forest" x-text="s.note"></span>
                </span>
            </li>
        </template>
    </ol>

    {{-- Bagian 1 --}}
    <section>
        <x-section-bar icon="map-pin" title="Bagian 1: Identifikasi Situs & Geospasial">AUTO_GEO_V3</x-section-bar>

        <label for="site-search" class="mb-2 block text-sm font-medium">Pilih Situs Cagar Budaya / Warisan Alam <span class="text-red-600">*</span></label>
        <div class="relative" @click.outside="siteOpen = false">
            <i data-lucide="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-forest"></i>
            <input id="site-search" type="text" x-model="siteQuery" autocomplete="off"
                   @focus="siteOpen = true" @input="siteOpen = true; siteId = null"
                   placeholder="Ketik nama situs…"
                   class="w-full rounded-lg bg-mint py-3 pl-11 pr-4 text-sm outline-none focus:ring-2 focus:ring-forest">
            <input type="hidden" name="heritage_site_id" :value="siteId">
            <ul x-show="siteOpen && filteredSites.length" x-cloak
                class="absolute z-20 mt-1 w-full overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-forest/10">
                <template x-for="s in filteredSites" :key="s.id">
                    <li><button type="button" @click="pickSite(s)" class="block w-full px-4 py-2.5 text-left text-sm hover:bg-mint" x-text="s.name"></button></li>
                </template>
            </ul>
        </div>
        <p class="mt-1.5 text-xs" x-show="site" x-cloak>
            ID Registrasi Nasional: <span class="text-forest" x-text="site?.registration_id"></span>
            <span x-text="site?.heritage_note ? `(${site.heritage_note})` : ''"></span>
        </p>

        <div class="mt-5 space-y-3 rounded-2xl bg-mint p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <button type="button" @click="locate()"
                        class="inline-flex items-center gap-2 rounded-lg bg-forest px-4 py-2.5 text-sm font-semibold text-white hover:bg-forest-700">
                    <i data-lucide="locate-fixed" class="h-4 w-4"></i>Ambil Lokasi GPS Perangkat Saat Ini
                </button>
                <p class="flex items-center gap-2 text-sm">
                    <span class="h-2 w-2 rounded-full" :class="gps.dot"></span><span x-text="gps.text"></span>
                </p>
            </div>

            <div class="flex flex-wrap justify-between gap-2 rounded-lg bg-white px-4 py-3 text-sm">
                <div>
                    <p class="text-xs">Koordinat Terkunci:</p>
                    <p class="font-semibold text-forest" x-text="coordLabel"></p>
                </div>
                <div class="text-right">
                    <p class="text-xs">Radius Toleransi:</p>
                    <p class="font-semibold text-forest" x-text="accuracy ? `±${accuracy} meter` : 'Pin manual'"></p>
                </div>
            </div>

            <div class="relative isolate overflow-hidden rounded-lg">
                <div x-ref="map" class="h-72 w-full bg-white"></div>
                <div class="absolute left-3 top-3 z-[1000] flex gap-1 rounded-md bg-white p-1 text-xs shadow">
                    <button type="button" @click="basemap = 'sat'" class="rounded px-2.5 py-1.5 font-medium"
                            :class="basemap === 'sat' ? 'bg-forest text-white' : ''">Satelit Resolusi Tinggi</button>
                    <button type="button" @click="basemap = 'topo'" class="rounded px-2.5 py-1.5 font-medium"
                            :class="basemap === 'topo' ? 'bg-forest text-white' : ''">Topografi Cagar</button>
                </div>
                <div class="absolute right-3 top-3 z-[1000] flex flex-col gap-1">
                    <button type="button" @click="zoom(1)" aria-label="Perbesar" class="grid h-9 w-9 place-items-center rounded-md bg-white shadow"><i data-lucide="plus" class="h-4 w-4"></i></button>
                    <button type="button" @click="zoom(-1)" aria-label="Perkecil" class="grid h-9 w-9 place-items-center rounded-md bg-white shadow"><i data-lucide="minus" class="h-4 w-4"></i></button>
                </div>
                <p class="absolute inset-x-3 bottom-3 z-[1000] flex justify-between rounded bg-white/90 px-3 py-1.5 text-xs">
                    <span x-text="site ? `Situs: ${site.name}` : 'Pilih situs untuk memusatkan peta'" class="truncate"></span>
                    <span class="shrink-0 font-semibold text-forest">Ketuk Peta untuk Geser Pin</span>
                </p>
            </div>

            <p class="flex gap-2 rounded-lg bg-mint-200/60 px-3 py-2 text-xs">
                <i data-lucide="shield-check" class="mt-0.5 h-4 w-4 shrink-0 text-forest"></i>
                <span><b class="text-forest">Perlindungan Bukti Forensik:</b> Metadata EXIF foto otomatis dibaca sistem untuk validasi silang antara timestamp kamera dan koordinat satelit yang terlapor.</span>
            </p>
        </div>
    </section>

    {{-- Bagian 2 --}}
    <section>
        <x-section-bar icon="images" title="Bagian 2: Unggah Bukti Lapangan (Multi-Foto)">
            <span x-text="`${photos.length} / 5 FOTO TERUNGGAH`"></span>
        </x-section-bar>

        <label class="flex cursor-pointer flex-col items-center gap-2 rounded-2xl bg-mint px-6 py-10 text-center transition has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-forest"
               :class="dragging && 'ring-2 ring-forest'"
               @dragover.prevent="dragging = true" @dragleave="dragging = false"
               @drop.prevent="dragging = false; addFiles($event.dataTransfer.files)">
            <span class="grid h-12 w-12 place-items-center rounded-full bg-mint-200 text-forest"><i data-lucide="camera" class="h-6 w-6"></i></span>
            <span class="text-base font-semibold text-forest">Seret foto bukti atau ambil langsung kamera lapangan</span>
            <span class="text-xs">Mendukung format JPG atau PNG hingga 10MB per berkas. Maksimal 5 foto bukti dokumentasi.</span>
            <span class="mt-1 flex flex-wrap justify-center gap-2 text-xs"><span class="rounded bg-white/70 px-2 py-1">Geotagging Aktif</span><span class="rounded bg-white/70 px-2 py-1">Otomatis Ekstraksi EXIF</span><span class="rounded bg-white/70 px-2 py-1">Anti-Manipulasi Metadata</span></span>
            <input x-ref="picker" type="file" name="photos[]" multiple accept="image/jpeg,image/png" class="sr-only"
                   @change="addFiles($event.target.files)">
        </label>
        <p class="mt-2 text-sm text-red-700" x-show="photoError" x-text="photoError" x-cloak></p>

        <div class="mt-4 rounded-lg bg-mint px-4 py-2 text-xs font-medium text-forest" x-show="photos.length" x-cloak>
            <div class="flex justify-between"><span>Penyelarasan Berkas Media: 100% Selesai</span><span x-text="`${photos.length} Berkas Diproses (Total ${totalMb} MB)`"></span></div>
            <div class="mt-1 h-1.5 rounded-full bg-white"><div class="h-1.5 w-full rounded-full bg-forest"></div></div>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <template x-for="(p, i) in photos" :key="p.key">
                <figure class="rounded-2xl bg-mint p-2">
                    <div class="relative overflow-hidden rounded-xl">
                        <img :src="p.url" :alt="p.name" class="h-52 w-full object-cover">
                        <span class="absolute left-2 top-2 flex items-center gap-1.5 rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-semibold"
                              :class="photoState(p).ok ? 'text-forest' : 'text-red-700'">
                            <span class="h-1.5 w-1.5 rounded-full" :class="photoState(p).ok ? 'bg-forest' : 'bg-red-600'"></span>
                            <span x-text="photoState(p).label"></span>
                        </span>
                        <button type="button" @click="removePhoto(i)" aria-label="Hapus foto"
                                class="absolute right-2 top-2 grid h-7 w-7 place-items-center rounded-full bg-white text-red-600 shadow">&times;</button>
                        <span class="absolute inset-x-2 bottom-2 rounded bg-black/50 px-2 py-1 text-[11px] text-white"
                              x-text="p.gps ? `GPS: ${p.gps.latitude.toFixed(6)}, ${p.gps.longitude.toFixed(6)}` : 'GPS: tidak ditemukan di metadata'"></span>
                    </div>
                    <figcaption class="px-1 pb-1 pt-2 text-xs">
                        <span class="flex justify-between gap-2 text-sm font-semibold"><span class="truncate" x-text="p.name"></span><span class="shrink-0 font-normal" x-text="`${p.size} MB`"></span></span>
                        <span class="text-forest" x-text="p.takenAt"></span>
                    </figcaption>
                </figure>
            </template>
        </div>
    </section>

    {{-- Bagian 3 --}}
    <section>
        <x-section-bar icon="shield-alert" title="Bagian 3: Klasifikasi & Tingkat Keparahan">
            <span x-text="`LEVEL SEVERITAS ${severity}`"></span>
        </x-section-bar>

        <p class="mb-3 text-sm font-medium">Jenis Kerusakan / Kategori Ancaman Cagar <span class="text-red-600">*</span></p>
        <div class="grid gap-3 md:grid-cols-3">
            @foreach ($categories as $c)
                <label class="cursor-pointer rounded-xl p-4 transition has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-forest has-[:focus-visible]:ring-offset-2"
                       :class="category === '{{ $c['value'] }}' ? 'bg-forest text-white' : 'bg-mint text-forest hover:bg-mint-200'">
                    <input type="radio" name="category" value="{{ $c['value'] }}" x-model="category" class="sr-only">
                    <span class="flex items-center gap-2 font-semibold">
                        <span class="grid h-3.5 w-3.5 place-items-center rounded-full border border-current">
                            <span class="h-1.5 w-1.5 rounded-full bg-current" x-show="category === '{{ $c['value'] }}'"></span>
                        </span>
                        <i data-lucide="{{ $c['icon'] }}" class="h-4 w-4"></i>{{ $c['label'] }}
                    </span>
                    <span class="mt-1 block pl-[22px] text-xs opacity-80">{{ $c['description'] }}</span>
                </label>
            @endforeach
        </div>

        <div class="mt-6 rounded-2xl bg-mint p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="font-semibold">Skala Tingkat Keparahan (Severity Scale 1 - 5)</h3>
                    <p class="text-xs">Panduan Standar Mitigasi Cagar Budaya Kemendikbudristek &amp; KLHK</p>
                </div>
                <span class="rounded-full bg-forest px-3 py-1 text-xs font-semibold text-white"
                      x-text="`Skala ${severity}: ${level.name}`"></span>
            </div>

            <input type="range" name="severity" min="1" max="5" step="1" x-model.number="severity"
                   aria-label="Tingkat keparahan" class="mt-5 h-2 w-full cursor-pointer accent-forest">
            <div class="mt-2 grid grid-cols-5 text-xs">
                <template x-for="l in levels" :key="l.value">
                    <span :class="[l.value === severity ? 'font-bold text-forest' : '', l.value === 1 ? 'text-left' : l.value === 5 ? 'text-right' : 'text-center']"
                          x-text="`${l.value} (${l.name})`"></span>
                </template>
            </div>

            <div class="mt-4 flex gap-3 rounded-lg bg-white p-4">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-md bg-mint text-forest"><i data-lucide="triangle-alert" class="h-5 w-5"></i></span>
                <div class="text-sm">
                    <p class="font-semibold" x-text="`Level ${level.value}: ${level.name} (${level.title})`"></p>
                    <p class="mt-1 text-xs leading-relaxed" x-text="level.description"></p>
                </div>
            </div>
        </div>
    </section>

    {{-- Bagian 4 --}}
    <section>
        <x-section-bar icon="notebook-pen" title="Bagian 4: Deskripsi Rinci & Privasi">FORM_VERIF_READY</x-section-bar>

        <div class="mb-2 flex justify-between text-sm font-medium">
            <label for="description">Kronologi &amp; Rincian Temuan Lapangan <span class="text-red-600">*</span></label>
            <span class="text-xs" x-text="`${description.length} / 1000 Karakter`"></span>
        </div>
        <textarea id="description" name="description" x-model="description" rows="4" maxlength="1000"
                  class="w-full rounded-xl bg-mint p-4 text-sm outline-none focus:ring-2 focus:ring-forest"
                  placeholder="Contoh: Ditemukan coretan spidol permanen pada relief bagian selatan. Terindikasi dilakukan oleh pengunjung pada pagi hari ini."></textarea>
        <p class="mt-2 text-xs">Saran: Hindari opini subjektif. Fokus pada bentuk kerusakan fisik dan petunjuk visual nyata.</p>

        <div class="mt-4 space-y-4 rounded-2xl bg-mint p-5 text-sm">
            <label class="flex gap-3">
                <input type="hidden" name="is_anonymous" value="0">
                <input type="checkbox" name="is_anonymous" value="1" x-model="anonymous" class="mt-1 h-4 w-4 shrink-0 accent-forest">
                <span><b class="block">Kirim sebagai Laporan Anonim (Privasi Terproteksi)</b>
                    <span class="text-xs">Nama akun Anda disamarkan dari portal peninjau publik dan forum warga. Identitas asli hanya dapat diakses secara terenkripsi oleh auditor BPK/BKSDA resmi untuk kebutuhan verifikasi BAP.</span></span>
            </label>
            <label class="flex gap-3">
                <input type="checkbox" name="declaration" value="1" x-model="declared" class="mt-1 h-4 w-4 shrink-0 accent-forest">
                <span><b class="block">Pernyataan Kebenaran Data Laporan <span class="text-red-600">*</span></b>
                    <span class="text-xs">Saya menyatakan informasi laporan ini dibuat dengan sejujur-jujurnya sesuai fakta riil di cagar budaya. Laporan palsu atau rekayasa bukti dikenakan sanksi suspensi reputasi dan ketentuan UU No. 11/2010.</span></span>
            </label>
        </div>
    </section>

    <div class="flex flex-wrap items-center justify-between gap-4 pb-4">
        <button type="submit" name="intent" value="draft"
                class="inline-flex items-center gap-2 rounded-lg bg-mint px-5 py-3 text-sm font-semibold text-forest hover:bg-mint-200">
            <i data-lucide="bookmark" class="h-4 w-4"></i>Simpan Draf Lapangan
        </button>
        <button type="submit" name="intent" value="submit"
                class="inline-flex items-center gap-2 rounded-lg bg-forest px-6 py-3 text-sm font-semibold text-white hover:bg-forest-700">
            Kirim Laporan Kerusakan Sekarang<i data-lucide="send" class="h-4 w-4"></i>
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('reportForm', (cfg) => {
        // Leaflet objects live outside Alpine state: reactive proxies break them.
        let map, marker;
        const tiles = {};
        const rad = Math.PI / 180;
        const meters = (la1, ln1, la2, ln2) => {
            const h = Math.sin((la2 - la1) * rad / 2) ** 2
                + Math.cos(la1 * rad) * Math.cos(la2 * rad) * Math.sin((ln2 - ln1) * rad / 2) ** 2;
            return 12742000 * Math.asin(Math.sqrt(h));
        };
        const old = cfg.old ?? {};

        return {
            sites: cfg.sites,
            levels: cfg.levels,
            siteId: old.heritage_site_id ? Number(old.heritage_site_id) : null,
            siteQuery: '', siteOpen: false,
            lat: old.latitude ?? '', lng: old.longitude ?? '', accuracy: old.gps_accuracy_m ?? '',
            gpsState: 'idle', basemap: 'sat',
            photos: [], photoError: '', dragging: false,
            category: old.category ?? '',
            severity: Number(old.severity ?? 3),
            description: old.description ?? '',
            anonymous: old.is_anonymous === '1',
            declared: false,

            get site() { return this.sites.find(s => s.id === this.siteId) ?? null; },
            get level() { return this.levels[this.severity - 1]; },
            get filteredSites() {
                const q = this.siteQuery.toLowerCase();
                return this.sites.filter(s => s.name.toLowerCase().includes(q)).slice(0, 8);
            },
            get totalMb() { return (this.photos.reduce((n, p) => n + p.bytes, 0) / 1048576).toFixed(1); },
            get coordLabel() {
                if (this.lat === '') return 'Belum dipilih';
                const la = Number(this.lat), ln = Number(this.lng);
                return `${Math.abs(la).toFixed(4)}° ${la < 0 ? 'S' : 'N'}, ${Math.abs(ln).toFixed(4)}° ${ln < 0 ? 'W' : 'E'}`;
            },
            get gps() {
                return {
                    idle: { dot: 'bg-slate-400', text: 'GPS belum diambil' },
                    wait: { dot: 'bg-amber-500', text: 'Mencari sinyal GPS…' },
                    ok: { dot: 'bg-forest', text: 'Sinyal GPS Optimal' },
                    manual: { dot: 'bg-forest', text: 'Pin diatur manual' },
                    error: { dot: 'bg-red-600', text: 'GPS gagal. Ketuk peta untuk memilih titik.' },
                }[this.gpsState];
            },
            get steps() {
                const done = [!!(this.siteId && this.lat !== ''), this.photos.length > 0, !!this.category,
                              this.description.trim().length >= 20 && this.declared];
                const current = done.indexOf(false);
                const meta = [
                    ['Lokasi & Situs Terkena', 'Terkonfirmasi'],
                    ['Unggah Bukti Kerusakan', 'Terunggah'],
                    ['Klasifikasi & Tingkat', 'Prioritas Tindakan'],
                    ['Pratinjau & Kirim', 'Verifikasi BPK RI'],
                ];
                return meta.map(([title, doneNote], i) => ({
                    title,
                    state: done[i] ? 'done' : i === current ? 'current' : 'todo',
                    note: done[i] ? doneNote : i === current ? 'Sedang Diisi' : doneNote === 'Terunggah' ? 'Bukti Foto' : doneNote,
                }));
            },

            init() {
                this.siteQuery = this.site?.name ?? '';
                const start = this.lat !== '' ? [Number(this.lat), Number(this.lng)] : [-2.5, 118];

                map = L.map(this.$refs.map, { zoomControl: false }).setView(start, this.lat !== '' ? 17 : 5);
                tiles.sat = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
                    { maxZoom: 19, attribution: 'Tiles &copy; Esri' });
                tiles.topo = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
                    { maxZoom: 17, attribution: '&copy; OpenTopoMap, &copy; OpenStreetMap' });
                tiles.sat.addTo(map);

                marker = L.marker(start, { draggable: true })
                    .bindTooltip('Titik Insiden Terpilih', { permanent: true, direction: 'bottom' });
                if (this.lat !== '') marker.addTo(map);

                map.on('click', e => this.setPin(e.latlng.lat, e.latlng.lng, null, true));
                marker.on('dragend', () => { const p = marker.getLatLng(); this.setPin(p.lat, p.lng, null, true); });

                this.$watch('basemap', v => {
                    map.removeLayer(v === 'sat' ? tiles.topo : tiles.sat);
                    tiles[v].addTo(map);
                });
                setTimeout(() => map.invalidateSize(), 200);
            },

            setPin(lat, lng, zoom = null, manual = false) {
                this.lat = Number(lat).toFixed(6);
                this.lng = Number(lng).toFixed(6);
                if (manual) { this.accuracy = ''; this.gpsState = 'manual'; }
                marker.setLatLng([lat, lng]).addTo(map);
                if (zoom) map.setView([lat, lng], zoom);
            },
            zoom(d) { d > 0 ? map.zoomIn() : map.zoomOut(); },

            pickSite(s) {
                this.siteId = s.id;
                this.siteQuery = s.name;
                this.siteOpen = false;
                this.setPin(Number(s.latitude), Number(s.longitude), 17, true);
            },

            locate() {
                if (!navigator.geolocation) { this.gpsState = 'error'; return; }
                this.gpsState = 'wait';
                navigator.geolocation.getCurrentPosition(
                    p => {
                        this.setPin(p.coords.latitude, p.coords.longitude, 18);
                        this.accuracy = Math.round(p.coords.accuracy);
                        this.gpsState = 'ok';
                    },
                    () => { this.gpsState = 'error'; },
                    { enableHighAccuracy: true, timeout: 12000 },
                );
            },

            async addFiles(list) {
                this.photoError = '';
                for (const file of Array.from(list)) {
                    if (this.photos.length >= 5) { this.photoError = 'Maksimal 5 foto bukti.'; break; }
                    if (!['image/jpeg', 'image/png'].includes(file.type)) { this.photoError = `${file.name}: format harus JPG atau PNG.`; continue; }
                    if (file.size > 10 * 1048576) { this.photoError = `${file.name}: ukuran melebihi 10 MB.`; continue; }

                    let gps = null;
                    try { gps = (await exifr.gps(file)) ?? null; } catch (e) { /* no EXIF */ }

                    this.photos.push({
                        key: crypto.randomUUID(), file, gps,
                        url: URL.createObjectURL(file), name: file.name,
                        bytes: file.size, size: (file.size / 1048576).toFixed(1),
                        takenAt: new Date(file.lastModified).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }),
                    });
                }
                this.syncInput();
            },
            removePhoto(i) {
                URL.revokeObjectURL(this.photos[i].url);
                this.photos.splice(i, 1);
                this.syncInput();
            },
            // Keep the real <input type="file"> in sync so the browser submits exactly these files.
            syncInput() {
                const dt = new DataTransfer();
                this.photos.forEach(p => dt.items.add(Alpine.raw(p).file));
                this.$refs.picker.files = dt.files;
            },
            // Advisory only: the server re-validates EXIF on submit.
            photoState(p) {
                if (!p.gps) return { ok: false, label: 'Tanpa GPS' };
                if (this.lat === '') return { ok: false, label: 'Pilih titik dulu' };
                const d = meters(p.gps.latitude, p.gps.longitude, Number(this.lat), Number(this.lng));
                return d <= 200 ? { ok: true, label: 'EXIF Valid' } : { ok: false, label: `Jauh dari titik (${Math.round(d)} m)` };
            },
        };
    });
});
</script>
@endpush
