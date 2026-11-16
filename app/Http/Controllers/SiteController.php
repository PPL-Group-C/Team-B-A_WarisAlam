<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Enums\SiteStatus;
use App\Enums\SiteType;
use App\Models\DamageReport;
use App\Models\HeritageSite;
use App\Models\SiteAdoption;
use App\Models\SitePhoto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

/**
 * Halaman publik detail situs (Raihan, FR-11):
 * - PBI-13: sejarah, fakta, galeri foto
 * - PBI-14: status kondisi, skor ancaman, jumlah pengadopsi
 * - PBI-15: riwayat laporan terverifikasi + tautan kasus publik
 */
class SiteController extends Controller
{
    private const REPORTS_PER_PAGE = 5;

    public function show(HeritageSite $site): View
    {
        $reports = $this->publicReports($site)
            ->with(['photos', 'user:id,name'])
            ->latest('submitted_at')
            ->latest('id')
            ->paginate(self::REPORTS_PER_PAGE)
            ->fragment('riwayat-laporan');

        return view('sites.show', [
            'site' => $site,
            'type' => SiteType::tryFrom((string) $site->type) ?? SiteType::Cultural,
            'status' => $this->status($site),
            'score' => (float) $site->threat_score,
            'facts' => $this->facts($site),
            'photos' => SitePhoto::where('heritage_site_id', $site->id)->ordered()->get(),
            'adoptersCount' => SiteAdoption::where('heritage_site_id', $site->id)->count(),
            'reportStats' => $this->reportStats($site),
            'reports' => $reports,
        ]);
    }

    /** PBI-15: rekam jejak penanganan satu laporan terverifikasi. */
    public function report(HeritageSite $site, DamageReport $report): View
    {
        abort_unless($this->publicReports($site)->whereKey($report->getKey())->exists(), 404);

        $report->load([
            'photos',
            'user:id,name',
            'statusHistories' => fn ($query) => $query->reorder()->oldest('created_at')->oldest('id'),
        ]);

        return view('sites.report', [
            'site' => $site,
            'report' => $report,
            'status' => $this->status($site),
        ]);
    }

    /**
     * Laporan yang boleh tampil ke publik: sudah lolos moderasi
     * (verified → resolved) dan bukan duplikat (FR-11, FR-17).
     */
    private function publicReports(HeritageSite $site): Builder
    {
        return DamageReport::query()
            ->where('heritage_site_id', $site->getKey())
            ->whereIn('status', ReportStatus::values(ReportStatus::accepted()))
            ->notDuplicate();
    }

    private function status(HeritageSite $site): SiteStatus
    {
        return SiteStatus::tryFrom((string) $site->status)
            ?? SiteStatus::fromScore((float) $site->threat_score);
    }

    /** @return list<array{label: string, value: string}> */
    private function facts(HeritageSite $site): array
    {
        $facts = is_array($site->facts) ? $site->facts : json_decode((string) $site->facts, true);

        return collect(is_array($facts) ? $facts : [])
            ->filter(fn ($fact) => is_array($fact) && filled($fact['label'] ?? null) && filled($fact['value'] ?? null))
            ->map(fn (array $fact) => ['label' => (string) $fact['label'], 'value' => (string) $fact['value']])
            ->values()
            ->all();
    }

    /** @return array{total: int, handling: int, resolved: int} */
    private function reportStats(HeritageSite $site): array
    {
        $counts = $this->publicReports($site)
            ->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total);

        $resolved = $counts->get(ReportStatus::Resolved->value, 0);

        return [
            'total' => $counts->sum(),
            'handling' => $counts->sum() - $resolved,
            'resolved' => $resolved,
        ];
    }
}
