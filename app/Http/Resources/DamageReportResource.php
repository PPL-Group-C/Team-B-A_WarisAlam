<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DamageReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'site' => $this->site?->name,
            'category' => $this->category?->value,
            'severity' => $this->severity,
            'description' => $this->description,
            'status' => $this->status,
            'is_anonymous' => $this->is_anonymous,
            'reporter' => $this->reporterNameFor($request->user()), // never user_id or email
            'verification' => [
                'confirms' => (int) $this->confirms_count,
                'disputes' => (int) $this->disputes_count,
                'status' => $this->verificationStatus(),
            ],
            'photos' => $this->photos->map(fn ($p) => [
                'url' => asset('storage/'.$p->path),
                'exif_valid' => $p->exif_valid,
            ]),
        ];
    }
}
