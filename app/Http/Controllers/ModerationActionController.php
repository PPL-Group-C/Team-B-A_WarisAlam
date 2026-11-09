<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\DamageReport;
use App\Services\ReportStatusService;
use App\Support\ReportPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * F08 Moderasi (PBI-23): Setujui / Tolak / Konfirmasi Selesai.
 * Semua perubahan status lewat ReportStatusService.
 */
class ModerationActionController extends Controller
{
    /** Parameter antrean yang dipertahankan setelah aksi (tanpa "report"). */
    private const QUEUE_PARAMS = ['q', 'status', 'category', 'severity', 'sort'];

    public function __construct(private readonly ReportStatusService $statuses) {}

    public function approve(Request $request, DamageReport $report): RedirectResponse
    {
        Gate::authorize('moderate-reports');

        $data = $request->validate([
            'severity' => ['nullable', 'integer', 'between:1,5'],
            'moderator_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $attributes = ['moderator_note' => $data['moderator_note'] ?? null];
        $notes = [$data['moderator_note'] ?? null];

        $newSeverity = isset($data['severity']) ? (int) $data['severity'] : null;
        if ($newSeverity !== null && $newSeverity !== (int) $report->severity) {
            $attributes['severity'] = $newSeverity;
            // Simpan nilai asli pelapor hanya pada penyesuaian pertama.
            $attributes['severity_original'] = $report->severity_original ?? $report->severity;
            array_unshift($notes, "Severity disesuaikan L{$report->severity} → L{$newSeverity}.");
        }

        return $this->apply($request, $report, ReportStatus::Verified, $notes, $attributes,
            'Laporan '.ReportPresenter::code($report->id).' disetujui dan berstatus Terverifikasi.');
    }

    public function reject(Request $request, DamageReport $report): RedirectResponse
    {
        Gate::authorize('moderate-reports');

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:10', 'max:1000'],
            'moderator_note' => ['nullable', 'string', 'max:2000'],
        ], [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi.',
            'rejection_reason.min' => 'Alasan penolakan minimal :min karakter.',
        ]);

        return $this->apply($request, $report, ReportStatus::Rejected,
            [$data['rejection_reason']],
            [
                'rejection_reason' => trim($data['rejection_reason']),
                'moderator_note' => $data['moderator_note'] ?? null,
            ],
            'Laporan '.ReportPresenter::code($report->id).' ditolak. Alasan tersimpan dan dapat dibaca pelapor.');
    }

    public function resolve(Request $request, DamageReport $report): RedirectResponse
    {
        Gate::authorize('moderate-reports');

        $data = $request->validate([
            'resolution_note' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'resolution_note.required' => 'Catatan penyelesaian wajib diisi.',
            'resolution_note.min' => 'Catatan penyelesaian minimal :min karakter.',
        ]);

        return $this->apply($request, $report, ReportStatus::Resolved, [$data['resolution_note']], [],
            'Penyelesaian laporan '.ReportPresenter::code($report->id).' dikonfirmasi.');
    }

    private function apply(
        Request $request,
        DamageReport $report,
        ReportStatus $to,
        array $notes,
        array $attributes,
        string $success,
    ): RedirectResponse {
        try {
            $this->statuses->transition(
                $report,
                $to,
                $request->user(),
                collect($notes)->filter(fn ($n) => filled($n))->map(fn ($n) => trim($n))->join("\n") ?: null,
                $attributes,
            );
        } catch (InvalidStatusTransitionException $e) {
            return redirect()->route('moderation.index', $this->queueQuery($request) + ['report' => $report->id])
                ->with('error', $e->getMessage());
        }

        // Tanpa "report" → kartu teratas antrean (laporan berikutnya) otomatis terpilih.
        return redirect()->route('moderation.index', $this->queueQuery($request))->with('success', $success);
    }

    private function queueQuery(Request $request): array
    {
        parse_str((string) $request->input('return_query', ''), $query);

        return array_filter(
            array_intersect_key($query, array_flip(self::QUEUE_PARAMS)),
            fn ($v) => is_string($v) && $v !== '',
        );
    }
}
