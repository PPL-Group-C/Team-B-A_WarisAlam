@extends('layouts.app')

@section('title', 'Laporan Warga — WarisAlam')

@section('content')
@php
    $badges = [
        'verified' => ['Terverifikasi warga', 'bg-forest text-white'],
        'disputed' => ['Diperdebatkan', 'bg-amber-100 text-amber-900'],
        'unverified' => ['Belum diverifikasi', 'bg-mint text-forest'],
    ];
@endphp

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-forest">Laporan Warga</h1>
        <p class="text-sm">Buka laporan untuk mengonfirmasi atau menyanggahnya, agar keakuratan laporan bisa diverifikasi.</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @forelse ($reports as $r)
            @php [$label, $class] = $badges[$r->verificationStatus()]; @endphp
            <article class="rounded-2xl bg-mint p-5">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="font-semibold text-forest">{{ $r->site->name }}</h2>
                    <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $class }}">{{ $label }}</span>
                </div>
                <p class="mt-1 text-sm">{{ $r->category?->label() }} &bull; Keparahan {{ $r->severity }}/5</p>
                <p class="mt-1 line-clamp-2 text-xs">{{ $r->description }}</p>
                <p class="mt-3 text-xs">
                    Dilaporkan oleh {{ $r->reporterNameFor(auth()->user()) }}
                    &bull; {{ $r->confirms_count }} Dikonfirmasi &bull; {{ $r->disputes_count }} Disanggah
                </p>
                <a href="{{ route('reports.show', $r) }}"
                   class="mt-3 inline-block rounded-lg bg-forest px-4 py-2 text-xs font-semibold text-white hover:bg-forest-700">Lihat &amp; Verifikasi</a>
            </article>
        @empty
            <p class="text-sm">Belum ada laporan yang dikirim.</p>
        @endforelse
    </div>

    {{ $reports->links() }}
</div>
@endsection
