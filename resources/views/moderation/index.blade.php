@extends('moderation.layout')

@section('title', 'Dasbor Moderasi')

@section('content')
@php
    use App\Support\ReportPresenter;
@endphp

{{-- ===== Header mint + statistik ===== --}}
{{-- Figma: banner mint selebar kontainer 1280px (bukan selebar layar), padding 32/24. --}}
<section class="mx-auto max-w-7xl bg-mint shadow-sm">
    <div class="px-4 py-6 sm:px-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <nav class="flex flex-wrap items-center gap-2 text-sm">
                <span class="inline-flex items-center gap-1 rounded-full bg-jade/60 px-3 py-1 font-bold text-forest">
                    <i data-lucide="shield-check" class="h-4 w-4"></i> Modul Kurasi F08
                </span>
                <i data-lucide="chevron-right" class="h-4 w-4 text-forest/50"></i>
                <span>Antrean Validasi Sains Warga</span>
                <i data-lucide="chevron-right" class="h-4 w-4 text-forest/50"></i>
                <span class="font-semibold">BPK Wilayah X &amp; BKSDA Jateng-DIY</span>
            </nav>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-forest px-3 py-1 text-[11px] font-semibold text-white" data-testid="duplicate-engine">
                    <span class="h-2 w-2 rounded-full bg-emerald-300"></span>
                    Mesin Deteksi Duplikat Aktif (Radius {{ config('moderation.duplicate_radius_m') }}m)
                </span>
                <a href="{{ request()->fullUrl() }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-mint-100 px-4 py-2 text-sm font-medium text-ink hover:bg-mint-200">
                    <i data-lucide="refresh-cw" class="h-4 w-4"></i> Muat Ulang Antrean
                </a>
            </div>
        </div>

        <div class="mt-4 grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
            <div class="min-w-0">
                <h1 class="text-2xl font-bold leading-tight text-forest lg:whitespace-nowrap lg:text-[32px] lg:leading-10">Dasbor Moderasi Laporan Sains Warga (F08)</h1>
                <p class="mt-1 max-w-[672px] text-sm text-ink-soft">
                    Kurasi bukti lapangan, verifikasi tingkat keparahan, dan eliminasi anomali duplikasi sebelum eskalasi
                    ke Balai Pelestarian Kebudayaan (BPK) &amp; BKSDA.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                @foreach ([
                    ['Menunggu Review', $stats['pending'], $stats['emergency'] > 0 ? "({$stats['emergency']} Darurat)" : null, 'clipboard-clock', 'text-red-600'],
                    ['Terverifikasi Hari Ini', $stats['verified_today'], 'Laporan', 'badge-check', 'text-forest/70'],
                    ['Terdeteksi Duplikat', $stats['duplicates'] ?? '—', $stats['duplicates'] !== null ? 'Laporan' : null, 'copy', 'text-forest/70'],
                    ['Rerata SLA Respon', $stats['avg_response_hours'] !== null ? number_format($stats['avg_response_hours'], 1, ',', '.') : '—', 'Jam', 'timer', 'text-forest/70'],
                ] as [$label, $value, $suffix, $icon, $suffixClass])
                    <div class="w-full rounded-xl bg-white p-3 shadow-sm lg:w-[7.25rem]" data-stat="{{ \Illuminate\Support\Str::slug($label) }}">
                        <div class="flex items-start justify-between gap-1 text-[11px] leading-tight text-ink-soft">
                            <span class="max-w-[5rem]">{{ $label }}</span>
                            <i data-lucide="{{ $icon }}" class="h-3.5 w-3.5 shrink-0 {{ $loop->first ? 'text-danger' : 'text-forest' }}"></i>
                        </div>
                        <p class="mt-1.5 flex items-baseline gap-1">
                            <span class="text-2xl font-bold leading-8 text-forest">{{ $value }}</span>
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

