<?php

namespace App\Models;

use App\Enums\DamageCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Gate;

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
        ];
    }

    public function reporterNameFor(?User $viewer): string
    {
    $canSee = ! $this->is_anonymous
        || ($viewer && Gate::forUser($viewer)->allows('view-reporter-identity', $this));

    return $canSee ? $this->user->name : 'Pelapor Anonim';
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
