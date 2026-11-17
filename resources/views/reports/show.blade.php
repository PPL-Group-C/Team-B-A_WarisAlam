@extends('layouts.app')

@section('title', 'Laporan #'.$report->id.' — WarisAlam')

@section('content')
@php
    $me = auth()->user();
    $isOwner = $me && $me->id === $report->user_id;
    [$statusLabel, $statusClass] = [
        'verified' => ['Terverifikasi warga', 'bg-forest text-white'],
        'disputed' => ['Diperdebatkan', 'bg-amber-100 text-amber-900'],
        'unverified' => ['Belum diverifikasi', 'bg-mint text-forest'],
    ][$report->verificationStatus()];
@endphp

<article class="space-y-6">
    @if (session('status'))
        <div class="rounded-lg bg-forest px-5 py-3 text-sm text-white" role="status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800" role="alert">
            <ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <header class="rounded-2xl bg-mint p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs">Laporan #{{ $report->id }}</p>
                <h1 class="text-xl font-bold text-forest">{{ $report->site->name }}</h1>
                <p class="mt-1 text-sm">{{ $report->category?->label() }} &bull; Keparahan {{ $report->severity }}/5</p>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
        </div>
        <p class="mt-3 whitespace-pre-line text-sm">{{ $report->description }}</p>
        <p class="mt-3 text-xs">
            Dilaporkan oleh {{ $report->reporterNameFor($me) }}
            @if ($report->latitude) &bull; Lokasi {{ $report->latitude }}, {{ $report->longitude }} @endif
        </p>
    </header>

    @if ($report->photos->isNotEmpty())
        <div class="grid gap-3 md:grid-cols-2">
            @foreach ($report->photos as $photo)
                <img src="{{ asset('storage/'.$photo->path) }}" alt="Foto bukti {{ $loop->iteration }}"
                     class="h-60 w-full rounded-xl object-cover">
            @endforeach
        </div>
    @endif

    <section class="rounded-2xl bg-mint p-6" id="verifikasi">
        <h2 class="text-lg font-semibold text-forest">Verifikasi Warga</h2>
        <p class="mt-1 text-sm font-medium">
            <span>{{ $report->confirms_count }} Dikonfirmasi</span> &bull;
            <span>{{ $report->disputes_count }} Disanggah</span>
        </p>

        @if (! $me)
            <p class="mt-4 text-sm">Masuk untuk memverifikasi laporan ini.</p>
        @elseif ($isOwner)
            <p class="mt-4 text-sm">Ini laporan Anda. Warga lain yang akan memverifikasinya.</p>
        @else
            <form method="POST" action="{{ route('reports.verify', $report) }}" class="mt-4 space-y-3">
                @csrf
                @if ($myVote)
                    <p class="text-sm font-medium text-forest">
                        Pilihan Anda: {{ $myVote->verdict === 'confirm' ? 'Konfirmasi' : 'Sanggah' }}
                        (Anda bisa mengubahnya di bawah)
                    </p>
                @endif
                <label for="comment" class="block text-sm">Alasan <span class="text-xs">(wajib jika menyanggah, minimal 10 karakter)</span></label>
                <textarea id="comment" name="comment" rows="3" maxlength="500"
                          class="w-full rounded-xl bg-white p-3 text-sm outline-none focus:ring-2 focus:ring-forest">{{ old('comment') }}</textarea>
                <div class="flex flex-wrap gap-3">
                    <button type="submit" name="verdict" value="confirm"
                            class="rounded-lg bg-forest px-5 py-2.5 text-sm font-semibold text-white hover:bg-forest-700">Konfirmasi Laporan</button>
                    <button type="submit" name="verdict" value="dispute"
                            class="rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-red-700 ring-1 ring-red-200 hover:bg-red-50">Sanggah Laporan</button>
                </div>
            </form>

            @if ($myVote)
                <form method="POST" action="{{ route('reports.unverify', $report) }}" class="mt-3">
                    @csrf @method('DELETE')
                    <button type="submit" dusk="withdraw-vote" class="text-xs font-semibold text-forest underline">Tarik Suara</button>
                </form>
            @endif
        @endif

        @if ($comments->isNotEmpty())
            <ul class="mt-5 space-y-2 border-t border-forest/10 pt-4 text-sm">
                @foreach ($comments as $c)
                    <li class="rounded-lg bg-white p-3">
                        <span class="text-xs font-semibold {{ $c->verdict === 'confirm' ? 'text-forest' : 'text-red-700' }}">
                            Warga &bull; {{ $c->verdict === 'confirm' ? 'Konfirmasi' : 'Sanggah' }}
                        </span>
                        <p class="mt-1 whitespace-pre-line">{{ $c->comment }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</article>
@endsection
