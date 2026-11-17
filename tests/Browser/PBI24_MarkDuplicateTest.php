<?php

namespace Tests\Browser;

use App\Enums\ReportStatus;
use Laravel\Dusk\Browser;
use Tests\Browser\Concerns\ModerationTestData;
use Tests\Browser\Pages\ModerationPage;
use Tests\DuskTestCase;

/**
 * PBI #24 (SCRUM-17): menandai laporan duplikat.
 * Test case di sheet: TC.Moderasi.015 – TC.Moderasi.020.
 *
 * Strategi pengujian (E2E, black-box):
 * - Partisi kandidat otomatis: ada (situs sama, jarak ≤ 50 m, selisih ≤ 7 hari) | tidak ada (situs berbeda).
 * - Kondisi batas waktu: laporan > 7 hari bukan kandidat otomatis, tetapi tetap bisa dipilih manual sebagai induk.
 * - Partisi status: laporan pending bisa ditandai duplikat | laporan yang sudah duplikat tidak bisa ditandai lagi.
 * - Relasi induk–anak: laporan induk menampilkan daftar duplikat yang ditautkan.
 * - Pencarian induk manual: berdasarkan ID laporan | kata di deskripsi.
 *
 * Jalankan: php artisan dusk --filter=PBI24_MarkDuplicateTest
 */
class PBI24_MarkDuplicateTest extends DuskTestCase
{
    use ModerationTestData;

    /** TC.Moderasi.015 */
    public function test_candidate_duplicate_is_detected(): void
    {
        $site = $this->site('Candi Plaosan');
        $first = $this->pending(['description' => 'Coretan cat merah di arca dwarapala.'], 120, $site);
        $second = $this->pending(['description' => 'Ada vandalisme cat merah di arca penjaga.'], 60, $site, 10);

        $this->browse(function (Browser $browser) use ($first, $second) {
            $browser->loginAs($this->moderator)
                ->visit(new ModerationPage)
                ->assertSeeIn(ModerationPage::card($first->id), 'Kandidat Duplikat')
                ->assertSeeIn(ModerationPage::card($second->id), 'Kandidat Duplikat')
                ->assertSeeIn(ModerationPage::card($second->id), 'Mirip '.ModerationPage::code($first->id))
                ->assertSeeIn(ModerationPage::card($first->id), 'Terdeteksi Duplikat');

            $browser->openReport($second->id);
            $browser->assertSeeIn('[data-testid="duplicate-compare"]', 'Laporan Ini: '.ModerationPage::code($second->id))
                ->assertSeeIn('[data-testid="duplicate-compare"]', 'Pembanding: '.ModerationPage::code($first->id))
                ->assertSeeIn('[data-testid="delta-time"]', 'Delta Waktu: Selisih')
                ->assertSeeIn('[data-testid="duplicate-recommendation"]', 'Jadikan '.ModerationPage::code($first->id).' sebagai Laporan Induk')
                ->assertSeeIn('[data-testid="candidate-count"]', '1 kandidat');
        });
    }

    /** TC.Moderasi.016 */
    public function test_moderator_marks_report_as_duplicate(): void
    {
        $site = $this->site('Candi Plaosan');
        $parent = $this->pending([], 120, $site);
        $child = $this->pending([], 60, $site, 10);

        $this->browse(function (Browser $browser) use ($parent, $child) {
            $browser->loginAs($this->moderator)
                ->visit(new ModerationPage)
                ->assertSeeIn(ModerationPage::stat('terdeteksi-duplikat'), '0');

            $browser->openReport($child->id);
            $browser->assertSeeIn('@duplicate-link', 'Tandai Duplikat '.ModerationPage::code($parent->id))
                ->assertMissing('@duplicate-form')
                ->click('@duplicate-link')
                ->waitFor('@duplicate-form')
                ->assertRadioSelected('duplicate_of_id', (string) $parent->id)
                ->click('@duplicate-submit')
                ->waitForText('Tandai '.ModerationPage::code($child->id).' sebagai duplikat?')
                ->waitForReload(fn (Browser $b) => $b->press('Ya, Tandai Duplikat'))
                ->assertSeeIn('@flash', 'ditandai sebagai duplikat dari '.ModerationPage::code($parent->id))
                ->assertMissing(ModerationPage::card($child->id))
                ->assertSeeIn(ModerationPage::stat('terdeteksi-duplikat'), '1')
                ->assertSeeIn('@log-first', ModerationPage::code($child->id))
                ->assertSeeIn('@log-first', 'Digabung Duplikat');

            $browser->visit(new ModerationPage(['status' => 'all', 'report' => $child->id]))
                ->assertSeeIn('@detail', 'Status: Ditolak')
                ->assertSeeIn('[data-testid="duplicate-of-badge"]', 'Duplikat dari '.ModerationPage::code($parent->id));
        });

        $child->refresh();
        $this->assertSame(ReportStatus::Rejected->value, $child->status);
        $this->assertSame($parent->id, (int) $child->duplicate_of_id);
    }

