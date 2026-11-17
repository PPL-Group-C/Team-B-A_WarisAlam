<?php

namespace Tests\Browser\Concerns;

use App\Enums\SiteStatus;
use Closure;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\MapPage;

/**
 * Browser tests for the map run against the 18 demo sites, served in the
 * browser instead of /map/sites. The tests then do not depend on (or change)
 * whatever is in the local database. The real endpoint is covered by
 * Tests\Feature\MapSitesTest and Tests\Browser\MapDatabaseTest.
 */
trait UsesDemoSites
{
    protected function browseDemoMap(Closure $callback): void
    {
        $this->browse(function (Browser $browser) use ($callback) {
            $fake = MapPage::fakeSitesResponse($browser, ['data' => static::demoSites()]);

            try {
                $callback($browser);
            } finally {
                MapPage::removeSitesResponse($browser, $fake);
            }
        });
    }

    /**
     * The demo sites in the same shape as the /map/sites response.
     *
     * @return list<array<string, mixed>>
     */
    protected static function demoSites(): array
    {
        $sites = require database_path('data/demo_sites.php');

        return array_map(fn (array $site, int $i) => [
            'id' => $i + 1,
            'name' => $site['name'],
            'type' => $site['type'],
            'category' => $site['category'],
            'province' => $site['province'],
            'city' => $site['city'],
            'lat' => $site['lat'],
            'lng' => $site['lng'],
            'status' => SiteStatus::fromScore($site['threat_score'])->value,
            'threat_score' => $site['threat_score'],
            'detail_url' => url('/sites/'.($i + 1)),
        ], $sites, array_keys($sites));
    }
}
