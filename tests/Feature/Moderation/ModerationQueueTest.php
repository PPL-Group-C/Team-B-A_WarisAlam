<?php

namespace Tests\Feature\Moderation;

use App\Enums\DamageCategory;
use App\Enums\ReportStatus;
use App\Models\User;
use App\Support\ReportPresenter;
use Database\Factories\DamageReportFactory;
use Database\Factories\HeritageSiteFactory;
use Database\Factories\ReportPhotoFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ModerationQueueTest extends TestCase
{
    use RefreshDatabase;

    private User $moderator;

    protected function setUp(): void
    {
        parent::setUp();

        config(['moderation.moderator_emails' => ['mod@warisalam.test']]);
        $this->moderator = User::factory()->create(['email' => 'mod@warisalam.test']);
    }

    public function test_moderator_can_open_the_queue(): void
    {
        $site = HeritageSiteFactory::new()->create(['name' => 'Candi Prambanan']);
        DamageReportFactory::new()->atSite($site)->create();

        $this->actingAs($this->moderator)
            ->get('/moderasi')
            ->assertOk()
            ->assertSee('Dasbor Moderasi Laporan Sains Warga (F08)')
            ->assertSee('Candi Prambanan');
    }

    public function test_moderator_email_check_is_case_insensitive(): void
    {
        $user = User::factory()->create(['email' => 'Admin.Moderasi@WarisAlam.test']);
        config(['moderation.moderator_emails' => ['admin.moderasi@warisalam.test']]);

        $this->actingAs($user)->get('/moderasi')->assertOk();
    }

    public function test_warga_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/moderasi')
            ->assertForbidden();
    }

    public function test_guest_is_forbidden(): void
    {
        $this->get('/moderasi')->assertForbidden();
    }

    public function test_queue_shows_only_pending_reports_oldest_first(): void
    {
        $newest = DamageReportFactory::new()->submittedAt(now()->subMinutes(5))->create();
        $oldest = DamageReportFactory::new()->submittedAt(now()->subDays(2))->create();
        $middle = DamageReportFactory::new()->submittedAt(now()->subHours(3))->create();
        $verified = DamageReportFactory::new()->status(ReportStatus::Verified)->submittedAt(now()->subDays(5))->create();
        $draft = DamageReportFactory::new()->state(['status' => 'draft'])->create();

        $response = $this->actingAs($this->moderator)->get('/moderasi')->assertOk();

        $this->assertSame([$oldest->id, $middle->id, $newest->id], $response->viewData('queue')->pluck('id')->all());
        $this->assertSame($oldest->id, $response->viewData('selected')->id, 'Kartu pertama terpilih otomatis.');
        $response->assertDontSee('data-report-card="'.$verified->id.'"', false)
            ->assertDontSee('data-report-card="'.$draft->id.'"', false);
    }

    public function test_queue_can_be_sorted_by_severity(): void
    {
        $low = DamageReportFactory::new()->state(['severity' => 1])->submittedAt(now()->subDays(3))->create();
        $critical = DamageReportFactory::new()->state(['severity' => 5])->submittedAt(now()->subHour())->create();
        $mid = DamageReportFactory::new()->state(['severity' => 3])->submittedAt(now()->subDay())->create();

        $ids = $this->actingAs($this->moderator)->get('/moderasi?sort=severity')
            ->viewData('queue')->pluck('id')->all();

        $this->assertSame([$critical->id, $mid->id, $low->id], $ids);
    }

    public function test_queue_filters_by_category_severity_status_and_search(): void
    {
        $prambanan = HeritageSiteFactory::new()->create(['name' => 'Candi Prambanan']);
        $borobudur = HeritageSiteFactory::new()->create(['name' => 'Candi Borobudur']);
        $vandal = DamageReportFactory::new()->atSite($prambanan)->state(['category' => DamageCategory::Vandalism, 'severity' => 4])->create();
        $waste = DamageReportFactory::new()->atSite($borobudur)->state(['category' => DamageCategory::Waste, 'severity' => 2])->create();
        $verified = DamageReportFactory::new()->atSite($borobudur)->status(ReportStatus::Verified)->create();

        $ids = fn (string $query) => $this->actingAs($this->moderator)->get('/moderasi?'.$query)
            ->assertOk()->viewData('queue')->pluck('id')->all();

        $this->assertSame([$vandal->id], $ids('category=vandalism'));
        $this->assertSame([$waste->id], $ids('severity=2'));
        $this->assertSame([$vandal->id], $ids('q=prambanan'));
        $this->assertSame([$waste->id], $ids('q='.urlencode(ReportPresenter::code($waste->id))));
        $this->assertSame([$verified->id], $ids('status=verified'));
        $this->assertCount(3, $ids('status=all'));
    }

    public function test_invalid_filter_values_are_rejected(): void
    {
        $this->actingAs($this->moderator)
            ->get('/moderasi?status=hacked&severity=9')
            ->assertSessionHasErrors(['status', 'severity']);
    }

    public function test_anonymous_reporter_name_stays_visible_to_moderator_with_marker(): void
    {
        $reporter = User::factory()->create(['name' => 'Warga Rahasia']);
        $report = DamageReportFactory::new()->anonymous()->create(['user_id' => $reporter->id]);

        $this->actingAs($this->moderator)
            ->get('/moderasi?report='.$report->id)
            ->assertOk()
            ->assertSee('Warga Rahasia')
            ->assertSee('Anonim');
    }

    public function test_detail_shows_selected_report_photos_evidence_and_reporter_track_record(): void
    {
        $this->freezeTime();
        $site = HeritageSiteFactory::new()->create(['latitude' => -7.7520, 'longitude' => 110.4914]);
        $reporter = User::factory()->create();
        DamageReportFactory::new()->count(2)->status(ReportStatus::Verified)->create(['user_id' => $reporter->id]);
        DamageReportFactory::new()->status(ReportStatus::Rejected)->create(['user_id' => $reporter->id]);

        $report = DamageReportFactory::new()->atSite($site, 100)->create([
            'user_id' => $reporter->id,
            'description' => 'Coretan pilox di kaki arca Ganesha.',
            'submitted_at' => now(),
        ]);
        ReportPhotoFactory::new()->create([
            'damage_report_id' => $report->id,
            'original_name' => 'IMG_BUKTI_1.JPG',
            'exif_valid' => true,
            'exif_latitude' => -7.7511,
            'exif_longitude' => 110.4914,
            'taken_at' => now()->subMinutes(30),
        ]);
        DamageReportFactory::new()->submittedAt(now()->subYear())->create(); // laporan lain, lebih tua

        $response = $this->actingAs($this->moderator)->get('/moderasi?report='.$report->id)->assertOk();

        $this->assertSame($report->id, $response->viewData('selected')->id);
        $this->assertSame(['total' => 3, 'accepted' => 2, 'rejected' => 1], $response->viewData('reporterStats'));
        $evidence = $response->viewData('evidence');
        $this->assertEqualsWithDelta(100, $evidence['distance_to_site'], 1.0);
        $this->assertSame(1800, $evidence['upload_lag']);
        $this->assertSame(1, $evidence['exif_valid']);

        $response->assertSee('Coretan pilox di kaki arca Ganesha.')
            ->assertSee('IMG_BUKTI_1.JPG')
            ->assertSee('EXIF Valid 1/1 foto')
            ->assertSee('30 menit');
    }

    public function test_stats_count_pending_emergency_and_verified_today(): void
    {
        DamageReportFactory::new()->count(2)->state(['severity' => 5])->create();
        DamageReportFactory::new()->state(['severity' => 2])->create();
        $toVerify = DamageReportFactory::new()->submittedAt(now()->subHours(2))->create();
        app(\App\Services\ReportStatusService::class)->transition($toVerify, ReportStatus::Verified, $this->moderator);

        $stats = $this->actingAs($this->moderator)->get('/moderasi')->viewData('stats');

        $this->assertSame(3, $stats['pending']);
        $this->assertSame(2, $stats['emergency']);
        $this->assertSame(1, $stats['verified_today']);
        $this->assertEqualsWithDelta(2.0, $stats['avg_response_hours'], 0.05);
    }

    public function test_queue_does_not_run_a_query_per_report(): void
    {
        $countQueries = function (): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($this->moderator)->get('/moderasi')->assertOk();

            return count(DB::getQueryLog());
        };

        DamageReportFactory::new()->count(2)->has(ReportPhotoFactory::new()->count(2), 'photos')->create();
        $few = $countQueries();

        DamageReportFactory::new()->count(10)->has(ReportPhotoFactory::new()->count(2), 'photos')->create();
        $many = $countQueries();

        $this->assertSame($few, $many, 'Jumlah query tidak boleh bertambah seiring jumlah laporan.');
    }
}
