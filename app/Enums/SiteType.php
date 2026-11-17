<?php

namespace App\Enums;

/** Tipe situs (FR-07). Label sama dengan filter peta (Fasya). */
enum SiteType: string
{
    case Cultural = 'cultural';
    case Natural = 'natural';

    public function label(): string
    {
        return match ($this) {
            self::Cultural => 'Cagar Budaya',
            self::Natural => 'Cagar Alam',
        };
    }

    /** Lucide icon name */
    public function icon(): string
    {
        return match ($this) {
            self::Cultural => 'landmark',
            self::Natural => 'trees',
        };
    }
}
