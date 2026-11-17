<?php

namespace App\Http\Controllers;

use App\Models\HeritageSite;
use Illuminate\Http\JsonResponse;

class MapController extends Controller
{
    /**
     * Columns read for the map. Reporter data is never included.
     */
    private const COLUMNS = ['id', 'name', 'type', 'category', 'province', 'city', 'latitude', 'longitude', 'status', 'threat_score'];

    /**
     * All sites for the public map, loaded once. Clustering and filtering
     * happen in the browser (NFR-01).
     */
    public function sites(): JsonResponse
    {
        $sites = HeritageSite::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('name')
            ->get(self::COLUMNS)
            ->map(fn (HeritageSite $site) => [
                'id' => $site->id,
                'name' => $site->name,
                'type' => $site->type,
                'category' => $site->category,
                'province' => $site->province,
                'city' => $site->city,
                'lat' => (float) $site->latitude,
                'lng' => (float) $site->longitude,
                'status' => $site->status,
                'threat_score' => (float) $site->threat_score,
                'detail_url' => route('sites.show', $site),
            ]);

        return response()->json(['data' => $sites]);
    }
}
