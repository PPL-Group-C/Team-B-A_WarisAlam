<?php

namespace App\Support;

/**
 * Helper tampilan laporan untuk halaman moderasi (F08).
 */
final class ReportPresenter
{
    private const SEVERITY_LABELS = [1 => 'Ringan', 2 => 'Rendah', 3 => 'Sedang', 4 => 'Tinggi', 5 => 'Kritis'];

    /** Sama dengan judul level di form laporan (DamageReportController). */
    public const SEVERITY_TITLES = [
        1 => 'Kerusakan Kosmetik',
        2 => 'Kerusakan Permukaan Terbatas',
        3 => 'Kerusakan Material Parsial',
        4 => 'Kerusakan Struktural Luas',
        5 => 'Ancaman Langsung',
    ];

    public static function severityShort(int $severity): string
    {
        return self::SEVERITY_LABELS[$severity] ?? '-';
    }

    /** #REP-0089 */
    public static function code(int $id): string
    {
        return '#REP-'.str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }

    /** "REP-0089", "#89", "89" → 89; selain itu null. */
    public static function parseCode(string $term): ?int
    {
        return preg_match('/^\s*#?\s*(?:rep-?)?\s*0*(\d+)\s*$/i', $term, $m) ? (int) $m[1] : null;
    }

    public static function severityLabel(?int $severity): string
    {
        return $severity ? "Level {$severity} - ".(self::SEVERITY_LABELS[$severity] ?? '') : 'Level -';
    }

    public static function severityBadge(?int $severity): string
    {
        return match (true) {
            $severity >= 4 => 'bg-red-100 text-red-700',
            $severity === 3 => 'bg-amber-100 text-amber-800',
            default => 'bg-mint-200 text-forest',
        };
    }

    public static function meters(?float $meters): string
    {
        if ($meters === null) {
            return '-';
        }

        return $meters < 1000
            ? number_format($meters, 1, ',', '.').' meter'
            : number_format($meters / 1000, 2, ',', '.').' km';
    }

    public static function duration(?int $seconds): string
    {
        if ($seconds === null) {
            return '-';
        }

        $seconds = abs($seconds);
        $d = intdiv($seconds, 86400);
        $h = intdiv($seconds % 86400, 3600);
        $m = intdiv($seconds % 3600, 60);

        return trim(($d ? "{$d} hari " : '').($h ? "{$h} jam " : '').($d ? '' : "{$m} menit")) ?: '0 menit';
    }

    public static function coordinate(mixed $lat, mixed $lng): string
    {
        if ($lat === null || $lng === null) {
            return '-';
        }

        return sprintf('%.6f°, %.6f°', $lat, $lng);
    }
}
