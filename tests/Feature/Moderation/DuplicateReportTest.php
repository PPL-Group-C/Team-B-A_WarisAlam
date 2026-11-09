<?php

namespace Tests\Feature\Moderation;

use App\Enums\ReportStatus;
use App\Events\ReportStatusChanged;
use App\Models\DamageReport;
use App\Models\HeritageSite;
use App\Models\User;
use App\Services\DuplicateReportService;
use App\Support\ReportPresenter;
use Database\Factories\DamageReportFactory;
use Database\Factories\HeritageSiteFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DuplicateReportTest extends TestCase
{
    use RefreshDatabase;

    private User $moderator;

    private HeritageSite $site;

    protected function setUp(): void
    {
        parent::setUp();

        config(['moderation.moderator_emails' => ['mod@warisalam.test']]);
        $this->moderator = User::factory()->create(['email' => 'mod@warisalam.test']);
        $this->site = HeritageSiteFactory::new()->create(['latitude' => -7.7520, 'longitude' => 110.4914]);
    }

    private function reportAt(float $offsetMeters = 0, ?\DateTimeInterface $at = null, ?HeritageSite $site = null): DamageReport
    {
        return DamageReportFactory::new()
            ->atSite($site ?? $this->site, $offsetMeters)
            ->submittedAt($at ?? now())
            ->create();
    }

    private function mark(DamageReport $report, DamageReport|int $parent, array $extra = [])
    {
        return $this->actingAs($this->moderator)->post(route('moderation.duplicate', $report), [
            'duplicate_of_id' => $parent instanceof DamageReport ? $parent->id : $parent,
        ] + $extra);
    }

    // ---- Penandaan berhasil -----------------------------------------------

    public function test_moderator_can_mark_report_as_duplicate_of_same_site_report(): void
    {
        Event::fake([ReportStatusChanged::class]);
        $parent = $this->reportAt(0, now()->subHour());
        $report = $this->reportAt(12);

        $this->mark($report, $parent, ['duplicate_note' => 'Foto dan lokasi identik.'])
            ->assertRedirect(route('moderation.index'))
            ->assertSessionHas('success');

        $report->refresh();
        $reason = 'Duplikat dari laporan '.ReportPresenter::code($parent->id).'.';
        $this->assertSame('rejected', $report->status);
        $this->assertSame($parent->id, $report->duplicate_of_id);
        $this->assertTrue($report->rejected_as_duplicate);
        $this->assertSame($reason, $report->rejection_reason);
        $this->assertSame('Foto dan lokasi identik.', $report->moderator_note);
        $this->assertTrue($report->duplicateOf->is($parent));
        $this->assertTrue($parent->duplicates()->first()->is($report));
        $this->assertSame('pending', $parent->fresh()->status, 'Laporan induk tidak berubah.');
        $this->assertDatabaseHas('report_status_histories', [
            'report_id' => $report->id, 'from_status' => 'pending', 'to_status' => 'rejected',
            'changed_by' => $this->moderator->id, 'note' => $reason."\nFoto dan lokasi identik.",
        ]);
        Event::assertDispatched(ReportStatusChanged::class, fn ($e) => $e->report->rejected_as_duplicate && $e->to === ReportStatus::Rejected);
    }

    public function test_parent_may_have_any_non_duplicate_status(): void
    {
        $parent = DamageReportFactory::new()->atSite($this->site)->status(ReportStatus::Verified)->create();
        $report = $this->reportAt(5);

        $this->mark($report, $parent)->assertSessionHas('success');

        $this->assertSame($parent->id, $report->fresh()->duplicate_of_id);
    }

    // ---- Aturan penolakan -------------------------------------------------

    private function assertRefused($response, DamageReport $report, string $message): void
    {
        $response->assertRedirect(route('moderation.index', ['report' => $report->id]))
            ->assertSessionHas('error', fn ($msg) => str_contains($msg, $message));

        $report->refresh();
        $this->assertSame('pending', $report->status);
        $this->assertNull($report->duplicate_of_id);
        $this->assertFalse($report->rejected_as_duplicate);
    }

    public function test_parent_on_different_site_is_refused(): void
    {
        $otherSite = HeritageSiteFactory::new()->create(['latitude' => -7.7520, 'longitude' => 110.4914]);
        $parent = $this->reportAt(0, null, $otherSite); // titik sama persis, tapi situs berbeda
        $report = $this->reportAt();

        $this->assertRefused($this->mark($report, $parent), $report, 'situs yang sama');
    }

    public function test_linking_report_to_itself_is_refused(): void
    {
        $report = $this->reportAt();

        $this->assertRefused($this->mark($report, $report), $report, 'dirinya sendiri');
    }

    public function test_parent_that_is_itself_a_duplicate_is_refused(): void
    {
        $original = $this->reportAt(0, now()->subDay());
        $duplicate = $this->reportAt(3, now()->subHours(5));
        app(DuplicateReportService::class)->markAsDuplicate($duplicate, $original, $this->moderator);
        $report = $this->reportAt(6);

        $this->assertRefused($this->mark($report, $duplicate), $report, 'Pilih laporan aslinya');
    }

    public function test_report_that_already_has_duplicates_cannot_become_a_duplicate(): void
    {
        $parent = $this->reportAt(0, now()->subDay());
        $child = $this->reportAt(3, now()->subHours(5));
        app(DuplicateReportService::class)->markAsDuplicate($child, $parent, $this->moderator);
        $other = $this->reportAt(6);

        $this->assertRefused($this->mark($parent, $other), $parent, 'sudah menjadi induk');
    }

    public function test_only_pending_reports_can_be_marked(): void
    {
        $parent = $this->reportAt();

        foreach (['verified', 'rejected', 'in_progress', 'resolved', 'submitted'] as $status) {
            $report = DamageReportFactory::new()->atSite($this->site)->state(['status' => $status])->create();

            $this->mark($report, $parent)->assertSessionHas('error', fn ($msg) => str_contains($msg, 'Menunggu Kurasi'));
            $this->assertSame($status, $report->fresh()->status);
            $this->assertNull($report->fresh()->duplicate_of_id);
        }
    }

    public function test_parent_must_be_chosen_and_exist(): void
    {
        $report = $this->reportAt();

        $this->actingAs($this->moderator)->post(route('moderation.duplicate', $report), [])
            ->assertSessionHasErrors('duplicate_of_id');
        $this->mark($report, 999999)->assertSessionHasErrors('duplicate_of_id');

        $this->assertSame('pending', $report->fresh()->status);
    }

    public function test_non_moderator_cannot_mark_duplicates(): void
    {
        $parent = $this->reportAt();
        $report = $this->reportAt(5);

        $this->actingAs(User::factory()->create())
            ->post(route('moderation.duplicate', $report), ['duplicate_of_id' => $parent->id])
            ->assertForbidden();

        $this->assertNull($report->fresh()->duplicate_of_id);
    }

    // ---- Scope -------------------------------------------------------------

    public function test_not_duplicate_scope_excludes_duplicates(): void
    {
        $parent = $this->reportAt(0, now()->subDay());
        $duplicate = $this->reportAt(5);
        $unrelated = $this->reportAt(500);
        $plainRejected = DamageReportFactory::new()->atSite($this->site)->status(ReportStatus::Rejected)->create();
        app(DuplicateReportService::class)->markAsDuplicate($duplicate, $parent, $this->moderator);

        $ids = DamageReport::query()->notDuplicate()->pluck('id')->sort()->values()->all();

        $this->assertSame(collect([$parent->id, $unrelated->id, $plainRejected->id])->sort()->values()->all(), $ids);
        $this->assertSame(3, $this->site->reports()->notDuplicate()->count(), 'Scope bisa dipakai lewat relasi situs (skor ancaman).');
    }

    // ---- Kandidat otomatis --------------------------------------------------

    public function test_candidates_respect_site_radius_time_window_and_status(): void
    {
        $this->freezeTime();
        $report = $this->reportAt(0);

        $near = $this->reportAt(30, now()->subDays(2));                // ✔ 30 m, 2 hari
        $nearest = $this->reportAt(8, now()->addHours(3));             // ✔ 8 m
        $this->reportAt(60, now());                                    // ✘ di luar 50 m
        $this->reportAt(10, now()->subDays(8));                        // ✘ di luar 7 hari
        $this->reportAt(0, now(), HeritageSiteFactory::new()->create([  // ✘ situs lain, titik sama
            'latitude' => -7.7520, 'longitude' => 110.4914,
        ]));
        DamageReportFactory::new()->atSite($this->site, 5)->status(ReportStatus::Rejected)->create(); // ✘ ditolak
        $original = $this->reportAt(2, now()->subDay());
        $dup = $this->reportAt(4, now()->subHours(20));
        app(DuplicateReportService::class)->markAsDuplicate($dup, $original, $this->moderator);   // ✘ duplikat

        $candidates = app(DuplicateReportService::class)->candidatesFor($report);

        $this->assertSame([$original->id, $nearest->id, $near->id], $candidates->pluck('report.id')->all());
        $this->assertEqualsWithDelta(8, $candidates[1]['distance'], 0.5);
        $this->assertSame(3 * 3600, $candidates[1]['seconds']);
    }

    public function test_queue_shows_candidate_badges_without_extra_queries_per_card(): void
    {
        $a = $this->reportAt(0, now()->subHours(2));
        $b = $this->reportAt(10, now()->subHour());
        $lonely = $this->reportAt(900);

        $response = $this->actingAs($this->moderator)->get('/moderasi')->assertOk();
        $map = $response->viewData('candidateMap');

        $this->assertSame([$b->id], $map[$a->id]);
        $this->assertSame([$a->id], $map[$b->id]);
        $this->assertArrayNotHasKey($lonely->id, $map);
        $response->assertSee('Kandidat Duplikat')->assertSee('Mirip '.ReportPresenter::code($b->id));

        $count = function (): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($this->moderator)->get('/moderasi')->assertOk();

            return count(DB::getQueryLog());
        };
        $before = $count();
        foreach (range(1, 8) as $i) {
            $this->reportAt($i * 3, now()->subMinutes($i));
        }
        $this->assertSame($before, $count());
    }

    // ---- Tampilan detail --------------------------------------------------

    public function test_detail_shows_comparison_badge_and_child_list(): void
    {
        $parent = $this->reportAt(0, now()->subHour());
        $parent->update(['description' => 'Coretan pilox biru di arca Ganesha.']);
        $report = $this->reportAt(12);

        $this->actingAs($this->moderator)->get(route('moderation.index', ['report' => $report->id]))
            ->assertOk()
            ->assertSee('Deteksi Duplikat')
            ->assertSee('1 kandidat')
            ->assertSee('Coretan pilox biru di arca Ganesha.')
            ->assertSee('data-action="duplicate"', false);

        app(DuplicateReportService::class)->markAsDuplicate($report, $parent, $this->moderator);

        $this->get(route('moderation.index', ['status' => 'all', 'report' => $report->id]))
            ->assertSee('Duplikat dari '.ReportPresenter::code($parent->id))
            ->assertSee(route('moderation.index', ['status' => 'all', 'report' => $parent->id]))
            ->assertDontSee('data-action="duplicate"', false);

        $this->get(route('moderation.index', ['report' => $parent->id]))
            ->assertSee('Laporan Duplikat yang Ditautkan (1)')
            ->assertSee(ReportPresenter::code($report->id));
    }

    public function test_stats_count_duplicates(): void
    {
        $parent = $this->reportAt(0, now()->subHour());
        app(DuplicateReportService::class)->markAsDuplicate($this->reportAt(5), $parent, $this->moderator);
        DamageReportFactory::new()->status(ReportStatus::Rejected)->create(); // penolakan biasa tidak dihitung

        $this->assertSame(1, $this->actingAs($this->moderator)->get('/moderasi')->viewData('stats')['duplicates']);
    }
}
