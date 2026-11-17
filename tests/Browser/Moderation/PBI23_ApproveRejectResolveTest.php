<?php

namespace Tests\Browser\Moderation;

use App\Enums\ReportStatus;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\ModerationPage;

/**
 * PBI #23 (SCRUM-16): setujui / tolak laporan dan konfirmasi penyelesaian.
 * Test case: TC.Moderasi.008 – TC.Moderasi.014.
 */
class PBI23_ApproveRejectResolveTest extends ModerationBrowserTestCase
{
    /** TC.Moderasi.008 */
    public function test_moderator_approves_report_with_adjusted_severity(): void
    {
        $report = $this->pending(['severity' => 3], 120);
        $next = $this->pending(['severity' => 2], 60);

        $this->browse(function (Browser $browser) use ($report, $next) {
            $browser->loginAs($this->moderator)
                ->visit(new ModerationPage)
                ->assertPresent('[data-selected-report="'.$report->id.'"]')
                ->assertSee('Penyesuaian Verifikasi Moderator')
                ->assertRadioSelected('severity', '3');

            $browser->chooseSeverity(4);
            $browser->assertRadioSelected('severity', '4')
                ->assertSee('Level 4: Kerusakan Struktural Luas')
                ->type('moderator_note', 'Retakan struktural terverifikasi dari dua foto.')
                ->click('@approve')
                ->waitFor('@approve-modal')
                ->assertSeeIn('@approve-modal', 'Setujui laporan '.ModerationPage::code($report->id).'?')
                ->assertSeeIn('@approve-modal', 'Level 4')
                ->waitForReload(fn (Browser $b) => $b->press('Ya, Setujui'))
                ->assertSeeIn('@flash', ModerationPage::code($report->id))
                ->assertMissing(ModerationPage::card($report->id))
                ->assertPresent('[data-selected-report="'.$next->id.'"]');
        });

        $report->refresh();
        $this->assertSame(ReportStatus::Verified->value, $report->status);
        $this->assertSame(4, (int) $report->severity);
    }

    /** TC.Moderasi.009 */
    public function test_reject_requires_reason_of_ten_characters(): void
    {
        $report = $this->pending();

        $this->browse(function (Browser $browser) use ($report) {
            $browser->loginAs($this->moderator)
                ->visit(new ModerationPage)
                ->click('@reject')
                ->waitFor('@reject-modal')
                ->type('rejection_reason', 'buram')
                ->assertSeeIn('@reject-modal', '5/10 karakter minimum')
                ->assertButtonDisabled('@reject-submit')
                ->type('rejection_reason', 'Foto buram, lokasi tidak terlihat jelas.')
                ->assertButtonEnabled('@reject-submit')
                ->waitForReload(fn (Browser $b) => $b->click('@reject-submit'))
                ->assertSeeIn('@flash', ModerationPage::code($report->id))
                ->assertMissing(ModerationPage::card($report->id));

            $browser->visit(new ModerationPage(['status' => 'all', 'report' => $report->id]))
                ->assertSeeIn('@detail', 'Status: Ditolak')
                ->assertSeeIn('[data-testid="rejection-reason"]', 'Alasan Penolakan')
                ->assertSeeIn('[data-testid="rejection-reason"]', 'Foto buram, lokasi tidak terlihat jelas.');
        });

        $this->assertSame(ReportStatus::Rejected->value, $report->fresh()->status);
    }

    /** TC.Moderasi.010 */
    public function test_moderator_confirms_resolution(): void
    {
        $this->pending();
        $report = $this->report(['status' => ReportStatus::InProgress->value]);

        $this->browse(function (Browser $browser) use ($report) {
            $browser->loginAs($this->moderator)->visit(new ModerationPage);
            $browser->filter(['status' => ReportStatus::InProgress->value]);
            $browser->openReport($report->id);

            $browser->assertSee('Konfirmasi Penyelesaian')
                ->type('resolution_note', 'Foto pasca-penanganan dari BPK menunjukkan coretan sudah dibersihkan.')
                ->click('@resolve')
                ->waitForText('Konfirmasi laporan '.ModerationPage::code($report->id).' selesai?')
                ->waitForReload(fn (Browser $b) => $b->press('Ya, Selesai'))
                ->assertSeeIn('@flash', ModerationPage::code($report->id));

            $browser->visit(new ModerationPage(['status' => 'all', 'report' => $report->id]))
                ->assertSeeIn('@detail', 'Status: Selesai');
        });

        $this->assertSame(ReportStatus::Resolved->value, $report->fresh()->status);
    }

