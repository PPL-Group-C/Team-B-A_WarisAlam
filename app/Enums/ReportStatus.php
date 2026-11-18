<?php

namespace App\Enums;

/**
 * Status laporan kerusakan (alur SRS):
 * pending → verified → forwarded → in_progress → resolved
 * pending → rejected
 *
 * Catatan: form laporan (Miftahul) saat ini juga menulis "draft"/"submitted";
 * nilai itu sengaja tidak ada di sini (lihat ReportStatus::tryFrom).
 */
enum ReportStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Forwarded = 'forwarded';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Kurasi',
            self::Verified => 'Terverifikasi',
            self::Rejected => 'Ditolak',
            self::Forwarded => 'Diteruskan',
            self::InProgress => 'Dalam Penanganan',
            self::Resolved => 'Selesai',
        };
    }

    /** Kelas Tailwind untuk badge status. */
    public function badgeClasses(): string
    {
        return match ($this) {
            // Warna mengikuti palet Figma (lihat moderation/layout.blade.php).
            self::Pending => 'bg-leaf text-leaf-ink',
            self::Verified => 'bg-jade text-jade-ink',
            self::Rejected => 'bg-danger-bg text-danger-ink',
            self::Forwarded => 'bg-jade text-jade-ink',
            self::InProgress => 'bg-mint-200 text-forest',
            self::Resolved => 'bg-forest text-white',
        };
    }

    /** Status yang menandakan laporan dinilai valid oleh moderator. */
    public static function accepted(): array
    {
        return [self::Verified, self::Forwarded, self::InProgress, self::Resolved];
    }

    /** @return list<string> */
    public static function values(?array $cases = null): array
    {
        return array_map(fn (self $s) => $s->value, $cases ?? self::cases());
    }
}
