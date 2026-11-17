<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Foto galeri situs (PBI-13). */
class SitePhoto extends Model
{
    protected $guarded = [];

    public function site(): BelongsTo
    {
        return $this->belongsTo(HeritageSite::class, 'heritage_site_id');
    }

    /** Urutan tampil di galeri. */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    /** URL publik: path disk "public", atau URL penuh apa adanya. */
    public function url(): string
    {
        return Str::startsWith($this->path, ['http://', 'https://'])
            ? $this->path
            : Storage::disk('public')->url($this->path);
    }
}