    /** TC.Moderasi.011 */
    public function test_curation_log_lists_latest_decision(): void
    {
        $site = $this->site('Candi Mendut');
        $report = $this->pending([], 60, $site);

        $this->browse(function (Browser $browser) use ($report) {
            $browser->loginAs($this->moderator)
                ->visit(new ModerationPage)
                ->assertSeeIn('@log', 'Belum ada tindakan kurasi.')
                ->type('moderator_note', 'Foto valid, lokasi sesuai.')
                ->click('@approve')
                ->waitFor('@approve-modal')
                ->waitForReload(fn (Browser $b) => $b->press('Ya, Setujui'))
                ->assertVisible('@flash')
                ->scrollIntoView('@log')
                ->assertSeeIn('@log-first', ModerationPage::code($report->id))
                ->assertSeeIn('@log-first', 'Candi Mendut')
                ->assertSeeIn('@log-first', 'Terverifikasi')
                ->assertSeeIn('@log-first', 'Rina Moderator')
                ->assertSeeIn('@log-first', 'Foto valid, lokasi sesuai.');
        });
    }

    /** TC.Moderasi.012 */
    public function test_cancel_approve_keeps_status(): void
    {
        $report = $this->pending();

        $this->browse(function (Browser $browser) use ($report) {
            $browser->loginAs($this->moderator)
                ->visit(new ModerationPage)
                ->click('@approve')
                ->waitFor('@approve-modal')
                ->within('@approve-modal', fn (Browser $modal) => $modal->press('Batal'))
                ->waitUntilMissing('@approve-modal')
                ->assertSeeIn('@detail', 'Status: Menunggu Kurasi')
                ->assertPresent(ModerationPage::card($report->id));
        });

        $this->assertSame(ReportStatus::Pending->value, $report->fresh()->status);
    }

    /** TC.Moderasi.013 */
    public function test_actions_disabled_for_verified_report(): void
    {
        $this->pending();
        $report = $this->report(['status' => ReportStatus::Verified->value]);

        $this->browse(function (Browser $browser) use ($report) {
            $browser->loginAs($this->moderator)->visit(new ModerationPage);
            $browser->filter(['status' => ReportStatus::Verified->value]);
            $browser->openReport($report->id);

            $browser->assertSeeIn('@detail', 'Status: Terverifikasi')
                ->assertButtonDisabled('@approve')
                ->assertButtonDisabled('@reject')
                ->assertMissing('@duplicate-link')
                ->assertSee('Setujui/Tolak hanya tersedia untuk laporan berstatus "Menunggu Kurasi".');
        });
    }

    /** TC.Moderasi.014 */
    public function test_original_severity_is_kept(): void
    {
        $report = $this->pending(['severity' => 3]);

        $this->browse(function (Browser $browser) use ($report) {
            $browser->loginAs($this->moderator)
                ->visit(new ModerationPage)
                ->assertSeeIn('[data-testid="submitted-severity"]', 'Level 3');

            $browser->chooseSeverity(4);
            $browser->click('@approve')
                ->waitFor('@approve-modal')
                ->waitForReload(fn (Browser $b) => $b->press('Ya, Setujui'))
                ->assertVisible('@flash');

            $browser->visit(new ModerationPage);
            $browser->filter(['status' => 'all']);
            $browser->openReport($report->id);
            $browser->assertSeeIn('@detail', 'Level 4')
                ->assertSeeIn('[data-testid="submitted-severity"]', 'Level 3')
                ->assertSee('Sudah disesuaikan moderator menjadi Level 4.');
        });

        $report->refresh();
        $this->assertSame(4, (int) $report->severity);
        $this->assertSame(3, (int) $report->severity_original);
    }
}
