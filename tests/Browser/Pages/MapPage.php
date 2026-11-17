<?php

namespace Tests\Browser\Pages;

use Facebook\WebDriver\Chrome\ChromeDevToolsDriver;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Assert;

/**
 * Public site map (UC-10 / UC-11).
 *
 * The demo data has 18 sites: 8 safe, 6 watch, 4 threatened,
 * 8 cultural and 10 natural.
 */
class MapPage extends Page
{
    public const TOTAL_SITES = 18;

    public function url(): string
    {
        return '/map';
    }

    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url())
            ->assertPresent('#site-map');
    }

    /**
     * @return array<string, string>
     */
    public function elements(): array
    {
        return [
            '@map' => '#site-map',
            '@loading' => '#map-loading',
            '@empty' => '#map-empty',
            '@error' => '#map-error',
            '@noResults' => '#map-no-results',
            '@search' => '#filter-search',
            '@type' => '#filter-type',
            '@province' => '#filter-province',
            '@status' => '#filter-status',
            '@reset' => '#filter-clear',
            '@count' => '#filter-count',
            '@siteList' => '#site-list',
            '@siteListEmpty' => '#site-list-empty',
        ];
    }

    /**
     * Wait until the site data is loaded and the markers are on the map.
     */
    public function waitForMarkers(Browser $browser, int $seconds = 10): void
    {
        $browser->waitUntilMissing('@loading', $seconds)
            ->waitUntil('window.warisAlamMarkers !== undefined', $seconds);
    }

    /**
     * Number of markers currently in the cluster layer (after filtering).
     */
    public function markerCount(Browser $browser): int
    {
        return (int) $browser->script('return window.warisAlamMarkers.getLayers().length;')[0];
    }

    public function assertMarkerCount(Browser $browser, int $expected): void
    {
        Assert::assertSame($expected, $this->markerCount($browser), 'Unexpected number of markers on the map.');
    }

    /**
     * Number of cards in the "Katalog Situs Terpantau" sidebar.
     */
    public function assertSiteListCount(Browser $browser, int $expected): void
    {
        $browser->assertCount('#site-list > li', $expected);
    }

    /**
     * Zoom straight to one site so its marker is shown on its own, not in a cluster.
     */
    public function zoomToSite(Browser $browser, string $name, int $zoom = 14): void
    {
        // The browser is reused between tests, so the mouse may still rest where the
        // previous marker was. Move it off the map so no hover popup opens by itself.
        $browser->mouseover('header');

        $browser->script(sprintf(
            'const site = window.warisAlamSites.find((s) => s.name === %s);
             window.warisAlamMap.setView([site.lat, site.lng], %d, { animate: false });',
            json_encode($name),
            $zoom,
        ));

        $browser->waitFor(self::markerSelector($name));
    }

    public static function markerSelector(string $name): string
    {
        return sprintf('.site-marker[title="%s"]', $name);
    }

    /**
     * Replace the response of /map/sites before the page loads, so empty and
     * failing data can be tested. Returns an id for removeSitesResponse().
     *
     * @param  array<mixed>|null  $body  null sends an empty body
     */
    public static function fakeSitesResponse(Browser $browser, ?array $body, int $status = 200): string
    {
        $source = sprintf(
            '(() => {
                const realFetch = window.fetch.bind(window);
                window.fetch = (input, init) => {
                    const url = typeof input === "string" ? input : input.url;
                    if (url.includes("/map/sites")) {
                        return Promise.resolve(new Response(%s, {
                            status: %d,
                            headers: { "Content-Type": "application/json" },
                        }));
                    }
                    return realFetch(input, init);
                };
            })();',
            json_encode($body === null ? '' : json_encode($body)),
            $status,
        );

        $result = (new ChromeDevToolsDriver($browser->driver))
            ->execute('Page.addScriptToEvaluateOnNewDocument', ['source' => $source]);

        return $result['identifier'];
    }

    public static function removeSitesResponse(Browser $browser, string $identifier): void
    {
        (new ChromeDevToolsDriver($browser->driver))
            ->execute('Page.removeScriptToEvaluateOnNewDocument', ['identifier' => $identifier]);
    }
}
