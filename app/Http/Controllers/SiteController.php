<?php

namespace App\Http\Controllers;

use App\Enums\SiteType;
use App\Models\HeritageSite;
use App\Models\SitePhoto;
use Illuminate\View\View;

/**
 * Halaman publik detail situs (Raihan, FR-11):
 * - PBI-13: sejarah, fakta, galeri foto
 */
class SiteController extends Controller
{
    public function show(HeritageSite $site): View
    {
        return view('sites.show', [
            'site' => $site,
            'type' => SiteType::tryFrom((string) $site->type) ?? SiteType::Cultural,
            'facts' => $this->facts($site),
            'photos' => SitePhoto::where('heritage_site_id', $site->id)->ordered()->get(),
        ]);
    }

    /** @return list<array{label: string, value: string}> */
    private function facts(HeritageSite $site): array
    {
        $facts = is_array($site->facts) ? $site->facts : json_decode((string) $site->facts, true);

        return collect(is_array($facts) ? $facts : [])
            ->filter(fn ($fact) => is_array($fact) && filled($fact['label'] ?? null) && filled($fact['value'] ?? null))
            ->map(fn (array $fact) => ['label' => (string) $fact['label'], 'value' => (string) $fact['value']])
            ->values()
            ->all();
    }
}
