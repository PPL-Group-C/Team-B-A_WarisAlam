<?php

namespace Tests\Browser\Moderation;

use Laravel\Dusk\Browser;
use Tests\Browser\Pages\ModerationPage;

/**
 * PBI #30 (SCRUM-18): jumlah konfirmasi & bantahan warga pada laporan.
 * Test case: TC.Moderasi.021 – TC.Moderasi.023.
 */
class PBI30_ConfirmationCountTest extends ModerationBrowserTestCase
{
    /** TC.Moderasi.021 */
    public function test_confirmation_counts_are_displayed(): void
    {
        $a = $this->pending([], 120);
        $b = $this->pending([], 60);
        $this->votes($a, confirms: 4);
        $this->votes($b, confirms: 1, disputes: 2);

        $this->browse(function (Browser $browser) use ($a, $b) {
            $badgeB = ModerationPage::card($b->id).' [data-testid="confirmation-badge"]';

            $browser->loginAs($this->moderator)
                ->visit(new ModerationPage)
                ->assertSeeIn(ModerationPage::card($a->id).' [data-testid="confirmation-badge"]', '4 konfirmasi')
                ->assertSeeIn($badgeB, '1 konfirmasi')
                ->assertSeeIn($badgeB.' span', '2 bantahan')
                ->assertAttributeContains($badgeB.' span', 'class', 'text-red-700');

            $browser->assertPresent('[data-selected-report="'.$a->id.'"]')
                ->assertSeeIn('[data-testid="confirm-count"]', '4')
                ->assertSeeIn('[data-testid="dispute-count"]', '0')
                ->assertMissing('[data-testid="disputed-warning"]');

            $browser->openReport($b->id);
            $browser->assertSeeIn('[data-testid="confirm-count"]', '1')
                ->assertSeeIn('[data-testid="dispute-count"]', '2')
                ->assertSeeIn('[data-testid="disputed-warning"]', 'Bantahan lebih banyak dari konfirmasi');
        });
    }

    /** TC.Moderasi.022 */
    public function test_sort_by_most_confirmations(): void
    {
        $none = $this->pending([], 180);
        $one = $this->pending([], 120);
        $three = $this->pending([], 60);
        $this->votes($one, confirms: 1);
        $this->votes($three, confirms: 3);

        $this->browse(function (Browser $browser) use ($none, $one, $three) {
            $browser->loginAs($this->moderator)->visit(new ModerationPage);
            $this->assertSame([$none->id, $one->id, $three->id], ModerationPage::queueIds($browser));

            $browser->filter(['sort' => 'confirmations']);
            $browser->assertSee('Urutkan: Konfirmasi warga terbanyak');
            $this->assertSame([$three->id, $one->id, $none->id], ModerationPage::queueIds($browser));
        });
    }

    /** TC.Moderasi.023 */
    public function test_report_without_votes(): void
    {
        $voted = $this->pending([], 120);
        $silent = $this->pending([], 60);
        $this->votes($voted, confirms: 2);

        $this->browse(function (Browser $browser) use ($silent) {
            $browser->loginAs($this->moderator)
                ->visit(new ModerationPage)
                ->assertMissing(ModerationPage::card($silent->id).' [data-testid="confirmation-badge"]');

            $browser->openReport($silent->id);
            $browser->assertSeeIn('[data-testid="confirm-count"]', '0')
                ->assertSeeIn('[data-testid="dispute-count"]', '0')
                ->assertMissing('[data-testid="disputed-warning"]');
        });
    }
}
