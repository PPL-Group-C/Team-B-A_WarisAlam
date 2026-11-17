<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Warga yang mengadopsi situs. Sprint 1 hanya menghitung jumlahnya (PBI-14);
 * alur adopsi menyusul di Sprint 2 (PBI-37).
 */
class SiteAdoption extends Model
{
    protected $guarded = [];

    public function site(): BelongsTo
    {
        return $this->belongsTo(HeritageSite::class, 'heritage_site_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
