<?php

namespace App\Enums;

/** Suara warga atas laporan orang lain (PBI-28 / PBI-30). */
enum ConfirmationType: string
{
    case Confirm = 'confirm';
    case Dispute = 'dispute';

    public function label(): string
    {
        return match ($this) {
            self::Confirm => 'Konfirmasi',
            self::Dispute => 'Bantahan',
        };
    }
}
