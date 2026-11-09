<?php

namespace App\Enums;

enum DamageCategory: string
{
    case Vandalism = 'vandalism';
    case Waste = 'waste';
    case PhysicalDamage = 'physical_damage';
    case FloraFauna = 'flora_fauna';
    case LandUseChange = 'land_use_change';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Vandalism => 'Vandalisme',
            self::Waste => 'Sampah & Limbah',
            self::PhysicalDamage => 'Kerusakan Fisik',
            self::FloraFauna => 'Flora & Fauna',
            self::LandUseChange => 'Alih Fungsi Lahan',
            self::Other => 'Ancaman Lain',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Vandalism => 'Coretan cat, spidol, ukiran liar artefak',
            self::Waste => 'Penumpukan sampah ilegal di area zona suci',
            self::PhysicalDamage => 'Runtuhan batu andesit, pelapukan, gempa',
            self::FloraFauna => 'Perburuan liar, penebangan pohon keramat',
            self::LandUseChange => 'Pembangunan tanpa izin di zona penyangga',
            self::Other => 'Pencurian artefak atau erosi hidrologis',
        };
    }

    /** Lucide icon name */
    public function icon(): string
    {
        return match ($this) {
            self::Vandalism => 'spray-can',
            self::Waste => 'trash-2',
            self::PhysicalDamage => 'brick-wall',
            self::FloraFauna => 'trees',
            self::LandUseChange => 'land-plot',
            self::Other => 'ellipsis',
        };
    }
}
