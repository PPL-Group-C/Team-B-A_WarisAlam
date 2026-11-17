<?php

namespace App\Models;

use App\Enums\DamageCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Gate;

class DamageReport extends Model
{
    /** Confirmations needed (and more than disputes) to show "Terverifikasi warga". */
    public const CONFIRMS_TO_VERIFY = 3;

    /** Disputes needed (and at least as many as confirmations) to flag "Diperdebatkan". */
    public const DISPUTES_TO_FLAG = 2;

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

    public function verifications(): HasMany
    {
        return $this->hasMany(ReportVerification::class);
    }

    /** Adds confirms_count and disputes_count to the query. */
    public function scopeWithVerificationCounts(Builder $query): Builder
    {
        return $query->withCount([
            'verifications as confirms_count' => fn ($v) => $v->where('verdict', 'confirm'),
            'verifications as disputes_count' => fn ($v) => $v->where('verdict', 'dispute'),
        ]);
    }

    /** verified | disputed | unverified (needs the counts from withVerificationCounts()). */
    public function verificationStatus(): string
    {
        $confirms = (int) $this->confirms_count;
        $disputes = (int) $this->disputes_count;

        if ($confirms >= self::CONFIRMS_TO_VERIFY && $confirms > $disputes) {
            return 'verified';
        }

        if ($disputes >= self::DISPUTES_TO_FLAG && $disputes >= $confirms) {
            return 'disputed';
        }

        return 'unverified';
    }

    public function reporterNameFor(?User $viewer): string
    {
        $canSee = ! $this->is_anonymous
            || ($viewer && Gate::forUser($viewer)->allows('view-reporter-identity', $this));

        return $canSee ? $this->user->name : 'Pelapor Anonim';
    }
}
