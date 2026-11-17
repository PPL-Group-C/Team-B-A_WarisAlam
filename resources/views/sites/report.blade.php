@extends('layouts.app')

@use('App\Support\ReportPresenter')
@php
    $code = ReportPresenter::code($report->id);
    $reportStatus = $report->statusEnum();
    $reporter = $report->is_anonymous ? 'Pelapor Anonim' : ($report->user?->name ?? 'Warga');
@endphp

@section('title', "Kasus {$code} — {$site->name}")

@section('content')
<nav aria-label="Breadcrumb" class="mb-4 text-xs">
    <ol class="flex flex-wrap items-center gap-1.5">
        <li><a href="{{ url('/map') }}" class="text-forest hover:underline">Jelajahi Peta</a></li>
        <li aria-hidden="true">/</li>
        <li><a href="{{ route('sites.show', $site) }}" class="text-forest hover:underline">{{ $site->name }}</a></li>
        <li aria-hidden="true">/</li>
        <li class="font-medium" aria-current="page">Kasus {{ $code }}</li>
    </ol>
</nav>

<header class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-forest">Rekam Jejak Kasus Publik</p>
        <h1 class="mt-1 text-2xl font-bold text-forest">Kasus {{ $code }}</h1>
        <p class="mt-1 text-sm">{{ $site->name }}</p>
    </div>
    @if ($reportStatus)
        <span class="rounded-full px-4 py-2 text-sm font-semibold {{ $reportStatus->badgeClasses() }}">{{ $reportStatus->label() }}</span>
    @endif
</header>

<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
    <div class="min-w-0 space-y-8">
        <section>
            <x-section-bar icon="file-text" title="Ringkasan Laporan"></x-section-bar>
            <div class="rounded-2xl bg-white p-5 text-sm shadow-sm">
                <dl class="mb-4 grid gap-3 sm:grid-cols-3">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-forest">Kategori</dt>
                        <dd>{{ $report->category?->label() ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-forest">Tingkat Kerusakan</dt>
                        <dd>{{ ReportPresenter::severityLabel($report->severity) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-forest">Pelapor</dt>
                        <dd>{{ $reporter }}</dd>
                    </div>
                </dl>
                <p class="leading-relaxed">{{ $report->description }}</p>
            </div>
        </section>

        @if ($report->photos->isNotEmpty())
            <section>
                <x-section-bar icon="camera" title="Foto Bukti">{{ $report->photos->count() }} foto</x-section-bar>
                <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
                    @foreach ($report->photos as $photo)
                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo->path) }}" target="_blank" rel="noopener"
                           class="overflow-hidden rounded-xl bg-mint">
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo->path) }}" alt="Foto bukti {{ $code }}"
                                 loading="lazy" class="aspect-[4/3] w-full object-cover">
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    {{-- Timeline penanganan --}}
    <aside>
        <section aria-labelledby="timeline-title" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-forest/10">
            <h2 id="timeline-title" class="mb-4 flex items-center gap-2 text-sm font-semibold text-forest">
                <i data-lucide="git-commit-vertical" class="h-4 w-4"></i>Riwayat Penanganan
            </h2>
            <ol class="relative space-y-5 border-l-2 border-mint-200 pl-5" data-testid="case-timeline">
                @if ($report->submitted_at)
                    <li class="relative">
                        <span class="absolute -left-[27px] top-0.5 h-3 w-3 rounded-full bg-forest ring-4 ring-white"></span>
                        <p class="text-sm font-semibold">Laporan dikirim warga</p>
                        <p class="text-xs">{{ $report->submitted_at->locale('id')->translatedFormat('j F Y, H:i') }}</p>
                    </li>
                @endif
                @foreach ($report->statusHistories as $history)
                    <li class="relative">
                        <span class="absolute -left-[27px] top-0.5 h-3 w-3 rounded-full bg-forest ring-4 ring-white"></span>
                        <p class="text-sm font-semibold">{{ $history->to_status?->label() ?? $history->getRawOriginal('to_status') }}</p>
                        <p class="text-xs">{{ $history->created_at?->locale('id')->translatedFormat('j F Y, H:i') }}</p>
                        @if (filled($history->note))
                            <p class="mt-1 rounded-lg bg-mint px-3 py-2 text-xs leading-relaxed">{{ $history->note }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>
        </section>

        <a href="{{ route('sites.show', $site) }}#riwayat-laporan"
           class="mt-4 flex items-center justify-center gap-2 rounded-lg border border-forest/30 px-4 py-2.5 text-sm font-semibold text-forest hover:bg-mint">
            <i data-lucide="arrow-left" class="h-4 w-4"></i>Kembali ke detail situs
        </a>
    </aside>
</div>
@endsection
