{{-- PBI-15: satu laporan terverifikasi di riwayat situs. Identitas pelapor anonim tidak ditampilkan (FR-14). --}}
@use('App\Support\ReportPresenter')
@php
    $reportStatus = $report->statusEnum();
    $thumb = $report->photos->first();
@endphp
<li id="laporan-{{ $report->id }}" class="flex gap-4 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-forest/10">
    @if ($thumb)
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($thumb->path) }}" alt="Foto laporan {{ ReportPresenter::code($report->id) }}"
             loading="lazy" class="hidden h-24 w-28 shrink-0 rounded-lg object-cover sm:block">
    @endif

    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="font-semibold text-forest">{{ ReportPresenter::code($report->id) }}</span>
            @if ($reportStatus)
                <span class="rounded-full px-2.5 py-0.5 font-semibold {{ $reportStatus->badgeClasses() }}">{{ $reportStatus->label() }}</span>
            @endif
            @if ($report->category)
                <span class="inline-flex items-center gap-1 rounded-full bg-mint px-2.5 py-0.5">
                    <i data-lucide="{{ $report->category->icon() }}" class="h-3 w-3"></i>{{ $report->category->label() }}
                </span>
            @endif
            <span class="rounded-full px-2.5 py-0.5 {{ ReportPresenter::severityBadge($report->severity) }}">{{ ReportPresenter::severityLabel($report->severity) }}</span>
        </div>

        <p class="mt-2 text-sm leading-relaxed">{{ \Illuminate\Support\Str::limit($report->description, 180) }}</p>

        <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs">
            <span>
                Dilaporkan oleh <b>{{ $report->is_anonymous ? 'Pelapor Anonim' : ($report->user?->name ?? 'Warga') }}</b>
                @if ($report->submitted_at)
                    &middot; {{ $report->submitted_at->locale('id')->translatedFormat('j F Y') }}
                @endif
            </span>
            <a href="{{ route('sites.reports.show', [$site, $report]) }}" class="inline-flex items-center gap-1 font-semibold text-forest hover:underline">
                Lihat rekam jejak kasus<i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
            </a>
        </div>
    </div>
</li>
