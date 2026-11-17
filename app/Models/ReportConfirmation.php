<?php

namespace App\Models;

use App\Enums\ConfirmationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Konfirmasi / bantahan warga atas sebuah laporan.
 * Unik per (report_id, user_id): satu warga satu suara per laporan.
 */
class ReportConfirmation extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => ConfirmationType::class,
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(DamageReport::class, 'report_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
