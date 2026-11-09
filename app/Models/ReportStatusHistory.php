<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportStatusHistory extends Model
{
    protected $fillable = ['report_id', 'from_status', 'to_status', 'changed_by', 'note'];

    protected function casts(): array
    {
        return [
            'from_status' => ReportStatus::class,
            'to_status' => ReportStatus::class,
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(DamageReport::class, 'report_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
