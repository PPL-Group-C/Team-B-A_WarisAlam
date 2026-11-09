<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class MapController extends Controller
{
    /**
     * Fields sent to the map. Reporter data is never included.
     */
    private const FIELDS = ['id', 'name', 'type', 'category', 'province', 'city', 'lat', 'lng', 'status', 'threat_score'];

    public function sites(): JsonResponse
    {
        // Temporary source until the `sites` table (SCRUM-21) is merged.
        $sites = collect(require database_path('data/demo_sites.php'))
            ->map(fn (array $site) => [
                ...array_intersect_key($site, array_flip(self::FIELDS)),
                'detail_url' => url('/sites/'.$site['id']),
            ])
            ->values();

        return response()->json(['data' => $sites]);
    }
}
