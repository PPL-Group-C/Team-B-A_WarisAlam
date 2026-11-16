<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
            'photos' => $this->photos->map(fn ($p) => [
                'url' => Storage::url($p->path),
                'exif_valid' => $p->exif_valid,
            ]),
        ];
    }
}