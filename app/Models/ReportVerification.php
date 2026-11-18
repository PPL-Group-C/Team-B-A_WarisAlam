<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportVerification extends Model
{
    protected $guarded = [];

    public function report(): BelongsTo
    {
        return $this->belongsTo(DamageReport::class, 'damage_report_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
