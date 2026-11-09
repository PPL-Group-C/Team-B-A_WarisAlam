<?php

namespace App\Http\Controllers;

use App\Enums\DamageCategory;
use App\Enums\ReportStatus;
use App\Models\DamageReport;
use App\Models\ReportStatusHistory;
use App\Support\ExifReader;
use App\Support\ReportPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * F08 Moderasi Laporan: dasbor antrean moderator.
 * Akses dibatasi Gate "moderate-reports" (lihat routes/moderation.php).
 */
class ModerationController extends Controller
{
    public const SORTS = [
        'oldest' => 'Terlama dulu (FIFO)',
        'severity' => 'Severity tertinggi',
    ];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in([...ReportStatus::values(), 'all'])],
            'category' => ['nullable', Rule::enum(DamageCategory::class)],
            'severity' => ['nullable', 'integer', 'between:1,5'],
            'sort' => ['nullable', Rule::in(array_keys(self::SORTS))],
            'report' => ['nullable', 'integer'],
        ]) + ['status' => ReportStatus::Pending->value, 'sort' => 'oldest'];

        $queue = $this->queueQuery($filters)
            ->paginate(config('moderation.queue_per_page', 15))
            ->withQueryString();

        $selected = $this->selectedReport($request->integer('report') ?: $queue->first()?->id);

        return view('moderation.index', [
            'filters' => $filters,
            'queue' => $queue,
            'selected' => $selected,
            'selectedStatus' => $selected?->statusEnum(),
            'reporterStats' => $selected ? $this->reporterStats($selected) : null,
            'evidence' => $selected ? $this->evidence($selected) : null,
            'stats' => $this->stats(),
            'categories' => DamageCategory::cases(),
            'statusOptions' => ReportStatus::cases(),
            'sorts' => self::SORTS,
        ]);
    }

    private function queueQuery(array $filters): Builder
    {
        return DamageReport::query()
            ->select('damage_reports.*')
            ->with([
                'site:id,name',
                'user:id,name',
                'photos:id,damage_report_id,path,original_name',
            ])
            ->when($filters['status'] !== 'all', fn (Builder $q) => $q->where('status', $filters['status']))
            ->when($filters['category'] ?? null, fn (Builder $q, $category) => $q->where('category', $category))
            ->when($filters['severity'] ?? null, fn (Builder $q, $severity) => $q->where('severity', $severity))
            ->when(filled($filters['q'] ?? null), function (Builder $q) use ($filters) {
                $term = trim($filters['q']);
                $id = ReportPresenter::parseCode($term);

                $q->where(function (Builder $w) use ($term, $id) {
                    $w->whereHas('site', fn (Builder $s) => $s->where('name', 'like', '%'.$term.'%'));

                    if ($id !== null) {
                        $w->orWhereKey($id);
                    }
                });
            })
            ->when($filters['sort'] === 'severity', fn (Builder $q) => $q->orderByDesc('severity'))
            ->orderBy('submitted_at')
            ->orderBy('id');
    }

    private function selectedReport(?int $id): ?DamageReport
    {
        if (! $id) {
            return null;
        }

        return DamageReport::query()
            ->with([
                'site',
                'user:id,name,email',
                'photos',
                'moderator:id,name',
                'statusHistories.changedBy:id,name',
            ])
            ->find($id);
    }

    /** Rekam jejak pelapor (di luar laporan ini) sebagai dasar kredibilitas. */
    private function reporterStats(DamageReport $report): array
    {
        $accepted = ReportStatus::values(ReportStatus::accepted());

        $row = DamageReport::query()
            ->where('user_id', $report->user_id)
            ->whereKeyNot($report->id)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status IN ('.implode(',', array_fill(0, count($accepted), '?')).') THEN 1 ELSE 0 END) as accepted', $accepted)
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as rejected', [ReportStatus::Rejected->value])
            ->toBase()
            ->first();

        return [
            'total' => (int) $row->total,
            'accepted' => (int) $row->accepted,
            'rejected' => (int) $row->rejected,
        ];
    }

    /** Data verifikasi EXIF & geospasial untuk panel detail. */
    private function evidence(DamageReport $report): array
    {
        $site = $report->site;
        $hasPin = $report->latitude !== null && $report->longitude !== null;
        $exifPhoto = $report->photos->first(fn ($p) => $p->exif_latitude !== null);
        $timedPhoto = $report->photos->first(fn ($p) => $p->taken_at !== null);

        return [
            'pin' => ReportPresenter::coordinate($report->latitude, $report->longitude),
            'exif_coordinate' => ReportPresenter::coordinate($exifPhoto?->exif_latitude, $exifPhoto?->exif_longitude),
            'distance_to_site' => $hasPin && $site
                ? ExifReader::distanceMeters((float) $report->latitude, (float) $report->longitude, (float) $site->latitude, (float) $site->longitude)
                : null,
            'taken_at' => $timedPhoto?->taken_at,
            'upload_lag' => $timedPhoto && $report->submitted_at
                ? (int) $timedPhoto->taken_at->diffInSeconds($report->submitted_at, false)
                : null,
            'exif_valid' => $report->photos->where('exif_valid', true)->count(),
            'photo_total' => $report->photos->count(),
            'device' => null, // kolom perangkat belum disimpan oleh form laporan
        ];
    }

    private function stats(): array
    {
        $pending = DamageReport::query()
            ->where('status', ReportStatus::Pending->value)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN severity >= ? THEN 1 ELSE 0 END) as emergency', [config('moderation.emergency_severity', 4)])
            ->toBase()
            ->first();

        $verifiedToday = ReportStatusHistory::query()
            ->where('to_status', ReportStatus::Verified->value)
            ->where('created_at', '>=', today())
            ->distinct()
            ->count('report_id');

        // Rerata jam dari laporan masuk sampai keputusan moderator (30 hari terakhir).
        $avgResponseHours = DamageReport::query()
            ->whereNotNull('moderated_at')
            ->whereNotNull('submitted_at')
            ->where('moderated_at', '>=', now()->subDays(30))
            ->get(['submitted_at', 'moderated_at'])
            ->avg(fn (DamageReport $r) => $r->submitted_at->diffInMinutes($r->moderated_at) / 60);

        return [
            'pending' => (int) $pending->total,
            'emergency' => (int) $pending->emergency,
            'verified_today' => $verifiedToday,
            'duplicates' => null, // diisi di PBI-24
            'avg_response_hours' => $avgResponseHours,
        ];
    }
}
