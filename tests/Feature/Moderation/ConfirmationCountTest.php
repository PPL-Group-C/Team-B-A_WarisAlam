<?php

namespace Tests\Feature\Moderation;

use App\Enums\ConfirmationType;
use App\Models\DamageReport;
use App\Models\User;
use Database\Factories\DamageReportFactory;
use Database\Factories\ReportConfirmationFactory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConfirmationCountTest extends TestCase
{
    use RefreshDatabase;

    private User $moderator;

    protected function setUp(): void
    {
        parent::setUp();

        config(['moderation.moderator_emails' => ['mod@warisalam.test']]);
        $this->moderator = User::factory()->create(['email' => 'mod@warisalam.test']);
    }

    private function vote(DamageReport $report, int $confirms, int $disputes = 0): void
    {
        ReportConfirmationFactory::new()->count($confirms)->create(['report_id' => $report->id]);
        ReportConfirmationFactory::new()->dispute()->count($disputes)->create(['report_id' => $report->id]);
    }

    // ---- Data -------------------------------------------------------------

    public function test_counts_scope_separates_confirm_and_dispute(): void
    {
        $report = DamageReportFactory::new()->create();
        $this->vote($report, 3, 2);
        $this->vote(DamageReportFactory::new()->create(), 5); // laporan lain tidak ikut terhitung

        $loaded = DamageReport::query()->withConfirmationCounts()->find($report->id);

        $this->assertSame(3, (int) $loaded->confirm_count);
        $this->assertSame(2, (int) $loaded->dispute_count);
        $this->assertSame(ConfirmationType::Dispute, $report->confirmations()->where('type', 'dispute')->first()->type);
    }

    public function test_one_vote_per_user_per_report(): void
    {
        $report = DamageReportFactory::new()->create();
        $user = User::factory()->create();
        ReportConfirmationFactory::new()->create(['report_id' => $report->id, 'user_id' => $user->id]);

        $this->expectException(QueryException::class);
        ReportConfirmationFactory::new()->dispute()->create(['report_id' => $report->id, 'user_id' => $user->id]);
    }

    public function test_confirmations_are_deleted_with_report(): void
    {
        $report = DamageReportFactory::new()->create();
        $this->vote($report, 2, 1);

        $report->delete();

        $this->assertDatabaseCount('report_confirmations', 0);
    }

    // ---- Tampilan ---------------------------------------------------------

    public function test_detail_credibility_box_shows_counts(): void
    {
        $report = DamageReportFactory::new()->create();
        $this->vote($report, 4, 1);

        $response = $this->actingAs($this->moderator)->get(route('moderation.index', ['report' => $report->id]))->assertOk();

        $this->assertSame(4, (int) $response->viewData('selected')->confirm_count);
        $this->assertSame(1, (int) $response->viewData('selected')->dispute_count);
        $response->assertSee('Tanggapan Warga atas Laporan Ini')
            ->assertSeeInOrder(['data-testid="confirm-count">4<', 'data-testid="dispute-count">1<'], false)
            ->assertDontSee('data-testid="disputed-warning"', false);
    }

    public function test_detail_warns_when_disputes_outnumber_confirmations(): void
    {
        $report = DamageReportFactory::new()->create();
        $this->vote($report, 1, 3);

        $this->actingAs($this->moderator)->get(route('moderation.index', ['report' => $report->id]))
            ->assertSee('data-testid="disputed-warning"', false);
    }

    public function test_queue_card_badge_only_for_reports_with_votes(): void
    {
        $voted = DamageReportFactory::new()->submittedAt(now()->subHour())->create();
        $this->vote($voted, 2, 1);
        DamageReportFactory::new()->create();

        $response = $this->actingAs($this->moderator)->get('/moderasi')->assertOk();

        $this->assertSame(1, substr_count($response->getContent(), 'data-testid="confirmation-badge"'));
        $response->assertSee('2 warga mengonfirmasi, 1 membantah');
    }

    // ---- Urutan -----------------------------------------------------------

    public function test_sort_by_confirmations_puts_most_confirmed_first(): void
    {
        $few = DamageReportFactory::new()->submittedAt(now()->subDays(3))->create();
        $many = DamageReportFactory::new()->submittedAt(now()->subHour())->create();
        $none = DamageReportFactory::new()->submittedAt(now()->subDays(5))->create();
        $tieDisputed = DamageReportFactory::new()->submittedAt(now()->subDays(4))->create();
        $this->vote($few, 1);
        $this->vote($many, 5);
        $this->vote($tieDisputed, 1, 2);

        $ids = $this->actingAs($this->moderator)->get('/moderasi?sort=confirmations')
            ->assertOk()
            ->viewData('queue')->pluck('id')->all();

        $this->assertSame([$many->id, $few->id, $tieDisputed->id, $none->id], $ids);

        $default = $this->get('/moderasi')->viewData('queue')->pluck('id')->all();
        $this->assertSame([$none->id, $tieDisputed->id, $few->id, $many->id], $default, 'Default tetap FIFO.');
    }

    public function test_sort_option_is_listed(): void
    {
        $this->actingAs($this->moderator)->get('/moderasi')
            ->assertSee('Konfirmasi warga terbanyak');
    }

    // ---- Performa ---------------------------------------------------------

    public function test_counts_do_not_add_queries_per_card(): void
    {
        $count = function (): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($this->moderator)->get('/moderasi?sort=confirmations')->assertOk();

            return count(DB::getQueryLog());
        };

        $this->vote(DamageReportFactory::new()->create(), 1);
        $before = $count();

        foreach (range(1, 6) as $i) {
            $this->vote(DamageReportFactory::new()->create(), $i, $i % 2);
        }

        $this->assertSame($before, $count());
    }
}
