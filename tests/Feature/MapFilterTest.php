<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * SCRUM-9 (PBI-12, FR-10): filtering runs in the browser, so these tests
 * check that the page exposes the filter controls and that the site data
 * carries valid values for every filter.
 */
class MapFilterTest extends TestCase
{
    public function test_map_page_has_all_filter_controls(): void
    {
        $this->get('/map')
            ->assertOk()
            ->assertSee('id="filter-search"', false)
            ->assertSee('id="filter-type"', false)
            ->assertSee('id="filter-province"', false)
            ->assertSee('id="filter-status"', false)
            ->assertSee('id="filter-clear"', false);
    }

    public function test_map_page_has_no_results_message_and_site_list(): void
    {
        $this->get('/map')
            ->assertOk()
            ->assertSee('id="map-no-results"', false)
            ->assertSee('No sites found')
            ->assertSee('id="site-list"', false);
    }

    public function test_status_filter_options_match_site_statuses(): void
    {
        $this->get('/map')
            ->assertSee('value="safe"', false)
            ->assertSee('value="watch"', false)
            ->assertSee('value="threatened"', false);
    }

    public function test_every_site_has_a_filterable_type_province_and_name(): void
    {
        foreach ($this->sites() as $site) {
            $this->assertContains($site['type'], ['cultural', 'natural'], $site['name']);
            $this->assertNotEmpty($site['province'], $site['name']);
            $this->assertNotEmpty(trim($site['name']));
        }
    }

    public function test_combined_filters_use_and_logic(): void
    {
        $matches = $this->sites()
            ->where('type', 'natural')
            ->where('status', 'threatened')
            ->where('province', 'Jambi');

        $this->assertCount(1, $matches);
        $this->assertSame('Taman Nasional Kerinci Seblat', $matches->first()['name']);
    }

    public function test_site_coordinates_are_inside_indonesia(): void
    {
        foreach ($this->sites() as $site) {
            $this->assertTrue($site['lat'] >= -11 && $site['lat'] <= 6, $site['name']);
            $this->assertTrue($site['lng'] >= 95 && $site['lng'] <= 141, $site['name']);
        }
    }

    private function sites()
    {
        return collect($this->getJson('/map/sites')->assertOk()->json('data'));
    }
}
