<?php

namespace App\Models;

use App\Enums\ConfirmationType;
use App\Enums\DamageCategory;
use App\Enums\ReportStatus;
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
            'moderated_at' => 'datetime', // F08
            'rejected_as_duplicate' => 'boolean', // F08
        ];
    }

    public function reporterNameFor(?User $viewer): string
    {
        $canSee = ! $this->is_anonymous
            || ($viewer && Gate::forUser($viewer)->allows('view-reporter-identity', $this));

        return $canSee ? $this->user->name : 'Pelapor Anonim';
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

    /** Laporan induk bila laporan ini ditandai duplikat. */
    public function duplicateOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'duplicate_of_id');
    }

    /** Laporan lain yang ditandai sebagai duplikat dari laporan ini. */
    public function duplicates(): HasMany
    {
        return $this->hasMany(self::class, 'duplicate_of_id');
    }

    public function isDuplicate(): bool
    {
        return $this->duplicate_of_id !== null || $this->rejected_as_duplicate;
    }

    /** Konfirmasi / bantahan warga (PBI-28/30). */
    public function confirmations(): HasMany
    {
        return $this->hasMany(ReportConfirmation::class, 'report_id');
    }

    /** Tambah atribut confirm_count & dispute_count tanpa N+1. */
    public function scopeWithConfirmationCounts(Builder $query): void
    {
        $query->withCount([
            'confirmations as confirm_count' => fn (Builder $q) => $q->where('type', ConfirmationType::Confirm->value),
            'confirmations as dispute_count' => fn (Builder $q) => $q->where('type', ConfirmationType::Dispute->value),
        ]);
    }

    /** Kecualikan laporan duplikat, mis. untuk skor ancaman situs (FR-17). */
    public function scopeNotDuplicate(Builder $query): void
    {
        $query->whereNull($query->qualifyColumn('duplicate_of_id'))
            ->where($query->qualifyColumn('rejected_as_duplicate'), false);
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
}