<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportPhoto extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'exif_valid' => 'boolean',
            'taken_at' => 'datetime',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(DamageReport::class, 'damage_report_id');
    }
}
