<?php

namespace App\Events;

use App\Enums\ReportStatus;
use App\Models\DamageReport;
use App\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Hook untuk fitur lain: notifikasi (Fasya), email/timeline pelapor (Mumtaz),
 * penerusan ke instansi (F09), reputasi (F10). Dikirim setelah transaksi commit.
 */
class ReportStatusChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public DamageReport $report,
        public ReportStatus $from,
        public ReportStatus $to,
        public ?User $actor = null,
        public ?string $note = null,
    ) {}
}
