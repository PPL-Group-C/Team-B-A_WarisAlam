@php
    use App\Support\ReportPresenter;
    $thumb = $report->photos->first();
@endphp

<a href="{{ request()->fullUrlWithQuery(['report' => $report->id]) }}"
   data-report-card="{{ $report->id }}"
   @if ($active) aria-current="true" @endif
   class="block rounded-xl border bg-white p-4 shadow-sm transition hover:border-forest/40
          {{ $active ? 'border-forest/30 border-l-4 border-l-forest' : 'border-forest/10' }}">
    <div class="flex gap-3">
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-xs font-semibold {{ $report->severity >= 4 ? 'text-red-700' : 'text-forest' }}">{{ ReportPresenter::code($report->id) }}</span>
                @if ($report->category)
                    <span class="rounded-full bg-mint-200 px-2 py-0.5 text-[10px] font-medium text-forest">{{ $report->category->label() }}</span>
                @endif
                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ ReportPresenter::severityBadge($report->severity) }}">
                    {{ ReportPresenter::severityLabel($report->severity) }}
                </span>
                @if ($report->statusEnum() && $report->statusEnum() !== \App\Enums\ReportStatus::Pending)
                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $report->statusEnum()->badgeClasses() }}">{{ $report->statusEnum()->label() }}</span>
                @endif
            </div>
            <p class="mt-1 truncate font-semibold">{{ $report->site?->name ?? 'Situs tidak diketahui' }}</p>
            <p class="line-clamp-2 text-xs text-[#0f2a1d]/70">{{ $report->description ?: 'Tanpa deskripsi.' }}</p>
        </div>

        <div class="flex shrink-0 flex-col items-end gap-2">
            <span class="whitespace-nowrap text-[11px] text-[#0f2a1d]/60" title="{{ $report->submitted_at?->translatedFormat('d M Y, H:i') }}">
                {{ $report->submitted_at?->diffForHumans() ?? '-' }}
            </span>
            @if ($thumb)
                <img src="{{ Storage::disk('public')->url($thumb->path) }}" alt="Foto {{ ReportPresenter::code($report->id) }}"
                     loading="lazy" onerror="this.style.visibility='hidden'"
                     class="h-14 w-14 rounded-lg bg-mint object-cover">
            @endif
        </div>
    </div>

    <div class="mt-3 flex items-center gap-2 text-xs">
        <span class="text-[#0f2a1d]/60">Pelapor:</span>
        <span class="font-medium">{{ $report->user?->name ?? '-' }}</span>
        @if ($report->is_anonymous)
            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600" title="Nama disembunyikan dari publik">Anonim</span>
        @endif
    </div>
</a>
