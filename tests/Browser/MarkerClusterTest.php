<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\Browser\Concerns\UsesDemoSites;
use Tests\Browser\Pages\MapPage;
use Tests\DuskTestCase;

/**
 * PBI-11 (SCRUM-8): marker clustering and site summary popup.
 */
class MarkerClusterTest extends DuskTestCase
{
    use UsesDemoSites;

    public function test_nearby_markers_are_clustered_at_country_zoom(): void
    {
        $this->browseDemoMap(function (Browser $browser) {
            $browser->visit(new MapPage)
                ->waitForMarkers()
                ->waitFor('.site-cluster');

            // Every site is counted once, either inside a cluster or as a single marker.
            [$clusters, $inClusters, $singles] = $browser->script('
                const clusters = [...document.querySelectorAll(".site-cluster")];
                return [
                    clusters.length,
                    clusters.reduce((sum, el) => sum + Number(el.textContent.trim()), 0),
                    document.querySelectorAll(".site-marker").length,
                ];
            ')[0];

            $this->assertGreaterThan(0, $clusters);
            $this->assertLessThan(MapPage::TOTAL_SITES, $clusters + $singles);
            $this->assertSame(MapPage::TOTAL_SITES, $inClusters + $singles);
        });
    }

    public function test_cluster_takes_the_color_of_its_most_threatened_site(): void
    {
        $this->browseDemoMap(function (Browser $browser) {
            $browser->visit(new MapPage)
                ->waitForMarkers()
                ->waitFor('.site-cluster');

            $mismatches = $browser->script('
                const colors = { safe: "#059669", watch: "#f59e0b", threatened: "#dc2626" };
                const rank = { safe: 0, watch: 1, threatened: 2 };
                const layer = window.warisAlamMarkers;
                const parents = new Set(layer.getLayers().map((m) => layer.getVisibleParent(m)).filter(Boolean));

                return [...parents]
                    .filter((p) => p.getChildCount)
                    .filter((cluster) => {
                        const worst = cluster.getAllChildMarkers()
                            .map((m) => m.options.status)
                            .reduce((a, b) => (rank[b] > rank[a] ? b : a), "safe");
                        return !cluster._icon.innerHTML.includes(colors[worst]);
                    })
                    .length;
            ')[0];

            $this->assertSame(0, $mismatches);
        });
    }

    public function test_clicking_a_cluster_zooms_in_until_markers_are_visible(): void
    {
        $this->browseDemoMap(function (Browser $browser) {
            $browser->visit(new MapPage)
                ->waitForMarkers()
                ->waitFor('.site-cluster');

            $zoomBefore = $browser->script('return window.warisAlamMap.getZoom();')[0];

            // Pick one site that is hidden inside the biggest cluster.
            $site = $browser->script('
                const layer = window.warisAlamMarkers;
                const clusters = new Map();
                layer.getLayers().forEach((marker) => {
                    const parent = layer.getVisibleParent(marker);
                    if (parent && parent.getChildCount) {
                        clusters.set(parent, [...(clusters.get(parent) ?? []), marker]);
                    }
                });
                const [, markers] = [...clusters].sort((a, b) => b[1].length - a[1].length)[0];
                return markers[0].options.site.name;
            ')[0];

            $this->assertFalse($this->markerIsShown($browser, $site));

            // Keep clicking the cluster that holds this site: each click zooms in further,
            // until the site shows up as its own marker.
            for ($click = 1; $click <= 8 && ! $this->markerIsShown($browser, $site); $click++) {
                $zoom = $browser->script('return window.warisAlamMap.getZoom();')[0];

                $browser->script(sprintf('
                    document.getElementById("dusk-cluster")?.removeAttribute("id");
                    const layer = window.warisAlamMarkers;
                    const marker = layer.getLayers().find((m) => m.options.site.name === %s);
                    layer.getVisibleParent(marker)._icon.id = "dusk-cluster";
                ', json_encode($site)));

                $browser->click('#dusk-cluster')
                    ->waitUntil('window.warisAlamMap.getZoom() > '.$zoom)
                    ->pause(400); // let the zoom animation finish
            }

            $this->assertTrue($this->markerIsShown($browser, $site), "{$site} is still hidden in a cluster.");
            $this->assertGreaterThan($zoomBefore, $browser->script('return window.warisAlamMap.getZoom();')[0]);
        });
    }

    private function markerIsShown(Browser $browser, string $site): bool
    {
        return (bool) $browser->script(sprintf(
            'return document.querySelector(%s) !== null;',
            json_encode(MapPage::markerSelector($site)),
        ))[0];
    }

    public function test_clicking_a_marker_shows_site_summary_with_detail_link(): void
    {
        $this->browseDemoMap(function (Browser $browser) {
            $browser->visit(new MapPage)
                ->waitForMarkers()
                ->zoomToSite('Candi Borobudur')
                ->click(MapPage::markerSelector('Candi Borobudur'))
                ->waitFor('.leaflet-popup .site-popup')
                ->within('.leaflet-popup .site-popup', function (Browser $popup) {
                    $popup->assertSeeIn('.site-popup__title', 'Candi Borobudur')
                        ->assertSeeIn('.site-popup__meta', 'Cagar Budaya')
                        ->assertSeeIn('.site-popup__meta', 'Jawa Tengah')
                        ->assertSeeIn('.site-popup__status', 'Aman')
                        ->assertSeeLink('Lihat Detail Situs');
                });

            $href = $browser->attribute('.site-popup__link', 'href');
            $this->assertStringEndsWith('/sites/1', $href);
        });
    }

    public function test_popup_shows_status_of_threatened_site(): void
    {
        $this->browseDemoMap(function (Browser $browser) {
            $browser->visit(new MapPage)
                ->waitForMarkers()
                ->zoomToSite('Taman Nasional Gunung Leuser')
                ->click(MapPage::markerSelector('Taman Nasional Gunung Leuser'))
                ->waitFor('.leaflet-popup .site-popup')
                ->assertSeeIn('.site-popup__meta', 'Cagar Alam')
                ->assertSeeIn('.site-popup__meta', 'Aceh')
                ->assertSeeIn('.site-popup__status', 'Terancam');
        });
    }

    public function test_hovering_a_marker_previews_the_popup(): void
    {
        $this->browseDemoMap(function (Browser $browser) {
            $browser->visit(new MapPage)
                ->waitForMarkers()
                ->zoomToSite('Kompleks Candi Prambanan')
                ->mouseover(MapPage::markerSelector('Kompleks Candi Prambanan'))
                ->waitFor('.leaflet-popup .site-popup')
                ->assertSeeIn('.site-popup__title', 'Kompleks Candi Prambanan');
        });
    }
}
