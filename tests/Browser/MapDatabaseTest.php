<?php

namespace Tests\Browser;

use App\Models\HeritageSite;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\MapPage;
use Tests\DuskTestCase;

/**
 * PBI-10 (SCRUM-7): the real /map/sites endpoint feeds the map from the
 * heritage_sites table. Uses the local database as it is (read only), so run
 * `php artisan migrate` first, and optionally
 * `php artisan db:seed --class=MapDemoSiteSeeder` for demo data.
 */
class MapDatabaseTest extends DuskTestCase
{
    public function test_map_shows_every_site_from_the_database(): void
    {
        $sites = HeritageSite::query()->whereNotNull('latitude')->whereNotNull('longitude');
        $count = $sites->count();

        $this->browse(function (Browser $browser) use ($sites, $count) {
            $browser->visit(new MapPage);

            if ($count === 0) {
                $browser->waitFor('@empty')->assertMissing('@error');

                return;
            }

            $browser->waitForMarkers()
                ->assertMissing('@error')
                ->assertMarkerCount($count)
                ->assertSeeIn('#summary-total', (string) $count);

            $first = $sites->orderBy('name')->first();
            $site = $browser->script('return window.warisAlamSites[0];')[0];

            $this->assertSame($first->name, $site['name']);
            $this->assertSame($first->status, $site['status']);
            $this->assertSame(route('sites.show', $first), $site['detail_url']);
        });
    }
}
