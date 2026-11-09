@extends('moderation.layout')

@section('title', 'Dasbor Moderasi')

@section('content')
@php
    use App\Support\ReportPresenter;
@endphp

{{-- ===== Header mint + statistik ===== --}}
<section class="bg-mint">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <nav class="flex flex-wrap items-center gap-2 text-sm">
                <span class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-1 font-semibold text-forest">
                    <i data-lucide="shield-check" class="h-4 w-4"></i> Modul Kurasi F08
                </span>
                <i data-lucide="chevron-right" class="h-4 w-4 text-forest/50"></i>
                <span>Antrean Validasi Sains Warga</span>
            </nav>
            <a href="{{ request()->fullUrl() }}"
               class="inline-flex items-center gap-2 rounded-full border border-forest/20 bg-white px-4 py-2 text-sm font-medium text-forest hover:bg-mint-200">
                <i data-lucide="refresh-cw" class="h-4 w-4"></i> Muat Ulang Antrean
            </a>
        </div>

        <div class="mt-4 grid gap-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
            <div>
                <h1 class="text-2xl font-bold text-forest sm:text-3xl">Dasbor Moderasi Laporan Sains Warga (F08)</h1>
                <p class="mt-2 max-w-2xl text-sm text-[#0f2a1d]/80">
                    Kurasi bukti lapangan, verifikasi tingkat keparahan, dan eliminasi duplikasi laporan
                    sebelum diteruskan ke Balai Pelestarian Kebudayaan (BPK) &amp; BKSDA.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ([
                    ['Menunggu Review', $stats['pending'], $stats['emergency'] > 0 ? "({$stats['emergency']} Darurat)" : null, 'clipboard-clock', 'text-red-600'],
                    ['Terverifikasi Hari Ini', $stats['verified_today'], 'Laporan', 'badge-check', 'text-forest/70'],
                    ['Terdeteksi Duplikat', $stats['duplicates'] ?? '—', $stats['duplicates'] !== null ? 'Laporan' : null, 'copy', 'text-forest/70'],
                    ['Rerata Respon', $stats['avg_response_hours'] !== null ? number_format($stats['avg_response_hours'], 1, ',', '.') : '—', 'Jam', 'timer', 'text-forest/70'],
                ] as [$label, $value, $suffix, $icon, $suffixClass])
                    <div class="min-w-[8.5rem] rounded-xl bg-white p-3 shadow-sm" data-stat="{{ \Illuminate\Support\Str::slug($label) }}">
                        <div class="flex items-start justify-between gap-2 text-[11px] leading-tight">
                            <span>{{ $label }}</span>
                            <i data-lucide="{{ $icon }}" class="h-4 w-4 shrink-0 text-forest"></i>
                        </div>
                        <p class="mt-2 flex items-baseline gap-1">
                            <span class="text-2xl font-bold text-forest">{{ $value }}</span>
                            @if ($suffix)
                                <span class="text-[11px] {{ $suffixClass }}">{{ $suffix }}</span>
                            @endif
                        </p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ===== Bar filter ===== --}}
        <form method="GET" action="{{ route('moderation.index') }}"
              class="mt-5 grid gap-2 rounded-xl bg-white p-2 shadow-sm sm:grid-cols-2 lg:grid-cols-[minmax(0,2fr)_repeat(4,minmax(0,1fr))_auto]">
            <label class="relative block">
                <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-forest/60"></i>
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}"
                       placeholder="Cari ID laporan, nama situs (mis: Prambanan)…"
                       class="w-full rounded-lg bg-mint py-2 pl-9 pr-3 text-sm outline-none focus:ring-2 focus:ring-forest">
            </label>
            <select name="status" aria-label="Status" class="rounded-lg bg-mint px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-forest">
                @foreach ($statusOptions as $status)
                    <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                @endforeach
                <option value="all" @selected($filters['status'] === 'all')>Semua status</option>
            </select>
            <select name="category" aria-label="Kategori" class="rounded-lg bg-mint px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-forest">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->value }}" @selected(($filters['category'] ?? null) === $category->value)>{{ $category->label() }}</option>
                @endforeach
            </select>
            <select name="severity" aria-label="Severity" class="rounded-lg bg-mint px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-forest">
                <option value="">Semua level</option>
                @foreach (range(1, 5) as $level)
                    <option value="{{ $level }}" @selected((int) ($filters['severity'] ?? 0) === $level)>{{ ReportPresenter::severityLabel($level) }}</option>
                @endforeach
            </select>
            <select name="sort" aria-label="Urutkan" class="rounded-lg bg-mint px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-forest">
                @foreach ($sorts as $value => $label)
                    <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button class="flex-1 rounded-lg bg-forest px-4 py-2 text-sm font-semibold text-white hover:bg-forest-700">Terapkan</button>
                <a href="{{ route('moderation.index') }}" class="rounded-lg px-3 py-2 text-sm text-forest hover:bg-mint" title="Reset filter">Reset</a>
            </div>
        </form>
    </div>
</section>

<main class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
    @foreach (['success' => 'bg-emerald-50 text-emerald-800 border-emerald-200', 'error' => 'bg-red-50 text-red-700 border-red-200'] as $key => $classes)
        @if (session($key))
            <div class="mb-4 rounded-lg border px-4 py-3 text-sm {{ $classes }}" role="status">{{ session($key) }}</div>
        @endif
    @endforeach

    <div class="grid gap-6 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
        {{-- ===== Kolom kiri: antrean ===== --}}
        <section aria-labelledby="queue-title">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h2 id="queue-title" class="flex items-center gap-2 font-bold">
                    Antrean Laporan Lapangan
                    <span class="rounded-full bg-mint-200 px-2 py-0.5 text-xs font-semibold text-forest" data-testid="queue-total">{{ $queue->total() }}</span>
                </h2>
                <span class="text-xs text-[#0f2a1d]/60">Urutkan: {{ $sorts[$filters['sort']] }}</span>
            </div>

            <div class="space-y-3">
                @forelse ($queue as $report)
                    @include('moderation.partials.queue-card', ['report' => $report, 'active' => $selected?->id === $report->id])
                @empty
                    <div class="rounded-xl border border-dashed border-forest/20 bg-white p-8 text-center text-sm text-[#0f2a1d]/60">
                        <i data-lucide="inbox" class="mx-auto mb-2 h-8 w-8 text-forest/40"></i>
                        Tidak ada laporan yang cocok dengan filter ini.
                    </div>
                @endforelse
            </div>

            <div class="mt-4">{{ $queue->onEachSide(1)->links() }}</div>
        </section>

        {{-- ===== Kolom kanan: detail ===== --}}
        <section aria-label="Detail laporan">
            @if ($selected)
                @include('moderation.partials.detail')
            @else
                <div class="rounded-2xl border border-forest/10 bg-white p-10 text-center text-sm text-[#0f2a1d]/60 shadow-sm">
                    Pilih laporan dari antrean untuk melihat detailnya.
                </div>
            @endif
        </section>
    </div>
</main>
@endsection
