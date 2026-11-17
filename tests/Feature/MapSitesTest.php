<?php

namespace Tests\Feature;

use App\Models\HeritageSite;
use Database\Factories\HeritageSiteFactory;
use Database\Seeders\MapDemoSiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapSitesTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_page_is_public(): void
    {
        $this->get('/map')
            ->assertOk()
            ->assertSee('id="site-map"', false);
    }

    public function test_sites_endpoint_returns_map_fields(): void
    {
        $this->seed(MapDemoSiteSeeder::class);

        $this->getJson('/map/sites')
            ->assertOk()
            ->assertJsonCount(18, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'type', 'category', 'province', 'city', 'lat', 'lng', 'status', 'threat_score', 'detail_url'],
                ],
            ]);
    }

    public function test_sites_come_from_the_heritage_sites_table(): void
    {
        $site = HeritageSiteFactory::new()->create([
            'name' => 'Candi Uji',
            'type' => 'natural',
            'category' => 'Taman Nasional',
            'province' => 'Bali',
            'city' => 'Tabanan',
            'latitude' => -8.37,
            'longitude' => 115.131,
            'status' => 'threatened',
            'threat_score' => 17.25,
        ]);

        $this->getJson('/map/sites')
            ->assertOk()
            ->assertExactJson(['data' => [[
                'id' => $site->id,
                'name' => 'Candi Uji',
                'type' => 'natural',
                'category' => 'Taman Nasional',
                'province' => 'Bali',
                'city' => 'Tabanan',
                'lat' => -8.37,
                'lng' => 115.131,
                'status' => 'threatened',
                'threat_score' => 17.25,
                'detail_url' => route('sites.show', $site),
            ]]]);
    }

    public function test_new_site_defaults_to_safe(): void
    {
        HeritageSiteFactory::new()->create();

        $this->getJson('/map/sites')
            ->assertOk()
            ->assertJsonPath('data.0.status', 'safe')
            ->assertJsonPath('data.0.threat_score', 0);
    }

    public function test_no_sites_returns_an_empty_list(): void
    {
        $this->getJson('/map/sites')
            ->assertOk()
            ->assertExactJson(['data' => []]);
    }

    public function test_detail_url_points_to_the_site_detail_page(): void
    {
        $this->seed(MapDemoSiteSeeder::class);

        foreach ($this->getJson('/map/sites')->json('data') as $site) {
            $this->assertSame(route('sites.show', $site['id']), $site['detail_url']);
        }
    }

    public function test_every_site_has_a_valid_status(): void
    {
        $this->seed(MapDemoSiteSeeder::class);

        $statuses = collect($this->getJson('/map/sites')->json('data'))->pluck('status');

        $this->assertNotEmpty($statuses);
        $this->assertEmpty($statuses->diff(['safe', 'watch', 'threatened']));
        $this->assertEqualsCanonicalizing(['safe', 'watch', 'threatened'], $statuses->unique()->values()->all());
    }

    public function test_status_matches_threat_score_thresholds(): void
    {
        $this->seed(MapDemoSiteSeeder::class);

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
        $this->seed(MapDemoSiteSeeder::class);

        foreach ($this->getJson('/map/sites')->json('data') as $site) {
            $this->assertArrayNotHasKey('reporter', $site);
            $this->assertArrayNotHasKey('user_id', $site);
            $this->assertArrayNotHasKey('registration_id', $site);
        }
    }

    public function test_demo_seeder_can_run_twice_without_duplicates(): void
    {
        $this->seed(MapDemoSiteSeeder::class);
        $this->seed(MapDemoSiteSeeder::class);

        $this->assertSame(18, HeritageSite::count());
    }
}