<main class="mx-auto max-w-7xl px-4 py-6 sm:px-8">
    @foreach (['success' => 'bg-emerald-50 text-emerald-800 border-emerald-200', 'error' => 'bg-red-50 text-red-700 border-red-200'] as $key => $classes)
        @if (session($key))
            <div class="mb-4 rounded-lg border px-4 py-3 text-sm {{ $classes }}" role="status" data-flash="{{ $key }}">{{ session($key) }}</div>
        @endif
    @endforeach
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            Aksi belum disimpan: {{ $errors->first() }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
        {{-- ===== Kolom kiri: antrean ===== --}}
        <section aria-labelledby="queue-title">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h2 id="queue-title" class="flex items-center gap-2 font-bold">
                    Antrean Laporan Lapangan
                    <span class="rounded-full bg-jade px-2 py-0.5 text-xs font-semibold text-jade-ink" data-testid="queue-total">{{ $queue->total() }}{{ $filters['status'] === 'pending' ? ' Baru' : '' }}</span>
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
            {{-- ===== Log tindakan kurasi (Figma: di kolom kanan, di bawah panel detail) ===== --}}
            <section class="mt-6 rounded-2xl bg-white p-4 shadow-sm" aria-labelledby="log-title">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <h2 id="log-title" class="flex items-center gap-1.5 font-bold text-ink"><i data-lucide="history" class="h-[18px] w-[18px] text-forest"></i> Log Tindakan Kurasi Terkini</h2>
                    <a href="{{ route('moderation.index', ['status' => 'all']) }}" class="text-[11px] font-bold text-forest hover:underline">Lihat Seluruh Log Audit →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[32rem] text-left text-sm">
                        <thead class="bg-mint text-[11px] uppercase text-ink-soft">
                            <tr>
                                <th class="px-2.5 py-4 font-bold">Waktu</th>
                                <th class="px-2.5 py-4 font-bold">ID Laporan</th>
                                <th class="px-2.5 py-4 font-bold">Nama Situs</th>
                                <th class="px-2.5 py-4 font-bold">Keputusan</th>
                                <th class="px-2.5 py-4 font-bold">Petugas Kurator</th>
                                <th class="px-2.5 py-4 font-bold">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-mint-100">
                            @forelse ($recentActions as $action)
                                <tr data-log-row="{{ $action->id }}">
                                    <td class="whitespace-nowrap px-2.5 py-2.5 text-xs text-ink-muted" title="{{ $action->created_at->translatedFormat('d M Y, H:i') }}">
                                        {{ $action->created_at->isToday() ? $action->created_at->format('H:i').' WIB' : $action->created_at->translatedFormat('d M, H:i') }}
                                    </td>
                                    <td class="px-2.5 py-2.5">
                                        <a href="{{ route('moderation.index', ['status' => 'all', 'report' => $action->report_id]) }}" class="text-xs font-bold hover:underline {{ match ($action->to_status) { \App\Enums\ReportStatus::Rejected => 'text-danger', \App\Enums\ReportStatus::Pending => 'text-ink', default => 'text-forest' } }}">
                                            {{ ReportPresenter::code($action->report_id) }}
                                        </a>
                                    </td>
                                    <td class="px-2.5 py-2.5 text-xs font-medium text-ink">{{ $action->report?->site?->name ?? '-' }}</td>
                                    <td class="px-2.5 py-2.5">
                                        @if ($action->to_status === \App\Enums\ReportStatus::Rejected && str_starts_with((string) $action->note, 'Duplikat dari'))
                                            <span class="inline-block rounded-2xl bg-leaf px-2 py-0.5 text-xs font-bold leading-4 text-sage">Digabung Duplikat</span>
                                        @else
                                            <span class="inline-block rounded-2xl px-2 py-0.5 text-xs font-bold leading-4 {{ $action->to_status->badgeClasses() }}">{{ $action->to_status->label() }}</span>
                                        @endif
                                    </td>
                                    <td class="px-2.5 py-2.5 text-xs text-ink">{{ $action->changedBy?->name ?? 'Sistem' }}</td>
                                    <td class="max-w-[9rem] truncate px-2.5 py-2.5 text-xs text-ink-muted" title="{{ $action->note }}">{{ $action->note ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-3 py-6 text-center text-sm text-[#0f2a1d]/60">Belum ada tindakan kurasi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </section>
    </div>

</main>
@endsection
