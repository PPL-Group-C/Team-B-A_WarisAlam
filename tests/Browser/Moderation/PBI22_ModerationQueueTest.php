<?php

namespace Tests\Browser\Moderation;

use App\Enums\DamageCategory;
use App\Enums\ReportStatus;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\ModerationPage;

/**
 * PBI #22 (SCRUM-15): antrean laporan pending untuk moderator.
 * Test case: TC.Moderasi.001 – TC.Moderasi.007.
 */
class PBI22_ModerationQueueTest extends ModerationBrowserTestCase
{
    /** TC.Moderasi.001 */
    public function test_moderator_sees_pending_queue(): void
    {
        $site = $this->site('Candi Borobudur');
        $oldest = $this->pending(['description' => 'Coretan cat semprot di relief sisi utara.'], 180, $site);
        $middle = $this->pending([], 120);
        $newest = $this->pending([], 30);
        $verified = $this->report(['status' => ReportStatus::Verified->value], 240);
        $this->photo($oldest);

        $this->browse(function (Browser $browser) use ($oldest, $middle, $newest, $verified) {
            $browser->loginAs($this->moderator)
                ->visit(new ModerationPage)
                ->assertPresent(ModerationPage::stat('menunggu-review'))
                ->assertPresent(ModerationPage::stat('terverifikasi-hari-ini'))
                ->assertPresent(ModerationPage::stat('terdeteksi-duplikat'))
                ->assertPresent(ModerationPage::stat('rerata-sla-respon'))
                ->assertSeeIn('@queue-total', '3 Baru')
                ->assertMissing(ModerationPage::card($verified->id));

            $this->assertSame([$oldest->id, $middle->id, $newest->id], ModerationPage::queueIds($browser));

            $browser->assertPresent('[data-selected-report="'.$oldest->id.'"]')
                ->assertSeeIn('@detail', 'Candi Borobudur')
                ->assertSeeIn('@detail', 'Bukti Foto Lapangan (1 Berkas)')
                ->assertSeeIn('@detail', 'Verifikasi EXIF & Geospasial')
                ->assertSeeIn('@credibility', 'Kredibilitas Sains Warga');
        });
    }

    /** TC.Moderasi.002 */
    public function test_search_and_filter_queue(): void
    {
        $prambanan = $this->site('Candi Prambanan');
        $borobudur = $this->site('Candi Borobudur');
        $vandal = $this->pending(['category' => DamageCategory::Vandalism, 'severity' => 3], 180, $prambanan);
        $waste = $this->pending(['category' => DamageCategory::Waste, 'severity' => 2], 120, $prambanan);
        $other = $this->pending(['category' => DamageCategory::Vandalism, 'severity' => 3], 60, $borobudur);

        $this->browse(function (Browser $browser) use ($vandal, $waste, $other) {
            $browser->loginAs($this->moderator)
                ->visit(new ModerationPage)
                ->assertSeeIn('@queue-total', '3');

            $browser->type('@search', 'Prambanan')
                ->waitForReload(fn (Browser $b) => $b->keys('@search', '{enter}'))
                ->assertPresent(ModerationPage::card($vandal->id))
                ->assertPresent(ModerationPage::card($waste->id))
                ->assertMissing(ModerationPage::card($other->id));

            $browser->filter(['category' => DamageCategory::Vandalism->value, 'severity' => '3']);
            $this->assertSame([$vandal->id], ModerationPage::queueIds($browser));

            $browser->waitForReload(fn (Browser $b) => $b->clickLink('Reset'))
                ->assertSeeIn('@queue-total', '3 Baru');
            $this->assertSame([$vandal->id, $waste->id, $other->id], ModerationPage::queueIds($browser));
        });
    }

    /** TC.Moderasi.003 */
    public function test_warga_cannot_open_moderation(): void
    {
        $this->pending();
        $warga = User::factory()->create(['name' => 'Budi Warga', 'email' => 'budi@warga.test']);

        $this->browse(function (Browser $browser) use ($warga) {
            $browser->loginAs($warga)
                ->visit('/moderasi')
                ->assertSee('403')
                ->assertDontSee('Antrean Laporan Lapangan')
                ->assertMissing('[data-report-card]');
        });
    }

    /** TC.Moderasi.004 */
    public function test_guest_cannot_open_moderation(): void
    {
        $this->pending();

        $this->browse(function (Browser $browser) {
            $browser->logout()
                ->visit('/moderasi')
                ->assertSee('403')
                ->assertDontSee('Antrean Laporan Lapangan')
                ->assertMissing('[data-report-card]');
        });
    }

    /** TC.Moderasi.005 */
    public function test_sort_queue_by_severity(): void
    {
        $l2 = $this->pending(['severity' => 2], 240);
        $l5a = $this->pending(['severity' => 5], 180);
        $l3 = $this->pending(['severity' => 3], 120);
        $l5b = $this->pending(['severity' => 5], 60);

        $this->browse(function (Browser $browser) use ($l2, $l5a, $l3, $l5b) {
            $browser->loginAs($this->moderator)
                ->visit(new ModerationPage)
                ->assertSee('Urutkan: Terlama dulu (FIFO)');
            $this->assertSame([$l2->id, $l5a->id, $l3->id, $l5b->id], ModerationPage::queueIds($browser));

            $browser->filter(['sort' => 'severity']);
            $browser->assertSee('Urutkan: Severity tertinggi');
            $this->assertSame([$l5a->id, $l5b->id, $l3->id, $l2->id], ModerationPage::queueIds($browser));
        });
    }

    /** TC.Moderasi.006 */
    public function test_anonymous_reporter_visible_to_moderator(): void
    {
        $this->pending([], 120);
        $reporter = User::factory()->create(['name' => 'Siti Rahma']);
        $anonymous = $this->pending(['user_id' => $reporter->id, 'is_anonymous' => true], 60);

        $this->browse(function (Browser $browser) use ($anonymous) {
            $card = ModerationPage::card($anonymous->id);

            $browser->loginAs($this->moderator)
                ->visit(new ModerationPage)
                ->assertSeeIn($card, 'Siti Rahma')
                ->assertSeeIn($card, 'Anonim');

            $browser->openReport($anonymous->id);
            $browser->assertSeeIn('@credibility', 'Siti Rahma')
                ->assertSeeIn('@credibility', 'Anonim');
        });
    }

    /** TC.Moderasi.007 */
    public function test_photo_lightbox_opens_and_closes(): void
    {
        $report = $this->pending();
        $this->photo($report, 'IMG_20261117_091500.JPG');
        $this->photo($report, 'IMG_20261117_091530.JPG');

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->moderator)
                ->visit(new ModerationPage)
                ->assertSeeIn('@detail', 'Bukti Foto Lapangan (2 Berkas)')
                ->assertMissing('@lightbox')
                ->click('button img[alt="Foto #1"]')
                ->waitFor('@lightbox')
                ->assertVisible('@lightbox img')
                ->assertAttributeContains('@lightbox img', 'src', '/storage/'.self::PHOTO_DIR.'/')
                ->screenshot('evidence/'.class_basename(static::class).'-'.$this->name().'-terbuka')
                ->press('Tutup')
                ->waitUntilMissing('@lightbox')
                ->assertVisible('@detail');
        });
    }
}
