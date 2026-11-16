<?php

namespace App\Enums;

enum SiteStatus: string
{
    case Safe = 'safe';
    case Watch = 'watch';
    case Threatened = 'threatened';

    public const WATCH_FROM = 5.0;

    public const THREATENED_FROM = 15.0;

    /** Dipakai scheduler skor ancaman (Sprint 2) untuk menentukan status baru. */
    public static function fromScore(float $score): self
    {
        return match (true) {
            $score >= self::THREATENED_FROM => self::Threatened,
            $score >= self::WATCH_FROM => self::Watch,
            default => self::Safe,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Safe => 'Aman',
            self::Watch => 'Waspada',
            self::Threatened => 'Terancam',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Safe => 'Belum ada ancaman berarti dari laporan terverifikasi.',
            self::Watch => 'Ada laporan kerusakan terverifikasi yang perlu dipantau.',
            self::Threatened => 'Ancaman tinggi; situs perlu penanganan prioritas.',
        };
    }

    public function scoreRange(): string
    {
        return match ($this) {
            self::Safe => 'skor < 5',
            self::Watch => '5 ≤ skor < 15',
            self::Threatened => 'skor ≥ 15',
        };
    }

    /** Warna hex marker peta. */
    public function color(): string
    {
        return match ($this) {
            self::Safe => '#059669',
            self::Watch => '#f59e0b',
            self::Threatened => '#dc2626',
        };
    }

    /** Kelas Tailwind untuk badge status. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Safe => 'bg-emerald-100 text-emerald-800',
            self::Watch => 'bg-amber-100 text-amber-800',
            self::Threatened => 'bg-red-100 text-red-700',
        };
    }

    /** Lucide icon name */
    public function icon(): string
    {
        return match ($this) {
            self::Safe => 'shield-check',
            self::Watch => 'eye',
            self::Threatened => 'triangle-alert',
        };
    }
}