    /** TC.Moderasi.017 */
    public function test_parent_lists_linked_duplicates(): void
    {
        $site = $this->site('Candi Plaosan');
        $parent = $this->pending([], 120, $site);
        $child = $this->markedDuplicate($this->pending([], 60, $site, 10), $parent);

        $this->browse(function (Browser $browser) use ($parent, $child) {
            $browser->loginAs($this->moderator)
                ->visit(new ModerationPage(['status' => 'all', 'report' => $child->id]))
                ->click('[data-testid="duplicate-of-badge"]')
                ->waitFor('[data-selected-report="'.$parent->id.'"]')
                ->assertSeeIn('[data-testid="duplicate-children"]', 'Laporan Duplikat yang Ditautkan (1)')
                ->assertSeeIn('[data-testid="duplicate-children"]', ModerationPage::code($child->id));
        });
    }

    /** TC.Moderasi.018 */
    public function test_report_without_candidates(): void
    {
        $report = $this->pending([], 120, $this->site('Candi Sewu'));
        $this->pending([], 60, $this->site('Candi Kalasan'));

        $this->browse(function (Browser $browser) use ($report) {
            $browser->loginAs($this->moderator)
                ->visit(new ModerationPage)
                ->assertPresent('[data-selected-report="'.$report->id.'"]')
                ->assertVisible('@duplicate-box')
                ->assertSeeIn('@duplicate-box', 'Tidak ada kandidat duplikat otomatis')
                ->assertSeeIn('[data-testid="candidate-count"]', '0 kandidat')
                ->assertMissing('[data-testid="candidate-badge"]');
        });
    }

    /** TC.Moderasi.019 */
    public function test_manual_parent_search_filters_options(): void
    {
        $site = $this->site('Candi Borobudur');
        // Bukan kandidat otomatis (selisih waktu > 7 hari), tetapi bisa dipilih manual.
        $stupa = $this->report(['status' => ReportStatus::Verified->value, 'description' => 'Retakan pada stupa sisi timur.'], 60 * 24 * 30, $site);
        $sampah = $this->pending(['description' => 'Sampah plastik menumpuk di pelataran.'], 60 * 24 * 20, $site);
        $report = $this->pending(['description' => 'Retak baru di stupa teras ketiga.'], 60, $site);

        $this->browse(function (Browser $browser) use ($stupa, $sampah, $report) {
            $stupaOption = 'input[name="duplicate_of_id"][value="'.$stupa->id.'"]';
            $sampahOption = 'input[name="duplicate_of_id"][value="'.$sampah->id.'"]';

            $browser->loginAs($this->moderator)->visit(new ModerationPage);
            $browser->openReport($report->id);
            $browser->assertSeeIn('@duplicate-box', 'Tidak ada kandidat duplikat otomatis')
                ->press('Gabungkan & Konsolidasikan Klaster')
                ->waitFor('@duplicate-form')
                ->assertVisible($stupaOption)
                ->assertVisible($sampahOption)
                ->type('@duplicate-search', ModerationPage::code($sampah->id))
                ->waitUntilMissing($stupaOption)
                ->assertVisible($sampahOption)
                ->type('@duplicate-search', 'stupa')
                ->waitUntilMissing($sampahOption)
                ->assertVisible($stupaOption);
        });
    }

    /** TC.Moderasi.020 */
    public function test_duplicate_report_has_no_mark_button(): void
    {
        $site = $this->site('Candi Plaosan');
        $parent = $this->pending([], 120, $site);
        $child = $this->markedDuplicate($this->pending([], 60, $site, 10), $parent);

        $this->browse(function (Browser $browser) use ($parent, $child) {
            $browser->loginAs($this->moderator)->visit(new ModerationPage);
            $browser->filter(['status' => 'all']);
            $browser->openReport($child->id);

            $browser->assertSeeIn('[data-testid="duplicate-of-badge"]', 'Duplikat dari '.ModerationPage::code($parent->id))
                ->assertMissing('@duplicate-link')
                ->assertMissing('@duplicate-box')
                ->assertDontSee('Tandai Duplikat');
        });
    }
}
