@php
    use App\Enums\ReportStatus;
    use App\Support\ReportPresenter;

    $canApprove = in_array(ReportStatus::Verified, $allowedTransitions, true);
    $canReject = in_array(ReportStatus::Rejected, $allowedTransitions, true);
    $canResolve = in_array(ReportStatus::Resolved, $allowedTransitions, true);
    $code = ReportPresenter::code($report->id);
    $titles = collect(ReportPresenter::SEVERITY_TITLES)->map(fn ($t, $l) => "Level {$l}: {$t}");
@endphp

{{-- ===== Penyesuaian verifikasi + Setujui / Tolak ===== --}}
<section aria-labelledby="review-title"
         x-data="{
            severity: {{ (int) old('severity', $report->severity ?? 0) }},
            titles: @js($titles),
            reason: @js(old('rejection_reason', '')),
            confirmApprove: false,
            rejectOpen: @js($errors->has('rejection_reason')),
         }">
    <form method="POST" action="{{ route('moderation.approve', $report) }}" data-testid="review-form">
        @csrf
        <input type="hidden" name="return_query" value="{{ $returnQuery }}">

        <h3 id="review-title" class="mb-3 flex items-center gap-2 font-bold">
            <i data-lucide="sliders-horizontal" class="h-5 w-5 text-forest"></i> Penyesuaian Verifikasi Moderator
        </h3>

        <fieldset @disabled(! $canApprove) class="disabled:opacity-60">
            <legend class="mb-2 text-sm font-semibold">Verifikasi Tingkat Keparahan (Severity):</legend>
            <div class="grid grid-cols-5 gap-1 rounded-xl bg-mint p-1">
                @foreach (range(1, 5) as $level)
                    <label class="cursor-pointer">
                        <input type="radio" name="severity" value="{{ $level }}" x-model.number="severity" class="peer sr-only"
                               @checked((int) old('severity', $report->severity) === $level)>
                        <span class="block rounded-lg px-1 py-2 text-center text-xs transition peer-checked:bg-forest peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-forest
                                     {{ $level >= 4 ? 'text-red-700' : '' }}">
                            <span class="block font-bold">L{{ $level }}</span>
                            <span class="block text-[10px]">{{ ReportPresenter::severityShort($level) }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            <p class="mt-1 text-xs text-[#0f2a1d]/60" x-text="titles[severity] ?? ''">{{ $titles[$report->severity] ?? '' }}</p>
            @if ($report->severity_original !== null && $report->severity_original !== $report->severity)
                <p class="mt-1 text-xs text-amber-700">Severity awal dari pelapor: Level {{ $report->severity_original }}</p>
            @endif
            @error('severity') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror

            <label for="moderator_note" class="mb-1 mt-4 block text-sm font-semibold">Catatan Kurasi untuk Tim Instansi <span class="font-normal text-[#0f2a1d]/50">(opsional)</span></label>
            <textarea id="moderator_note" name="moderator_note" rows="3" maxlength="2000"
                      placeholder="Mis: coretan cat semprot terverifikasi pada arca; rekomendasi pelarut non-destruktif."
                      class="w-full rounded-xl border border-forest/15 bg-mint/50 p-3 text-sm outline-none focus:ring-2 focus:ring-forest">{{ old('moderator_note') }}</textarea>
            @error('moderator_note') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
        </fieldset>

        <div class="mt-4 flex flex-col-reverse gap-2 rounded-xl bg-mint p-3 sm:flex-row sm:justify-end">
            <button type="button" @click="rejectOpen = true" @disabled(! $canReject) data-action="reject"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50">
                <i data-lucide="ban" class="h-4 w-4"></i> Tolak Laporan
            </button>
            <button type="button" @click="confirmApprove = true" @disabled(! $canApprove) data-action="approve"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-forest px-5 py-2.5 text-sm font-semibold text-white hover:bg-forest-700 disabled:cursor-not-allowed disabled:opacity-50">
                <i data-lucide="check-circle-2" class="h-4 w-4"></i> Setujui Laporan
            </button>
        </div>
        @unless ($canApprove || $canReject)
            <p class="mt-2 text-xs text-[#0f2a1d]/60">Setujui/Tolak hanya tersedia untuk laporan berstatus "Menunggu Kurasi".</p>
        @endunless

        {{-- Modal konfirmasi setujui --}}
        <div x-cloak x-show="confirmApprove" x-transition.opacity @keydown.escape.window="confirmApprove = false"
             class="fixed inset-0 z-40 grid place-items-center bg-black/50 p-4" role="dialog" aria-modal="true" aria-labelledby="approve-title">
            <div @click.outside="confirmApprove = false" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h4 id="approve-title" class="text-lg font-bold text-forest">Setujui laporan {{ $code }}?</h4>
                <p class="mt-2 text-sm">Status akan berubah menjadi <strong>Terverifikasi</strong> dengan severity
                    <strong x-text="'Level ' + severity"></strong>. Laporan siap diteruskan ke instansi.</p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" @click="confirmApprove = false" class="rounded-lg px-4 py-2 text-sm hover:bg-mint">Batal</button>
                    <button type="submit" class="rounded-lg bg-forest px-4 py-2 text-sm font-semibold text-white hover:bg-forest-700">Ya, Setujui</button>
                </div>
            </div>
        </div>

        {{-- Modal alasan tolak (berbagi catatan kurasi di atas) --}}
        <div x-cloak x-show="rejectOpen" x-transition.opacity @keydown.escape.window="rejectOpen = false"
             class="fixed inset-0 z-40 grid place-items-center bg-black/50 p-4" role="dialog" aria-modal="true" aria-labelledby="reject-title">
            <div @click.outside="rejectOpen = false" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                <h4 id="reject-title" class="text-lg font-bold text-red-700">Tolak laporan {{ $code }}</h4>
                <label for="rejection_reason" class="mb-1 mt-3 block text-sm font-semibold">Alasan penolakan <span class="text-red-700">*</span></label>
                <textarea id="rejection_reason" name="rejection_reason" rows="4" maxlength="1000" x-model="reason"
                          placeholder="Jelaskan alasannya. Alasan ini akan dibaca oleh pelapor."
                          class="w-full rounded-xl border border-red-200 p-3 text-sm outline-none focus:ring-2 focus:ring-red-500"></textarea>
                <div class="mt-1 flex justify-between text-xs">
                    <span class="text-red-700">@error('rejection_reason'){{ $message }}@enderror</span>
                    <span :class="reason.trim().length < 10 ? 'text-red-700' : 'text-[#0f2a1d]/60'"
                          x-text="reason.trim().length + '/10 karakter minimum'"></span>
                </div>
                <p class="mt-2 text-xs text-[#0f2a1d]/60">Alasan disimpan dan ditampilkan ke pelapor. Catatan kurasi (bila diisi) ikut tersimpan.</p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" @click="rejectOpen = false" class="rounded-lg px-4 py-2 text-sm hover:bg-mint">Batal</button>
                    <button type="submit" formaction="{{ route('moderation.reject', $report) }}" :disabled="reason.trim().length < 10"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50">
                        Tolak Laporan
                    </button>
                </div>
            </div>
        </div>
    </form>
</section>

{{-- ===== Konfirmasi Selesai (hanya status in_progress) ===== --}}
@if ($canResolve)
    <section x-data="{ confirmResolve: false }" class="rounded-xl border border-forest/15 p-4" aria-labelledby="resolve-title">
        <form method="POST" action="{{ route('moderation.resolve', $report) }}">
            @csrf
            <input type="hidden" name="return_query" value="{{ $returnQuery }}">
            <h3 id="resolve-title" class="flex items-center gap-2 font-bold"><i data-lucide="flag" class="h-5 w-5 text-forest"></i> Konfirmasi Penyelesaian</h3>
            <p class="mt-1 text-xs text-[#0f2a1d]/60">Instansi sedang menangani laporan ini. Konfirmasi bila tindak lanjut sudah terbukti selesai.</p>
            <label for="resolution_note" class="mb-1 mt-3 block text-sm font-semibold">Catatan penyelesaian <span class="text-red-700">*</span></label>
            <textarea id="resolution_note" name="resolution_note" rows="3" maxlength="2000"
                      placeholder="Mis: foto pasca-penanganan dari BPK menunjukkan coretan sudah dibersihkan."
                      class="w-full rounded-xl border border-forest/15 p-3 text-sm outline-none focus:ring-2 focus:ring-forest">{{ old('resolution_note') }}</textarea>
            @error('resolution_note') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
            <div class="mt-3 flex justify-end">
                <button type="button" @click="confirmResolve = true" data-action="resolve"
                        class="inline-flex items-center gap-2 rounded-lg bg-forest px-5 py-2.5 text-sm font-semibold text-white hover:bg-forest-700">
                    <i data-lucide="check-check" class="h-4 w-4"></i> Konfirmasi Selesai
                </button>
            </div>

            <div x-cloak x-show="confirmResolve" x-transition.opacity @keydown.escape.window="confirmResolve = false"
                 class="fixed inset-0 z-40 grid place-items-center bg-black/50 p-4" role="dialog" aria-modal="true">
                <div @click.outside="confirmResolve = false" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                    <h4 class="text-lg font-bold text-forest">Konfirmasi laporan {{ $code }} selesai?</h4>
                    <p class="mt-2 text-sm">Status akan berubah menjadi <strong>Selesai</strong> dan tidak dapat diubah lagi.</p>
                    <div class="mt-5 flex justify-end gap-2">
                        <button type="button" @click="confirmResolve = false" class="rounded-lg px-4 py-2 text-sm hover:bg-mint">Batal</button>
                        <button type="submit" class="rounded-lg bg-forest px-4 py-2 text-sm font-semibold text-white">Ya, Selesai</button>
                    </div>
                </div>
            </div>
        </form>
    </section>
@endif
