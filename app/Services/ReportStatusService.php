<?php

namespace App\Services;

use App\Enums\ReportStatus;
use App\Events\ReportStatusChanged;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\DamageReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * SATU-SATUNYA tempat untuk mengubah status laporan.
 * Fitur lain (penerusan F09, tindak lanjut instansi) wajib lewat service ini
 * supaya riwayat & event selalu tercatat.
 */
class ReportStatusService
{
    /** @var array<string, list<ReportStatus>> */
    private const TRANSITIONS = [
        'pending' => [ReportStatus::Verified, ReportStatus::Rejected],
        'verified' => [ReportStatus::Forwarded],
        'forwarded' => [ReportStatus::InProgress],
        'in_progress' => [ReportStatus::Resolved],
        'rejected' => [],
        'resolved' => [],
    ];

    /** @return list<ReportStatus> */
    public function allowedTransitions(DamageReport $report): array
    {
        return self::TRANSITIONS[$report->status] ?? [];
    }

    public function canTransition(DamageReport $report, ReportStatus $to): bool
    {
        return in_array($to, $this->allowedTransitions($report), true);
    }

    /**
     * @param  array<string, mixed>  $attributes  kolom lain yang ikut diperbarui secara atomik
     *                                            (mis. rejection_reason)
     *
     * @throws InvalidStatusTransitionException
     */
    public function transition(
        DamageReport $report,
        ReportStatus $to,
        ?User $actor = null,
        ?string $note = null,
        array $attributes = [],
    ): DamageReport {
        $note = filled($note) ? trim($note) : null;

        $fresh = DB::transaction(function () use ($report, $to, $actor, $note, $attributes) {
            // Kunci baris supaya dua moderator tidak memproses laporan yang sama bersamaan.
            $locked = DamageReport::query()->whereKey($report->getKey())->lockForUpdate()->firstOrFail();

            if (! $this->canTransition($locked, $to)) {
                throw InvalidStatusTransitionException::between($locked->status, $to);
            }

            $from = ReportStatus::from($locked->status);

            $locked->forceFill($attributes);
            $locked->status = $to->value;

            if ($from === ReportStatus::Pending) {
                $locked->moderated_by = $actor?->getKey();
                $locked->moderated_at = now();
            }

            $locked->save();

            $locked->statusHistories()->create([
                'from_status' => $from->value,
                'to_status' => $to->value,
                'changed_by' => $actor?->getKey(),
                'note' => $note,
            ]);

            // TODO(F08): catat juga ke spatie/laravel-activitylog setelah paket dipasang tim fondasi.

            ReportStatusChanged::dispatch($locked, $from, $to, $actor, $note);

            return $locked;
        });

        // Sinkronkan instance milik pemanggil.
        $report->setRawAttributes($fresh->getAttributes(), true);

        return $report;
    }
}
