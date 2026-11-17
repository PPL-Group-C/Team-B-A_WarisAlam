<?php

namespace App\Services;

use App\Enums\ReportStatus;
use App\Exceptions\InvalidDuplicateException;
use App\Models\DamageReport;
use App\Models\User;
use App\Support\ExifReader;
use App\Support\ReportPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * F08 Moderasi (PBI-24): deteksi kandidat duplikat (tanpa AI) dan penandaan duplikat.
 *
 * Kandidat = laporan lain di SITUS YANG SAMA, berjarak ≤ radius (Haversine)
 * dan selisih waktu kirim ≤ jendela hari, bukan duplikat, bukan laporan ditolak.
 */
class DuplicateReportService
{
    public function __construct(private readonly ReportStatusService $statuses) {}

    /**
     * Tandai $report sebagai duplikat dari $parent: status → rejected (lewat
     * ReportStatusService), alasan otomatis, tautan, dan penanda duplikat.
     *
     * @throws InvalidDuplicateException
     */
    public function markAsDuplicate(DamageReport $report, DamageReport $parent, ?User $actor, ?string $note = null): DamageReport
    {
        return DB::transaction(function () use ($report, $parent, $actor, $note) {
            // Baca ulang keduanya dengan kunci baris supaya aturan dicek pada data terbaru.
            $locked = DamageReport::query()->whereKey([$report->id, $parent->id])->lockForUpdate()->get()->keyBy('id');
            $report = $locked->get($report->id) ?? $report;
            $parent = $locked->get($parent->id) ?? $parent;

            $this->assertCanLink($report, $parent);

            // TODO(F08): catat ke activity log setelah spatie/laravel-activitylog terpasang.
            $reason = 'Duplikat dari laporan '.ReportPresenter::code($parent->id).'.';

            return $this->statuses->transition(
                $report,
                ReportStatus::Rejected,
                $actor,
                collect([$reason, filled($note) ? trim($note) : null])->filter()->join("\n"),
                [
                    'rejection_reason' => $reason,
                    'moderator_note' => filled($note) ? trim($note) : null,
                    'duplicate_of_id' => $parent->id,
                    'rejected_as_duplicate' => true,
                ],
            );
        });
    }

    /** @throws InvalidDuplicateException */
    public function assertCanLink(DamageReport $report, DamageReport $parent): void
    {
        $code = fn (?int $id) => ReportPresenter::code((int) $id);

        match (true) {
            $report->is($parent) => throw new InvalidDuplicateException('Laporan tidak dapat ditandai sebagai duplikat dari dirinya sendiri.'),
            $report->status !== ReportStatus::Pending->value => throw new InvalidDuplicateException('Hanya laporan berstatus "Menunggu Kurasi" yang dapat ditandai duplikat.'),
            (int) $report->heritage_site_id !== (int) $parent->heritage_site_id => throw new InvalidDuplicateException('Laporan induk harus berada di situs yang sama.'),
            $parent->isDuplicate() => throw new InvalidDuplicateException("Laporan {$code($parent->id)} sendiri adalah duplikat dari {$code($parent->duplicate_of_id)}. Pilih laporan aslinya."),
            $report->duplicates()->exists() => throw new InvalidDuplicateException("Laporan {$code($report->id)} sudah menjadi induk duplikat lain, sehingga tidak dapat ditandai duplikat."),
            default => null,
        };
    }

    /**
     * Kandidat duplikat untuk satu laporan, urut dari yang terdekat.
     *
     * @return Collection<int, array{report: DamageReport, distance: float, seconds: int}>
     */
    public function candidatesFor(DamageReport $report): Collection
    {
        if (! $this->hasGeoAndTime($report)) {
            return collect();
        }

        $window = $this->windowSeconds();

        return $this->partnerQuery([$report->heritage_site_id], $report->submitted_at->copy()->subSeconds($window), $report->submitted_at->copy()->addSeconds($window))
            ->whereKeyNot($report->id)
            ->with('user:id,name')
            ->get()
            ->map(fn (DamageReport $other) => $this->compare($report, $other))
            ->filter(fn (array $c) => $c['distance'] <= $this->radius())
            ->sortBy('distance')
            ->values();
    }

    /**
     * Kandidat untuk semua kartu antrean dalam SATU query.
     *
     * @param  iterable<DamageReport>  $reports
     * @return array<int, list<int>>  id laporan => id kandidat (terdekat dulu)
     */
    public function candidateMapFor(iterable $reports): array
    {
        $reports = collect($reports)->filter(fn (DamageReport $r) => $r->status === ReportStatus::Pending->value
            && ! $r->isDuplicate()
            && $this->hasGeoAndTime($r));

        if ($reports->isEmpty()) {
            return [];
        }

        $window = $this->windowSeconds();
        $partners = $this->partnerQuery(
            $reports->pluck('heritage_site_id')->unique()->all(),
            $reports->min('submitted_at')->copy()->subSeconds($window),
            $reports->max('submitted_at')->copy()->addSeconds($window),
        )->get(['id', 'heritage_site_id', 'latitude', 'longitude', 'submitted_at'])->groupBy('heritage_site_id');

        return $reports->mapWithKeys(fn (DamageReport $r) => [
            $r->id => ($partners[$r->heritage_site_id] ?? collect())
                ->reject(fn (DamageReport $o) => $o->id === $r->id)
                ->map(fn (DamageReport $o) => $this->compare($r, $o))
                ->filter(fn (array $c) => $c['distance'] <= $this->radius() && abs($c['seconds']) <= $window)
                ->sortBy('distance')
                ->map(fn (array $c) => $c['report']->id)
                ->values()
                ->all(),
        ])->filter()->all();
    }

    /**
     * Laporan yang boleh dipilih manual sebagai induk (situs sama, bukan duplikat).
     *
     * @return Collection<int, DamageReport>
     */
    public function eligibleParents(DamageReport $report, int $limit = 100): Collection
    {
        return DamageReport::query()
            ->where('heritage_site_id', $report->heritage_site_id)
            ->whereKeyNot($report->id)
            ->notDuplicate()
            ->with('user:id,name')
            ->latest('submitted_at')
            ->limit($limit)
            ->get(['id', 'user_id', 'status', 'severity', 'description', 'is_anonymous', 'submitted_at']);
    }

    private function partnerQuery(array $siteIds, $from, $to): Builder
    {
        return DamageReport::query()
            ->whereIn('heritage_site_id', $siteIds)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('submitted_at', [$from, $to])
            ->where('status', '!=', ReportStatus::Rejected->value)
            ->notDuplicate();
    }

    /** @return array{report: DamageReport, distance: float, seconds: int} */
    private function compare(DamageReport $a, DamageReport $b): array
    {
        return [
            'report' => $b,
            'distance' => ExifReader::distanceMeters((float) $a->latitude, (float) $a->longitude, (float) $b->latitude, (float) $b->longitude),
            'seconds' => (int) $a->submitted_at->diffInSeconds($b->submitted_at, false),
        ];
    }

    private function hasGeoAndTime(DamageReport $r): bool
    {
        return $r->latitude !== null && $r->longitude !== null && $r->submitted_at !== null;
    }

    private function radius(): float
    {
        return (float) config('moderation.duplicate_radius_m', 50);
    }

    private function windowSeconds(): int
    {
        return (int) config('moderation.duplicate_window_days', 7) * 86400;
    }
}
