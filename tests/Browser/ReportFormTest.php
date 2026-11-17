<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\Browser\Concerns\InteractsWithReportForm;
use Tests\DuskTestCase;

/**
 * PBI 16: As a reporter, I want to fill in a report form (photo, category, severity),
 * so that site damage can be reported.
 */
class ReportFormTest extends DuskTestCase
{
    use DatabaseMigrations, InteractsWithReportForm;

    public function test_reporter_submits_report_with_photo_category_and_severity(): void
    {
        $this->site();
        $user = $this->reporter();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)->visit('/laporan/baru');

            $this->chooseSite($browser);
            $this->attachPhoto($browser);
            $this->completeDetails($browser);

            $browser->keys('input[name="severity"]', '{arrow_right}')   // 3 -> 4
                ->waitForText('Skala 4: Tinggi');

            $this->submit($browser)
                ->waitForText('Laporan terkirim dan menunggu verifikasi BPK.');
        });

        $this->assertDatabaseHas('damage_reports', [
            'user_id' => $user->id, 'status' => 'submitted',
            'category' => 'vandalism', 'severity' => 4,
        ]);
        $this->assertDatabaseCount('report_photos', 1);
    }

    public function test_incomplete_form_shows_errors_and_saves_nothing(): void
    {
        $this->site();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->reporter())->visit('/laporan/baru');

            // Severity defaults to 3, so it can't be missing; the rest can.
            $this->submit($browser)
                ->waitForText('Laporan belum bisa disimpan:')
                ->assertSee('Pilih situs cagar budaya terlebih dahulu.')
                ->assertSee('Unggah minimal 1 foto bukti kerusakan.')
                ->assertSee('Pilih kategori ancaman.')
                ->assertSee('Centang pernyataan kebenaran data laporan.');
        });

        $this->assertDatabaseCount('damage_reports', 0);
    }

    public function test_severity_slider_updates_the_level_description(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->reporter())->visit('/laporan/baru')
                ->assertSee('Skala 3: Sedang')
                ->keys('input[name="severity"]', '{arrow_right}', '{arrow_right}')
                ->waitForText('Skala 5: Kritis/Darurat')
                ->assertSee('LEVEL SEVERITAS 5');
        });
    }

    public function test_draft_only_needs_a_site(): void
    {
        $this->site();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->reporter())->visit('/laporan/baru');

            $this->chooseSite($browser);

            $browser->click('button[name="intent"][value="draft"]')
                ->waitForText('Draf laporan tersimpan.');
        });

        $this->assertDatabaseHas('damage_reports', ['status' => 'draft']);
    }
}
