<?php

namespace Tests\Browser;

use App\Models\DamageReport;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\Browser\Concerns\InteractsWithReportForm;
use Tests\DuskTestCase;

/**
 * PBI 18: As a reporter, I want to submit a report anonymously, so that my identity is protected.
 *
 * The public page is GET /laporan/{id} (JSON via DamageReportResource).
 */
class AnonymousSubmissionTest extends DuskTestCase
{
    use DatabaseMigrations, InteractsWithReportForm;

    public function test_anonymous_submission_hides_the_reporter_from_the_public(): void
    {
        $this->site();
        $user = $this->reporter('Budi Santoso');

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)->visit('/laporan/baru')
                ->assertSee('Kirim sebagai Laporan Anonim');

            $this->chooseSite($browser);
            $this->attachPhoto($browser);
            $this->completeDetails($browser);
            $browser->check('is_anonymous');
            $this->submit($browser)->waitForText('Laporan terkirim');

            $report = DamageReport::firstOrFail();
            $this->assertTrue($report->is_anonymous);

            // A guest opens the public page.
            $browser->logout()->visit("/laporan/{$report->id}")
                ->assertSee('Pelapor Anonim')
                ->assertDontSee('Budi Santoso')
                ->assertDontSee('user_id');
        });
    }

    public function test_the_owner_still_sees_their_own_name(): void
    {
        $owner = $this->reporter('Budi Santoso');
        $report = $this->createReport($owner, anonymous: true);

        $this->browse(function (Browser $browser) use ($owner, $report) {
            $browser->loginAs($owner)->visit("/laporan/{$report->id}")
                ->assertSee('Budi Santoso');
        });
    }

    public function test_another_user_sees_only_anonymous(): void
    {
        $report = $this->createReport($this->reporter('Budi Santoso'), anonymous: true);
        $other = $this->reporter('Siti Rahma');

        $this->browse(function (Browser $browser) use ($other, $report) {
            $browser->loginAs($other)->visit("/laporan/{$report->id}")
                ->assertSee('Pelapor Anonim')
                ->assertDontSee('Budi Santoso');
        });
    }

    public function test_a_normal_report_shows_the_reporter_name(): void
    {
        $report = $this->createReport($this->reporter('Budi Santoso'), anonymous: false);

        $this->browse(function (Browser $browser) use ($report) {
            $browser->visit("/laporan/{$report->id}")
                ->assertSee('Budi Santoso')
                ->assertDontSee('Pelapor Anonim');
        });
    }
}
