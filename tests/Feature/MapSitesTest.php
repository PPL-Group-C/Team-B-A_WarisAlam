<?php

namespace Tests\Feature;

use Tests\TestCase;

class MapSitesTest extends TestCase
{
    public function test_map_page_is_public(): void
    {
        $this->get('/map')
            ->assertOk()
            ->assertSee('id="site-map"', false);
    }

    public function test_sites_endpoint_returns_map_fields(): void
    {
        $this->getJson('/map/sites')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'type', 'category', 'province', 'city', 'lat', 'lng', 'status', 'threat_score', 'detail_url'],
                ],
            ]);
    }

    public function test_every_site_has_a_valid_status(): void
    {
        $statuses = collect($this->getJson('/map/sites')->json('data'))->pluck('status');

        $this->assertNotEmpty($statuses);
        $this->assertEmpty($statuses->diff(['safe', 'watch', 'threatened']));
    }

    public function test_status_matches_threat_score_thresholds(): void
    {
        foreach ($this->getJson('/map/sites')->json('data') as $site) {
            $expected = match (true) {
                $site['threat_score'] >= 15 => 'threatened',
                $site['threat_score'] >= 5 => 'watch',
                default => 'safe',
            };

            $this->assertSame($expected, $site['status'], $site['name']);
        }
    }

    public function test_reporter_data_is_not_exposed(): void
    {
        foreach ($this->getJson('/map/sites')->json('data') as $site) {
            $this->assertArrayNotHasKey('reporter', $site);
            $this->assertArrayNotHasKey('user_id', $site);
        }
    }
}
