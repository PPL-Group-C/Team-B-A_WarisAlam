<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\Browser\Concerns\UsesDemoSites;
use Tests\Browser\Pages\MapPage;
use Tests\DuskTestCase;

/**
 * PBI-10 (SCRUM-7): map with site markers coloured by status.
 */
class MapMarkersTest extends DuskTestCase
{
    use UsesDemoSites;

    private const GREEN = '#059669';

    private const YELLOW = '#f59e0b';

    private const RED = '#dc2626';

    public function test_public_visitor_sees_all_sites_as_markers(): void
    {
        $this->browseDemoMap(function (Browser $browser) {
            $browser->visit(new MapPage)
                ->assertTitle('Jelajah Peta - WarisAlam')
                ->waitForMarkers()
                ->assertMarkerCount(MapPage::TOTAL_SITES)
                ->assertPresent('#site-map .leaflet-tile-pane')
                ->assertSeeIn('#summary-total', (string) MapPage::TOTAL_SITES)
                ->assertSeeIn('#summary-at-risk', '10')
                ->assertMissing('@empty')
                ->assertMissing('@error');
        });
    }

    public function test_safe_site_marker_is_green(): void
    {
        $this->assertMarkerColor('Candi Borobudur', self::GREEN);
    }

    public function test_watch_site_marker_is_yellow(): void
    {
        $this->assertMarkerColor('Candi Cetho', self::YELLOW);
    }

    public function test_threatened_site_marker_is_red(): void
    {
        $this->assertMarkerColor('Taman Nasional Gunung Leuser', self::RED);
    }

    public function test_every_marker_color_matches_its_status(): void
    {
        $this->browseDemoMap(function (Browser $browser) {
            $browser->visit(new MapPage)->waitForMarkers();

            $mismatches = $browser->script('
                const colors = { safe: "#059669", watch: "#f59e0b", threatened: "#dc2626" };
                return window.warisAlamMarkers.getLayers()
                    .filter((m) => !m.options.icon.options.html.includes(colors[m.options.status]))
                    .map((m) => m.options.site.name);
            ')[0];

            $this->assertSame([], $mismatches);
        });
    }

    public function test_shows_message_when_there_are_no_sites(): void
    {
        $this->browse(function (Browser $browser) {
            $fake = MapPage::fakeSitesResponse($browser, ['data' => []]);

            try {
                $browser->visit(new MapPage)
                    ->waitFor('@empty')
                    ->assertSeeIn('@empty', 'Belum ada situs yang terdata.')
                    ->assertMissing('@loading')
                    ->assertSeeIn('#summary-total', '0')
                    ->assertMissing('.site-marker')
                    ->assertMissing('.site-cluster');
            } finally {
                MapPage::removeSitesResponse($browser, $fake);
            }
        });
    }

    public function test_shows_error_when_site_data_fails_to_load(): void
    {
        $this->browse(function (Browser $browser) {
            $fake = MapPage::fakeSitesResponse($browser, null, 500);

            try {
                $browser->visit(new MapPage)
                    ->waitFor('@error')
                    ->assertSeeIn('@error', 'Data situs gagal dimuat')
                    ->assertMissing('@loading')
                    ->assertMissing('.site-marker');
            } finally {
                MapPage::removeSitesResponse($browser, $fake);
            }
        });
    }

    /**
     * NFR-01: the map with 100 sites is ready within 3 seconds.
     */
    public function test_map_with_100_sites_loads_within_3_seconds(): void
    {
        $sites = collect(range(1, 100))->map(fn (int $i) => [
            'id' => $i,
            'name' => "Situs Uji {$i}",
            'type' => $i % 2 ? 'cultural' : 'natural',
            'category' => 'Cagar Budaya Nasional',
            'province' => 'Provinsi '.($i % 10),
            'city' => 'Kota Uji',
            'lat' => -8 + ($i % 10) * 0.9,
            'lng' => 96 + intdiv($i, 10) * 4,
            'status' => ['safe', 'watch', 'threatened'][$i % 3],
            'threat_score' => [0, 7, 20][$i % 3],
            'detail_url' => "/sites/{$i}",
        ])->all();

        $this->browse(function (Browser $browser) use ($sites) {
            $fake = MapPage::fakeSitesResponse($browser, ['data' => $sites]);

            try {
                $browser->visit(new MapPage)
                    ->waitForMarkers(3)
                    ->assertMarkerCount(100);

                $elapsed = $browser->script('return performance.now();')[0];

                $this->assertLessThanOrEqual(3000, $elapsed, "Map took {$elapsed} ms to show 100 sites.");
            } finally {
                MapPage::removeSitesResponse($browser, $fake);
            }
        });
    }

    private function assertMarkerColor(string $site, string $color): void
    {
        $this->browseDemoMap(function (Browser $browser) use ($site, $color) {
            $browser->visit(new MapPage)
                ->waitForMarkers()
                ->zoomToSite($site);

            $pinColor = $browser->script(sprintf(
                'return document.querySelector(%s).querySelector(".site-marker__pin").style.getPropertyValue("--pin-color").trim();',
                json_encode(MapPage::markerSelector($site)),
            ))[0];

            $this->assertSame($color, $pinColor, "{$site} has the wrong marker colour.");
        });
    }
}
