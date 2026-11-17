@php
    use App\Support\ReportPresenter;

    $isPending = $selectedStatus === \App\Enums\ReportStatus::Pending;
    $nearest = $candidates->first();
    $candidateIds = $candidates->pluck('report.id')->all();
    // Kandidat terdekat ditampilkan paling atas, lalu laporan lain di situs yang sama.
    $options = $candidates->pluck('report')->concat($parentOptions)->unique('id')->values();

    $reporter = fn ($r) => ($r->user?->name ?? '-').($r->is_anonymous ? ' (Anonim)' : '');
    $relative = fn (int $seconds) => ReportPresenter::duration($seconds).($seconds === 0 ? '' : ($seconds > 0 ? ' setelahnya' : ' sebelumnya'));
@endphp

{{-- ===== Laporan ini adalah induk dari duplikat lain ===== --}}
@if ($report->duplicates->isNotEmpty())
    <section class="rounded-xl border border-forest/15 bg-mint/60 p-4 text-sm" data-testid="duplicate-children">
        <h3 class="flex items-center gap-2 font-bold text-forest"><i data-lucide="link" class="h-4 w-4"></i> Laporan Duplikat yang Ditautkan ({{ $report->duplicates->count() }})</h3>
        <ul class="mt-2 flex flex-wrap gap-2">
            @foreach ($report->duplicates as $child)
                <li>
                    <a href="{{ route('moderation.index', ['status' => 'all', 'report' => $child->id]) }}"
                       class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-1 text-xs font-semibold text-forest hover:underline">
                        {{ ReportPresenter::code($child->id) }} <span class="font-normal text-[#0f2a1d]/60">• {{ $reporter($child) }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
        <p class="mt-2 text-xs text-[#0f2a1d]/60">Laporan duplikat tidak dihitung dalam skor ancaman situs.</p>
    </section>
@endif

{{-- ===== Deteksi & penandaan duplikat (hanya laporan pending) ===== --}}
@if ($isPending)
    <section id="tandai-duplikat" class="scroll-mt-4 rounded-xl bg-leaf/40 p-4" aria-labelledby="dup-title"
             x-data="{ pickOpen: @js($errors->has('duplicate_of_id')) }"
             @open-duplicate.window="pickOpen = true; $nextTick(() => $el.scrollIntoView({ behavior: 'smooth' }))">
        <div class="flex items-start justify-between gap-3">
            <div class="flex min-w-0 flex-1 items-center gap-2">
                <span class="grid h-7 w-6 shrink-0 place-items-center rounded-md bg-forest text-white"><i data-lucide="git-merge" class="h-4 w-4"></i></span>
                <div class="min-w-0">
                    <h3 id="dup-title" class="font-bold text-ink">Deteksi Duplikat (Geospasial)</h3>
                    <p class="text-xs text-ink-soft">
                        @if ($nearest)
                            Laporan lain di situs yang sama berjarak {{ ReportPresenter::meters($nearest['distance']) }}, dalam radius {{ config('moderation.duplicate_radius_m') }} meter.
                        @else
                            Laporan lain di situs yang sama dalam radius {{ config('moderation.duplicate_radius_m') }} meter dan selisih waktu ≤ {{ config('moderation.duplicate_window_days') }} hari.
                        @endif
                    </p>
                </div>
            </div>
            <div class="flex shrink-0 flex-col items-end gap-1">
                @if ($nearest)
                    <span class="whitespace-nowrap rounded-full bg-white px-3 py-1 text-[11px] font-bold text-ink shadow-sm" data-testid="delta-time">
                        Delta Waktu: Selisih {{ ReportPresenter::duration(abs($nearest['seconds'])) }}
                    </span>
                @endif
                <span class="rounded-full bg-white px-3 py-1 text-[11px] font-bold shadow-sm {{ $candidates->isNotEmpty() ? 'text-sage' : 'text-forest' }}" data-testid="candidate-count">
                    {{ $candidates->count() }} kandidat
                </span>
            </div>
        </div>

        @if ($nearest)
            @php $other = $nearest['report']; @endphp
            <div class="mt-3 grid gap-4 rounded-lg bg-white p-2 sm:grid-cols-2" data-testid="duplicate-compare">
                @foreach ([['Laporan Ini', $report, 'text-forest'], ['Pembanding', $other, 'text-sage']] as [$title, $r, $tone])
                    <div class="flex flex-col gap-1 rounded bg-mint p-2">
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-bold leading-6 {{ $tone }}">{{ $title }}: {{ ReportPresenter::code($r->id) }} <span class="font-semibold">({{ $r->is_anonymous ? 'Pelapor Anonim' : ($r->user?->name ?? '-') }})</span></p>
                            <span class="whitespace-nowrap text-xs text-ink-muted">{{ $r->submitted_at?->format('H:i') }} WIB</span>
                        </div>
                        <p class="line-clamp-3 text-xs text-ink">"{{ $r->description ?: 'Tanpa deskripsi.' }}"</p>
                        <p class="font-semibold leading-6 {{ $loop->first ? 'text-forest' : 'text-ink-soft' }}">Tingkat Keparahan Diajukan: Level {{ $r->severity ?? '-' }}</p>
                    </div>
                @endforeach
            </div>
            @php
                // Laporan yang lebih dulu masuk dijadikan induk.
                $thisIsNewer = $nearest['seconds'] < 0; // seconds = waktu pembanding − waktu laporan ini
                $parentCode = ReportPresenter::code($thisIsNewer ? $other->id : $report->id);
                $childCode = ReportPresenter::code($thisIsNewer ? $report->id : $other->id);
            @endphp
            <p class="mt-3 text-xs text-ink-soft" data-testid="duplicate-recommendation">
                Rekomendasi: Jadikan {{ $parentCode }} sebagai <strong>Laporan Induk</strong> dan tautkan {{ $childCode }} sebagai <strong>Duplikat</strong> agar kerusakan yang sama tidak dihitung dua kali.
                @unless ($thisIsNewer)
                    <a href="{{ route('moderation.index', ['report' => $other->id]) }}" class="font-semibold text-forest hover:underline">Buka {{ $childCode }} →</a>
                @endunless
            </p>
            @if ($candidates->count() > 1)
                <p class="mt-2 text-xs">Kandidat lain:
                    @foreach ($candidates->skip(1) as $c)
                        <a href="{{ route('moderation.index', ['status' => 'all', 'report' => $c['report']->id]) }}" class="font-semibold text-forest hover:underline">{{ ReportPresenter::code($c['report']->id) }}</a>
                        <span class="text-[#0f2a1d]/60">({{ ReportPresenter::meters($c['distance']) }})</span>@if (! $loop->last), @endif
                    @endforeach
                </p>
            @endif
        @else
            <p class="mt-3 rounded-lg bg-white px-3 py-2 text-xs text-ink-soft">Tidak ada kandidat duplikat otomatis. Anda tetap dapat memilih laporan induk secara manual.</p>
        @endif

        {{-- Form tandai duplikat: tersembunyi di balik tombol seperti di Figma --}}
        @if ($options->isNotEmpty())
            <button type="button" @click="pickOpen = !pickOpen" :aria-expanded="pickOpen.toString()"
                    class="mt-3 inline-flex items-center gap-1 rounded-lg bg-mint-200 px-3 py-1.5 text-[13px] font-bold text-ink shadow-sm hover:bg-leaf">
                <i data-lucide="link" class="h-3.5 w-3.5"></i> Gabungkan &amp; Konsolidasikan Klaster
            </button>

            <form x-cloak x-show="pickOpen" x-transition method="POST" action="{{ route('moderation.duplicate', $report) }}" class="mt-3 rounded-xl bg-white p-3"
                  x-data="{ q: '', parent: @js((int) old('duplicate_of_id', $nearest['report']->id ?? 0) ?: null), confirm: false }">
                @csrf
                <input type="hidden" name="return_query" value="{{ $returnQuery }}">
                <p class="text-sm font-semibold">Tandai sebagai duplikat dari:</p>
                <label class="relative mt-2 block">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-forest/60"></i>
                    <input type="search" x-model="q" placeholder="Cari ID atau kata di deskripsi (situs yang sama)…"
                           class="w-full rounded-lg bg-mint py-2 pl-9 pr-3 text-sm outline-none focus:ring-2 focus:ring-forest">
                </label>
                <ul class="mt-2 max-h-56 space-y-1 overflow-y-auto pr-1" role="radiogroup" aria-label="Laporan induk">
                    @foreach ($options as $option)
                        @php
                            $isCandidate = in_array($option->id, $candidateIds, true);
                            $search = mb_strtolower(ReportPresenter::code($option->id).' '.$option->id.' '.$option->description.' '.$reporter($option));
                        @endphp
                        <li x-show="!q || @js($search).includes(q.toLowerCase().trim())">
                            <label class="flex cursor-pointer items-start gap-2 rounded-lg border px-3 py-2 text-xs hover:bg-mint/60"
                                   :class="parent === {{ $option->id }} ? 'border-forest bg-mint/60' : 'border-forest/10'">
                                <input type="radio" name="duplicate_of_id" value="{{ $option->id }}" x-model.number="parent" class="mt-0.5 accent-[#05452b]">
                                <span class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-center gap-1.5">
                                        <span class="font-semibold text-forest">{{ ReportPresenter::code($option->id) }}</span>
                                        @if ($isCandidate)
                                            <span class="rounded-full bg-leaf px-2 py-0.5 text-[10px] font-semibold text-sage">Kandidat</span>
                                        @endif
                                        @if ($s = $option->statusEnum())
                                            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $s->badgeClasses() }}">{{ $s->label() }}</span>
                                        @endif
                                        <span class="text-[#0f2a1d]/60">{{ $reporter($option) }} • {{ $option->submitted_at?->translatedFormat('d M Y, H:i') }}</span>
                                    </span>
                                    <span class="mt-0.5 block truncate text-[#0f2a1d]/70">{{ $option->description }}</span>
                                </span>
                            </label>
                        </li>
                    @endforeach
                </ul>
                @error('duplicate_of_id') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror

                <label for="duplicate_note" class="mb-1 mt-3 block text-xs font-semibold">Catatan (opsional)</label>
                <input id="duplicate_note" name="duplicate_note" maxlength="2000" value="{{ old('duplicate_note') }}"
                       class="w-full rounded-lg border border-forest/15 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-forest">

                <p class="mt-2 text-[11px] text-[#0f2a1d]/60">Laporan ini akan berstatus <strong>Ditolak</strong> dengan alasan otomatis "Duplikat dari laporan #…". Penolakan karena duplikat tidak mengurangi reputasi pelapor.</p>
                <div class="mt-3 flex justify-end">
                    <button type="button" @click="confirm = true" :disabled="!parent" data-action="duplicate"
                            class="inline-flex items-center gap-2 rounded-lg border border-forest/30 bg-mint px-4 py-2 text-sm font-semibold text-forest hover:bg-mint-200 disabled:cursor-not-allowed disabled:opacity-50">
                        <i data-lucide="copy-check" class="h-4 w-4"></i> Tandai Duplikat
                    </button>
                </div>

                <div x-cloak x-show="confirm" x-transition.opacity @keydown.escape.window="confirm = false"
                     class="fixed inset-0 z-40 grid place-items-center bg-black/50 p-4" role="dialog" aria-modal="true">
                    <div @click.outside="confirm = false" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                        <h4 class="text-lg font-bold text-forest">Tandai {{ ReportPresenter::code($report->id) }} sebagai duplikat?</h4>
                        <p class="mt-2 text-sm">Laporan akan ditautkan ke <strong x-text="'#REP-' + String(parent).padStart(4, '0')"></strong> dan tidak dihitung dalam skor ancaman situs.</p>
                        <div class="mt-5 flex justify-end gap-2">
                            <button type="button" @click="confirm = false" class="rounded-lg px-4 py-2 text-sm hover:bg-mint">Batal</button>
                            <button type="submit" class="rounded-lg bg-forest px-4 py-2 text-sm font-semibold text-white">Ya, Tandai Duplikat</button>
                        </div>
                    </div>
                </div>
            </form>
        @else
            <p class="mt-3 text-xs text-[#0f2a1d]/60">Belum ada laporan lain di situs ini yang dapat dijadikan induk.</p>
        @endif
    </section>
@endif
