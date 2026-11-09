<?php

namespace App\Models;

use App\Enums\DamageCategory;
use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DamageReport extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'category' => DamageCategory::class,
            'is_anonymous' => 'boolean',
            'declaration_accepted_at' => 'datetime',
            'submitted_at' => 'datetime',
            'moderated_at' => 'datetime', // F08
        ];
    }

    // ---- F08 Moderasi (Akmal) -------------------------------------------
    // Kolom status sengaja TIDAK di-cast ke ReportStatus karena form laporan
    // masih menulis "draft"/"submitted". Pakai statusEnum() untuk membaca.

    public function statusEnum(): ?ReportStatus
    {
        return ReportStatus::tryFrom((string) $this->status);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ReportStatusHistory::class, 'report_id')->latest('id');
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(HeritageSite::class, 'heritage_site_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ReportPhoto::class);
    }
}
