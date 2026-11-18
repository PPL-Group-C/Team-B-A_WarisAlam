<?php

namespace Tests\Browser;

use App\Models\ReportVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\Browser\Concerns\InteractsWithReportForm;
use Tests\DuskTestCase;

/**
 * PBI 28: As a user, I want to confirm or dispute other people's reports,
 * so that report accuracy can be verified.
 */
class VerifyReportsTest extends DuskTestCase
{
    use DatabaseMigrations, InteractsWithReportForm;

    public function test_a_user_can_confirm_someone_elses_report(): void
    {
        $report = $this->createReport($this->reporter('Budi Santoso'), anonymous: false);
        $verifier = $this->reporter('Siti Rahma');

        $this->browse(function (Browser $browser) use ($verifier, $report) {
            $browser->loginAs($verifier)->visit("/laporan/{$report->id}")
                ->assertSee('0 Dikonfirmasi')
                ->click('button[name="verdict"][value="confirm"]')
                ->waitForText('Terima kasih, konfirmasi Anda tercatat.')
                ->assertSee('1 Dikonfirmasi')
                ->assertSee('Pilihan Anda: Konfirmasi');
        });

        $this->assertDatabaseHas('report_verifications', [
            'damage_report_id' => $report->id, 'user_id' => $verifier->id, 'verdict' => 'confirm',
        ]);
    }

    public function test_disputing_requires_a_reason_and_then_shows_it(): void
    {
        $report = $this->createReport($this->reporter('Budi Santoso'), anonymous: false);

        $this->browse(function (Browser $browser) use ($report) {
            $browser->loginAs($this->reporter('Siti Rahma'))->visit("/laporan/{$report->id}")
                ->click('button[name="verdict"][value="dispute"]')
                ->waitForText('Tulis alasan sanggahan')
                ->type('comment', 'Foto ini bukan dari lokasi tersebut.')
                ->click('button[name="verdict"][value="dispute"]')
                ->waitForText('Sanggahan Anda tercatat.')
                ->assertSee('1 Disanggah')
                ->assertSee('Foto ini bukan dari lokasi tersebut.');
        });
    }

    public function test_a_user_can_change_and_withdraw_their_vote(): void
    {
        $report = $this->createReport($this->reporter('Budi Santoso'), anonymous: false);

        $this->browse(function (Browser $browser) use ($report) {
            $browser->loginAs($this->reporter('Siti Rahma'))->visit("/laporan/{$report->id}")
                ->click('button[name="verdict"][value="confirm"]')
                ->waitForText('1 Dikonfirmasi')

                ->type('comment', 'Setelah dicek ulang, kondisinya tidak sesuai.')
                ->click('button[name="verdict"][value="dispute"]')
                ->waitForText('1 Disanggah')
                ->assertSee('0 Dikonfirmasi')

                ->click('@withdraw-vote')
                ->waitForText('Suara Anda ditarik.')
                ->assertSee('0 Disanggah');
        });

        $this->assertDatabaseCount('report_verifications', 0);
    }

    public function test_the_owner_cannot_verify_their_own_report(): void
    {
        $owner = $this->reporter('Budi Santoso');
        $report = $this->createReport($owner, anonymous: false);

        $this->browse(function (Browser $browser) use ($owner, $report) {
            $browser->loginAs($owner)->visit("/laporan/{$report->id}")
                ->assertSee('Ini laporan Anda')
                ->assertMissing('button[name="verdict"]');
        });
    }

    public function test_guests_see_the_counts_but_cannot_vote(): void
    {
        $report = $this->createReport($this->reporter('Budi Santoso'), anonymous: false);

        $this->browse(function (Browser $browser) use ($report) {
            $browser->visit("/laporan/{$report->id}")
                ->assertSee('Masuk untuk memverifikasi laporan ini.')
                ->assertMissing('button[name="verdict"]');
        });
    }

    public function test_enough_confirmations_show_the_verified_badge(): void
    {
        $report = $this->createReport($this->reporter('Budi Santoso'), anonymous: false);

        User::factory()->count(3)->create()->each(
            fn (User $u) => ReportVerification::create([
                'damage_report_id' => $report->id, 'user_id' => $u->id, 'verdict' => 'confirm',
            ])
        );

        $this->browse(function (Browser $browser) use ($report) {
            $browser->visit("/laporan/{$report->id}")
                ->assertSee('3 Dikonfirmasi')
                ->assertSee('Terverifikasi warga');
        });
    }

    public function test_the_feed_links_to_a_report_and_masks_anonymous_reporters(): void
    {
        $report = $this->createReport($this->reporter('Budi Santoso'), anonymous: true);
        $verifier = $this->reporter('Siti Rahma');

        $this->browse(function (Browser $browser) use ($verifier, $report) {
            $browser->loginAs($verifier)->visit('/laporan')
                ->assertSee('Laporan Warga')
                ->assertSee('Candi Prambanan')
                ->assertSee('Pelapor Anonim')
                ->assertDontSee('Budi Santoso')
                ->click('a[href$="/laporan/'.$report->id.'"]')
                ->assertPathIs("/laporan/{$report->id}")
                ->assertSee('Verifikasi Warga');
        });
    }
}
