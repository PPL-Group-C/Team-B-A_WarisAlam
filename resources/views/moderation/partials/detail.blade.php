@php
    use App\Support\ReportPresenter;
    $report = $selected;
@endphp

<article class="space-y-5 rounded-2xl border border-forest/10 bg-white p-4 shadow-sm sm:p-6" data-selected-report="{{ $report->id }}">

    {{-- ===== Ringkasan + kredibilitas pelapor ===== --}}
    <div class="grid gap-4 rounded-xl bg-mint p-4 sm:grid-cols-[minmax(0,1fr)_14rem]">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-forest px-2.5 py-0.5 text-xs font-semibold text-white">{{ ReportPresenter::code($report->id) }}</span>
                @if ($selectedStatus)
                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $selectedStatus->badgeClasses() }}">
                        <i data-lucide="circle-dot" class="h-3 w-3"></i> Status: {{ $selectedStatus->label() }}
                    </span>
                @else
                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">Status: {{ $report->status }}</span>
                @endif
            </div>
            <p class="mt-3 text-xs text-[#0f2a1d]/70">Waktu Kirim: {{ $report->submitted_at?->translatedFormat('d M Y, H:i') ?? '-' }} WIB</p>
            <h2 class="mt-1 text-xl font-bold text-forest sm:text-2xl">{{ $report->site?->name ?? 'Situs tidak diketahui' }}</h2>
            <p class="mt-1 text-sm text-[#0f2a1d]/70">
                {{ collect([$report->site?->registration_id, $report->site?->heritage_note])->filter()->join(' • ') }}
            </p>
            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                @if ($report->category)
                    <span class="rounded-full bg-white px-2.5 py-1 font-medium text-forest">{{ $report->category->label() }}</span>
                @endif
                <span class="rounded-full px-2.5 py-1 font-semibold {{ ReportPresenter::severityBadge($report->severity) }}">{{ ReportPresenter::severityLabel($report->severity) }}</span>
            </div>
        </div>

        <aside class="rounded-xl bg-white p-3 text-xs" aria-label="Kredibilitas pelapor">
            <p class="text-[#0f2a1d]/60">Kredibilitas Pelapor:</p>
            <div class="mt-2 flex items-center gap-2">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-forest text-xs font-semibold text-white">
                    {{ str($report->user?->name ?? '?')->explode(' ')->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->join('') }}
                </span>
                <div class="min-w-0">
                    <p class="truncate font-semibold">{{ $report->user?->name ?? '-' }}</p>
                    @if ($report->is_anonymous)
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600" title="Nama disembunyikan dari publik, hanya terlihat oleh moderator/instansi/admin">Anonim</span>
                    @endif
                </div>
            </div>
            <dl class="mt-3 grid grid-cols-2 gap-2">
                <div>
                    <dt class="text-[#0f2a1d]/60">Terverifikasi</dt>
                    <dd class="text-base font-bold text-forest" data-testid="reporter-accepted">{{ $reporterStats['accepted'] }}</dd>
                </div>
                <div>
                    <dt class="text-[#0f2a1d]/60">Ditolak</dt>
                    <dd class="text-base font-bold text-red-700" data-testid="reporter-rejected">{{ $reporterStats['rejected'] }}</dd>
                </div>
            </dl>
            <p class="mt-1 text-[10px] text-[#0f2a1d]/50">Dari {{ $reporterStats['total'] }} laporan lain oleh pelapor ini.</p>
        </aside>
    </div>

    {{-- ===== Deskripsi ===== --}}
    <section>
        <h3 class="mb-2 flex items-center gap-2 font-bold"><i data-lucide="file-text" class="h-5 w-5 text-forest"></i> Deskripsi Pelapor</h3>
        <p class="whitespace-pre-line rounded-xl border border-forest/10 p-4 text-sm leading-relaxed">{{ $report->description ?: 'Tanpa deskripsi.' }}</p>
    </section>

    {{-- ===== Galeri foto ===== --}}
    <section x-data="{ zoom: null }">
        <div class="mb-2 flex flex-wrap items-baseline justify-between gap-2">
            <h3 class="flex items-center gap-2 font-bold"><i data-lucide="images" class="h-5 w-5 text-forest"></i> Bukti Foto Lapangan ({{ $report->photos->count() }} Berkas)</h3>
            <span class="text-xs text-[#0f2a1d]/60">Klik foto untuk memperbesar</span>
        </div>

        @if ($report->photos->isEmpty())
            <p class="rounded-xl border border-dashed border-forest/20 p-6 text-center text-sm text-[#0f2a1d]/60">Laporan ini tidak memiliki foto.</p>
        @else
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($report->photos as $i => $photo)
                    @php $url = Storage::disk('public')->url($photo->path); @endphp
                    <button type="button" @click="zoom = @js($url)"
                            class="group relative aspect-[4/3] overflow-hidden rounded-xl bg-mint text-left">
                        <img src="{{ $url }}" alt="Foto #{{ $i + 1 }}" loading="lazy"
                             class="h-full w-full object-cover transition group-hover:scale-105">
                        <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-3 text-[11px] text-white">
                            <span class="mb-1 inline-block rounded bg-forest px-2 py-0.5 font-semibold">Foto #{{ $i + 1 }}</span>
                            @if ($photo->exif_valid)
                                <span class="mb-1 ml-1 inline-block rounded bg-emerald-500 px-2 py-0.5 font-semibold">EXIF Valid</span>
                            @endif
                            <span class="block truncate">{{ number_format($photo->size_kb / 1024, 1, ',', '.') }} MB • {{ $photo->original_name }}</span>
                        </span>
                        <span class="absolute bottom-3 right-3 grid h-7 w-7 place-items-center rounded-full bg-white/90 text-forest"><i data-lucide="zoom-in" class="h-4 w-4"></i></span>
                    </button>
                @endforeach
            </div>

            <div x-cloak x-show="zoom" x-transition.opacity @click="zoom = null" @keydown.escape.window="zoom = null"
                 class="fixed inset-0 z-50 grid place-items-center bg-black/80 p-4" role="dialog" aria-label="Foto diperbesar">
                <img :src="zoom" alt="" class="max-h-[90vh] max-w-full rounded-lg object-contain">
                <button type="button" class="absolute right-4 top-4 rounded-full bg-white px-3 py-1 text-sm font-semibold text-forest">Tutup</button>
            </div>
        @endif
    </section>

    {{-- ===== EXIF & geospasial ===== --}}
    <section class="rounded-xl bg-mint p-4">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h3 class="flex items-center gap-2 font-bold"><i data-lucide="scan-search" class="h-5 w-5 text-forest"></i> Verifikasi EXIF &amp; Geospasial</h3>
            <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $evidence['exif_valid'] > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                EXIF Valid {{ $evidence['exif_valid'] }}/{{ $evidence['photo_total'] }} foto
            </span>
        </div>
        <dl class="grid gap-2 text-sm sm:grid-cols-2">
            @foreach ([
                ['Koordinat Laporan', $evidence['pin']],
                ['Koordinat EXIF Foto', $evidence['exif_coordinate']],
                ['Jarak ke Titik Situs', ReportPresenter::meters($evidence['distance_to_site'])],
                ['Akurasi GPS', $report->gps_accuracy_m ? "±{$report->gps_accuracy_m} meter" : '-'],
                ['Waktu Jepret', $evidence['taken_at']?->translatedFormat('d M Y, H:i') ?? '-'],
                ['Selisih Jepret → Unggah', ReportPresenter::duration($evidence['upload_lag'])],
                ['Perangkat Pemotret', $evidence['device'] ?? '-'],
            ] as [$label, $value])
                <div class="grid grid-cols-[8.5rem_minmax(0,1fr)] gap-2 rounded-lg bg-white px-3 py-2">
                    <dt class="text-[#0f2a1d]/60">{{ $label }}</dt>
                    <dd class="font-semibold">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    {{-- ===== Hasil keputusan sebelumnya ===== --}}
    @if ($report->rejection_reason)
        <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm" data-testid="rejection-reason">
            <h3 class="flex items-center gap-2 font-bold text-red-700"><i data-lucide="ban" class="h-4 w-4"></i> Alasan Penolakan <span class="text-xs font-normal">(terlihat oleh pelapor)</span></h3>
            <p class="mt-1 whitespace-pre-line">{{ $report->rejection_reason }}</p>
        </section>
    @endif
    @if ($report->moderator_note && $selectedStatus !== \App\Enums\ReportStatus::Pending)
        <section class="rounded-xl border border-forest/15 bg-mint/60 p-4 text-sm">
            <h3 class="flex items-center gap-2 font-bold text-forest"><i data-lucide="notebook-pen" class="h-4 w-4"></i> Catatan Kurasi Moderator</h3>
            <p class="mt-1 whitespace-pre-line">{{ $report->moderator_note }}</p>
            @if ($report->moderator)
                <p class="mt-1 text-xs text-[#0f2a1d]/60">— {{ $report->moderator->name }}, {{ $report->moderated_at?->translatedFormat('d M Y, H:i') }}</p>
            @endif
        </section>
    @endif

    {{-- ===== Aksi moderator ===== --}}
    @include('moderation.partials.actions')

    {{-- ===== Riwayat status ===== --}}
    @if ($report->statusHistories->isNotEmpty())
        <section>
            <h3 class="mb-2 flex items-center gap-2 font-bold"><i data-lucide="history" class="h-5 w-5 text-forest"></i> Riwayat Status</h3>
            <ol class="space-y-2 border-l-2 border-mint-200 pl-4 text-sm">
                @foreach ($report->statusHistories as $history)
                    <li>
                        <span class="font-semibold">{{ $history->from_status?->label() ?? '-' }} → {{ $history->to_status->label() }}</span>
                        <span class="text-xs text-[#0f2a1d]/60">• {{ $history->changedBy?->name ?? 'Sistem' }} • {{ $history->created_at->translatedFormat('d M Y, H:i') }}</span>
                        @if ($history->note)
                            <p class="text-xs text-[#0f2a1d]/80">{{ $history->note }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>
        </section>
    @endif
</article>
