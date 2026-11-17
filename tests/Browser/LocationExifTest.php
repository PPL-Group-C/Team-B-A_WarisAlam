<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\Browser\Concerns\InteractsWithReportForm;
use Tests\DuskTestCase;

/**
 * PBI 17: As a reporter, I want the location to be filled automatically from photo
 * EXIF/GPS data and the pin to be draggable, so that the report location is accurate.
 *
 * Fixtures: geotagged.jpg is ~70 m from the seeded site, far-geotagged.jpg is ~33 km away,
 * no-gps.jpg has no GPS tags.
 */
class LocationExifTest extends DuskTestCase
{
    use DatabaseMigrations, InteractsWithReportForm;

    private const SITE_COORD = '7.7520° S, 110.4914° E';

    public function test_exif_gps_moves_the_pin_and_marks_the_photo_valid(): void
    {
        $this->site();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->reporter())->visit('/laporan/baru');
            $this->chooseSite($browser)->assertSee(self::SITE_COORD);

            $this->attachPhoto($browser, 'geotagged.jpg');

            $browser->waitForText('Lokasi dari EXIF foto')
                ->assertSee('7.7524° S, 110.4919° E')
                ->assertDontSee(self::SITE_COORD)
                ->assertSee('EXIF Valid');
        });
    }

    public function test_photo_without_gps_leaves_the_pin_alone(): void
    {
        $this->site();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->reporter())->visit('/laporan/baru');
            $this->chooseSite($browser);

            $this->attachPhoto($browser, 'no-gps.jpg');

            $browser->waitForText('Tanpa GPS')
                ->assertSee('Pin di titik situs')
                ->assertSee(self::SITE_COORD);
        });
    }

    public function test_pin_can_be_dragged_and_becomes_manual(): void
    {
        $this->site();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->reporter())->visit('/laporan/baru');
            $this->chooseSite($browser);

            $browser->waitFor('.leaflet-marker-icon')
                ->dragOffset('.leaflet-marker-icon', 80, 60)
                ->waitForText('Pin diatur manual')
                ->assertDontSee(self::SITE_COORD);
        });
    }

    public function test_exif_does_not_override_a_pin_the_reporter_placed(): void
    {
        $this->site();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->reporter())->visit('/laporan/baru');
            $this->chooseSite($browser);

            $browser->waitFor('.leaflet-marker-icon')
                ->dragOffset('.leaflet-marker-icon', 80, 60)
                ->waitForText('Pin diatur manual');

            $this->attachPhoto($browser, 'geotagged.jpg');

            $browser->assertSee('Pin diatur manual')
                ->assertDontSee('Lokasi dari EXIF foto')
                ->assertDontSee('7.7524° S, 110.4919° E');
        });
    }

    public function test_far_photo_offers_to_use_the_photo_location(): void
    {
        $this->site();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->reporter())->visit('/laporan/baru');
            $this->chooseSite($browser);

            $browser->waitFor('.leaflet-marker-icon')
                ->dragOffset('.leaflet-marker-icon', 80, 60)
                ->waitForText('Pin diatur manual');

            $this->attachPhoto($browser, 'far-geotagged.jpg');

            $browser->waitForText('Jauh dari titik')
                ->assertSee('Pakai lokasi foto ini')
                ->click('figcaption button')
                ->waitForText('Lokasi dari EXIF foto')
                ->assertSee('7.6079° S, 110.2038° E');
        });
    }

    public function test_location_source_is_saved_with_the_report(): void
    {
        $this->site();
        $user = $this->reporter();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)->visit('/laporan/baru');
            $this->chooseSite($browser);
            $this->attachPhoto($browser, 'geotagged.jpg');
            $browser->waitForText('Lokasi dari EXIF foto');
            $this->completeDetails($browser);
            $this->submit($browser)->waitForText('Laporan terkirim');
        });

        $this->assertDatabaseHas('damage_reports', ['user_id' => $user->id, 'location_source' => 'exif']);
        $this->assertDatabaseHas('report_photos', ['exif_valid' => true]);   // server re-checked the EXIF
    }

    public function test_dragged_pin_is_saved_as_manual(): void
    {
        $this->site();
        $user = $this->reporter();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)->visit('/laporan/baru');
            $this->chooseSite($browser);

            $browser->waitFor('.leaflet-marker-icon')
                ->dragOffset('.leaflet-marker-icon', 80, 60)
                ->waitForText('Pin diatur manual');

            $this->attachPhoto($browser, 'geotagged.jpg');
            $this->completeDetails($browser);
            $this->submit($browser)->waitForText('Laporan terkirim');
        });

        $this->assertDatabaseHas('damage_reports', ['user_id' => $user->id, 'location_source' => 'manual']);
    }
}
