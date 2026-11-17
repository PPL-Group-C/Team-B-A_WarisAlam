@php
    use App\Enums\ReportStatus;
    use App\Support\ReportPresenter;

    $thumb = $report->photos->first();
    $similar = $candidateMap[$report->id] ?? [];
    $status = $report->statusEnum();
    $isPending = $status === ReportStatus::Pending;
    $initials = str($report->user?->name ?? '?')->explode(' ')->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->join('');

    // Kotak "Terdeteksi Duplikat" hanya di kartu terpilih; datanya dari panel detail
    // ($selected, $candidates) supaya tidak menambah query per kartu.
    $match = $active && $isPending ? ($candidates->first() ?? null) : null;
    $detail = $active ? $selected : null;
@endphp

<a href="{{ request()->fullUrlWithQuery(['report' => $report->id]) }}"
   data-report-card="{{ $report->id }}"
   @if ($active) aria-current="true" @endif
   class="block rounded-xl border bg-white p-4 shadow-sm transition hover:border-forest/40
          {{ $active ? 'border-forest/30 border-l-4 border-l-forest' : 'border-forest/10' }}">

    {{-- Baris atas: ID + kategori (kiri), level + waktu (kanan) --}}
    <div class="flex items-start justify-between gap-3">
        <div class="flex min-w-0 flex-wrap items-center gap-1.5">
            <span class="text-xs font-semibold {{ $report->severity >= 4 ? 'text-red-700' : 'text-forest' }}">{{ ReportPresenter::code($report->id) }}</span>
            @if ($report->category)
                <span class="rounded-full bg-leaf px-2 py-0.5 text-[10px] font-semibold text-leaf-ink">{{ $report->category->label() }}</span>
            @endif
            @if ($report->duplicate_of_id)
                <span class="inline-flex items-center gap-1 rounded-full bg-leaf px-2 py-0.5 text-[10px] font-semibold text-sage">
                    <i data-lucide="link" class="h-3 w-3"></i> Duplikat dari {{ ReportPresenter::code($report->duplicate_of_id) }}
                </span>
            @elseif ($similar)
                <span class="rounded-full bg-leaf px-2 py-0.5 text-[10px] font-semibold text-sage" data-testid="candidate-badge">Kandidat Duplikat</span>
            @endif
            @if ($status && ! $isPending)
                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $status->badgeClasses() }}">{{ $status->label() }}</span>
            @endif
        </div>
        <div class="flex shrink-0 flex-col items-end gap-1">
            <span class="inline-flex items-center gap-1 rounded px-2 py-0.5 text-[10px] font-bold {{ ReportPresenter::severityBadge($report->severity) }}">
                @if ($report->severity >= 4)<i data-lucide="triangle-alert" class="h-3 w-3"></i>@endif
                {{ ReportPresenter::severityLabel($report->severity) }}
            </span>
            <span class="whitespace-nowrap text-[11px] text-[#0f2a1d]/60" title="{{ $report->submitted_at?->translatedFormat('d M Y, H:i') }}">
                {{ $report->submitted_at?->diffForHumans() ?? '-' }}
            </span>
        </div>
    </div>

    {{-- Isi: situs + ringkasan, thumbnail di kanan (kecuali kartu terpilih yang punya kotak duplikat) --}}
    <div class="mt-1 flex gap-3">
        <div class="min-w-0 flex-1">
            <p class="truncate font-semibold">{{ $report->site?->name ?? 'Situs tidak diketahui' }}</p>
            <p class="line-clamp-2 text-xs text-[#0f2a1d]/70">{{ $report->description ?: 'Tanpa deskripsi.' }}</p>
        </div>
        @if ($thumb && ! $match)
            <img src="{{ Storage::disk('public')->url($thumb->path) }}" alt="Foto {{ ReportPresenter::code($report->id) }}"
                 loading="lazy" onerror="this.style.visibility='hidden'"
                 class="h-14 w-14 shrink-0 rounded-lg bg-mint object-cover">
        @endif
    </div>

    @if ($match)
        <div class="mt-3 flex gap-3 rounded-lg bg-mint p-2.5" data-testid="card-duplicate-box">
            @if ($thumb)
                <img src="{{ Storage::disk('public')->url($thumb->path) }}" alt="" loading="lazy" onerror="this.style.visibility='hidden'"
                     class="h-14 w-14 shrink-0 rounded-md bg-white object-cover">
            @endif
            <div class="min-w-0 text-[11px]">
                <p class="flex items-center gap-1 text-sm font-bold text-red-700"><i data-lucide="copy" class="h-4 w-4"></i> Terdeteksi Duplikat</p>
                <p class="text-[#0f2a1d]/80">
                    Cocok dengan {{ ReportPresenter::code($match['report']->id) }} dalam radius {{ ReportPresenter::meters($match['distance']) }}
                    &amp; selisih {{ ReportPresenter::duration(abs($match['seconds'])) }}.
                </p>
                <p class="mt-1 flex flex-wrap gap-x-3 gap-y-0.5 text-forest">
                    @if ($detail?->gps_accuracy_m)
                        <span class="inline-flex items-center gap-1"><i data-lucide="locate-fixed" class="h-3 w-3"></i> GPS Valid (±{{ $detail->gps_accuracy_m }}m)</span>
                    @endif
                    @if ($detail?->photos->contains('exif_valid', true))
                        <span class="inline-flex items-center gap-1"><i data-lucide="image" class="h-3 w-3"></i> EXIF Asli</span>
                    @endif
                </p>
            </div>
        </div>
    @endif

    {{-- Baris bawah: pelapor (kiri), status/tautan (kanan) --}}
    <div class="mt-3 flex items-center gap-2 text-xs">
        <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-forest text-[10px] font-semibold text-white">{{ $initials }}</span>
        <span class="min-w-0 truncate font-medium">{{ $report->user?->name ?? '-' }}</span>
        @if ($report->is_anonymous)
            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600" title="Nama disembunyikan dari publik">Anonim</span>
        @endif
        @if ($report->confirm_count || $report->dispute_count)
            <span class="inline-flex shrink-0 items-center gap-1 text-[10px] text-[#0f2a1d]/70" data-testid="confirmation-badge"
                  title="{{ $report->confirm_count }} warga mengonfirmasi, {{ $report->dispute_count }} membantah">
                <i data-lucide="users" class="h-3 w-3"></i>
                {{ $report->confirm_count }} konfirmasi
                @if ($report->dispute_count)
                    • <span class="{{ $report->dispute_count > $report->confirm_count ? 'font-semibold text-red-700' : '' }}">{{ $report->dispute_count }} bantahan</span>
                @endif
            </span>
        @endif

        <span class="ml-auto shrink-0 whitespace-nowrap">
            @if ($active)
                <span class="rounded bg-mint-100 px-2 py-0.5 text-[11px] font-bold text-forest">Sedang Dinilai →</span>
            @elseif ($similar && ! $report->duplicate_of_id)
                <span class="inline-flex items-center gap-1 text-[11px] font-medium text-sage">
                    <i data-lucide="link" class="h-3 w-3"></i> Mirip {{ ReportPresenter::code($similar[0]) }}@if (count($similar) > 1) +{{ count($similar) - 1 }}@endif
                </span>
            @elseif ($isPending)
                <span class="font-semibold text-forest">Siap Kurasi</span>
            @endif
        </span>
    </div>
</a>
