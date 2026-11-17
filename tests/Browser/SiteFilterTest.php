<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\Browser\Concerns\UsesDemoSites;
use Tests\Browser\Pages\MapPage;
use Tests\DuskTestCase;

/**
 * PBI-12 (SCRUM-9): filter sites by type, province and status, and search by name.
 */
class SiteFilterTest extends DuskTestCase
{
    use UsesDemoSites;

    public function test_search_by_name_updates_markers_without_reloading_the_page(): void
    {
        $this->browseDemoMap(function (Browser $browser) {
            $this->openMap($browser);

            // A full page reload would wipe this flag.
            $browser->script('window.duskNoReload = true;');

            $browser->type('@search', 'Borobudur')
                ->waitForTextIn('@count', 'Menampilkan 1 dari 18 situs')
                ->assertMarkerCount(1)
                ->assertSiteListCount(1)
                ->assertSeeIn('@siteList', 'Candi Borobudur');

            $this->assertTrue($browser->script('return window.duskNoReload === true;')[0]);
        });
    }

    public function test_search_ignores_letter_case(): void
    {
        $this->browseDemoMap(function (Browser $browser) {
            $this->openMap($browser);

            $browser->type('@search', 'CANDI')
                ->waitForTextIn('@count', 'Menampilkan 4 dari 18 situs')
                ->assertMarkerCount(4);
        });
    }

    public function test_filter_by_type(): void
    {
        $this->browseDemoMap(function (Browser $browser) {
            $this->openMap($browser);

            $browser->select('@type', 'natural')
                ->waitForTextIn('@count', 'Menampilkan 10 dari 18 situs')
                ->assertMarkerCount(10);

            $browser->select('@type', 'cultural')
                ->waitForTextIn('@count', 'Menampilkan 8 dari 18 situs')
                ->assertMarkerCount(8);
        });
    }

    public function test_filter_by_province(): void
    {
        $this->browseDemoMap(function (Browser $browser) {
            $this->openMap($browser);

            $browser->select('@province', 'Jambi')
                ->waitForTextIn('@count', 'Menampilkan 2 dari 18 situs')
                ->assertMarkerCount(2)
                ->assertSeeIn('@siteList', 'Kawasan Percandian Muaro Jambi')
                ->assertSeeIn('@siteList', 'Taman Nasional Kerinci Seblat');
        });
    }

    public function test_filter_by_status(): void
    {
        $this->browseDemoMap(function (Browser $browser) {
            $this->openMap($browser);

            $browser->select('@status', 'threatened')
                ->waitForTextIn('@count', 'Menampilkan 4 dari 18 situs')
                ->assertMarkerCount(4);

            $statuses = $browser->script(
                'return [...new Set(window.warisAlamMarkers.getLayers().map((m) => m.options.status))];'
            )[0];

            $this->assertSame(['threatened'], $statuses);
        });
    }

    public function test_filters_and_search_are_combined_with_and_logic(): void
    {
        $this->browseDemoMap(function (Browser $browser) {
            $this->openMap($browser);

            $browser->type('@search', 'taman nasional')
                ->waitForTextIn('@count', 'Menampilkan 9 dari 18 situs')
                ->select('@status', 'threatened')
                ->waitForTextIn('@count', 'Menampilkan 3 dari 18 situs')
                ->select('@province', 'Jambi')
                ->waitForTextIn('@count', 'Menampilkan 1 dari 18 situs')
                ->assertMarkerCount(1)
                ->assertSiteListCount(1)
                ->assertSeeIn('@siteList', 'Taman Nasional Kerinci Seblat');
        });
    }

    public function test_type_and_status_combination(): void
    {
        $this->browseDemoMap(function (Browser $browser) {
            $this->openMap($browser);

            $browser->select('@type', 'cultural')
                ->select('@status', 'threatened')
                ->waitForTextIn('@count', 'Menampilkan 1 dari 18 situs')
                ->assertMarkerCount(1)
                ->assertSeeIn('@siteList', 'Kawasan Percandian Muaro Jambi');
        });
    }

    public function test_no_matching_sites_shows_no_sites_found(): void
    {
        $this->browseDemoMap(function (Browser $browser) {
            $this->openMap($browser);

            $browser->type('@search', 'situs yang tidak ada')
                ->waitFor('@noResults')
                ->assertSeeIn('@noResults', 'No sites found.')
                ->assertMarkerCount(0)
                ->assertMissing('.site-marker')
                ->assertMissing('.site-cluster')
                ->assertSiteListCount(0)
                ->assertVisible('@siteListEmpty');
        });
    }

    public function test_combination_without_results_shows_no_sites_found(): void
    {
        $this->browseDemoMap(function (Browser $browser) {
            $this->openMap($browser);

            // Borobudur is a cultural site, so "Borobudur" AND natural matches nothing.
            $browser->type('@search', 'Borobudur')
                ->select('@type', 'natural')
                ->waitFor('@noResults')
                ->assertMarkerCount(0);
        });
    }

    public function test_changing_a_filter_updates_the_markers(): void
    {
        $this->browseDemoMap(function (Browser $browser) {
            $this->openMap($browser);

            $browser->select('@status', 'watch')
                ->waitForTextIn('@count', 'Menampilkan 6 dari 18 situs')
                ->assertMarkerCount(6)
                ->select('@status', 'safe')
                ->waitForTextIn('@count', 'Menampilkan 8 dari 18 situs')
                ->assertMarkerCount(8);
        });
    }

    public function test_reset_clears_filters_and_shows_all_sites_again(): void
    {
        $this->browseDemoMap(function (Browser $browser) {
            $this->openMap($browser);

            $browser->type('@search', 'tidak ada')
                ->waitFor('@noResults')
                ->click('@reset')
                ->waitForTextIn('@count', 'Menampilkan semua 18 situs')
                ->assertMarkerCount(MapPage::TOTAL_SITES)
                ->assertSiteListCount(MapPage::TOTAL_SITES)
                ->assertMissing('@noResults')
                ->assertInputValue('@search', '')
                ->assertSelected('@type', '')
                ->assertSelected('@province', '')
                ->assertSelected('@status', '');
        });
    }

    public function test_clusters_are_rebuilt_from_filtered_markers(): void
    {
        $this->browseDemoMap(function (Browser $browser) {
            $this->openMap($browser);

            $browser->select('@status', 'threatened')
                ->waitForTextIn('@count', 'Menampilkan 4 dari 18 situs');

            // Clusters and single markers on screen only hold the 4 threatened sites.
            [$inClusters, $singles] = $browser->script('
                return [
                    [...document.querySelectorAll(".site-cluster")]
                        .reduce((sum, el) => sum + Number(el.textContent.trim()), 0),
                    document.querySelectorAll(".site-marker").length,
                ];
            ')[0];

            $this->assertSame(4, $inClusters + $singles);
        });
    }

    private function openMap(Browser $browser): void
    {
        $browser->visit(new MapPage)
            ->waitForMarkers()
            ->waitForTextIn('@count', 'Menampilkan semua 18 situs');
    }
}
