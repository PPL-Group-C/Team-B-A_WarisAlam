<?php

namespace Tests\Feature\Moderation;

use App\Enums\ReportStatus;
use App\Events\ReportStatusChanged;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\User;
use App\Services\ReportStatusService;
use Database\Factories\DamageReportFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReportStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReportStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ReportStatusService::class);
    }

    public function test_valid_transition_updates_status_records_history_and_dispatches_event(): void
    {
        Event::fake([ReportStatusChanged::class]);
        $moderator = User::factory()->create();
        $report = DamageReportFactory::new()->create();

        $this->service->transition($report, ReportStatus::Verified, $moderator, 'Foto jelas dan lokasi sesuai.');

        $this->assertSame('verified', $report->status);
        $this->assertDatabaseHas('damage_reports', [
            'id' => $report->id,
            'status' => 'verified',
            'moderated_by' => $moderator->id,
        ]);
        $this->assertNotNull($report->fresh()->moderated_at);
        $this->assertDatabaseHas('report_status_histories', [
            'report_id' => $report->id,
            'from_status' => 'pending',
            'to_status' => 'verified',
            'changed_by' => $moderator->id,
            'note' => 'Foto jelas dan lokasi sesuai.',
        ]);

        Event::assertDispatched(ReportStatusChanged::class, fn (ReportStatusChanged $e) => $e->report->is($report)
            && $e->from === ReportStatus::Pending
            && $e->to === ReportStatus::Verified
            && $e->actor->is($moderator)
            && $e->note === 'Foto jelas dan lokasi sesuai.');
    }

    public function test_full_lifecycle_is_recorded_in_order(): void
    {
        $report = DamageReportFactory::new()->create();

        foreach ([ReportStatus::Verified, ReportStatus::Forwarded, ReportStatus::InProgress, ReportStatus::Resolved] as $to) {
            $this->service->transition($report, $to);
        }

        $this->assertSame('resolved', $report->fresh()->status);
        $this->assertSame(
            ['pending>verified', 'verified>forwarded', 'forwarded>in_progress', 'in_progress>resolved'],
            $report->statusHistories()->reorder('id')->get()
                ->map(fn ($h) => $h->from_status->value.'>'.$h->to_status->value)->all(),
        );
    }

    public static function invalidTransitions(): array
    {
        return [
            'pending → resolved' => ['pending', ReportStatus::Resolved],
            'pending → forwarded' => ['pending', ReportStatus::Forwarded],
            'pending → pending' => ['pending', ReportStatus::Pending],
            'rejected → verified' => ['rejected', ReportStatus::Verified],
            'resolved → pending' => ['resolved', ReportStatus::Pending],
            'verified → rejected' => ['verified', ReportStatus::Rejected],
            'status form "submitted"' => ['submitted', ReportStatus::Verified],
            'status form "draft"' => ['draft', ReportStatus::Verified],
        ];
    }

    #[DataProvider('invalidTransitions')]
    public function test_invalid_transition_is_rejected_without_side_effects(string $from, ReportStatus $to): void
    {
        Event::fake([ReportStatusChanged::class]);
        $report = DamageReportFactory::new()->state(['status' => $from])->create();

        try {
            $this->service->transition($report, $to, User::factory()->create());
            $this->fail('Transisi tidak valid seharusnya ditolak.');
        } catch (InvalidStatusTransitionException $e) {
            $this->assertStringContainsString('tidak diizinkan', $e->getMessage());
        }

        $this->assertSame($from, $report->fresh()->status);
        $this->assertNull($report->fresh()->moderated_at);
        $this->assertDatabaseCount('report_status_histories', 0);
        Event::assertNotDispatched(ReportStatusChanged::class);
    }

    public function test_service_checks_current_database_state_not_stale_instance(): void
    {
        $report = DamageReportFactory::new()->create();
        $stale = $report->replicate()->setRawAttributes($report->getAttributes(), true);

        $this->service->transition($report, ReportStatus::Rejected);

        $this->expectException(InvalidStatusTransitionException::class);
        $this->service->transition($stale, ReportStatus::Verified);
    }

    public function test_allowed_transitions_follow_the_flow(): void
    {
        $this->assertSame(
            [ReportStatus::Verified, ReportStatus::Rejected],
            $this->service->allowedTransitions(DamageReportFactory::new()->make(['status' => 'pending'])),
        );
        $this->assertSame(
            [ReportStatus::Resolved],
            $this->service->allowedTransitions(DamageReportFactory::new()->make(['status' => 'in_progress'])),
        );
        $this->assertSame([], $this->service->allowedTransitions(DamageReportFactory::new()->make(['status' => 'rejected'])));
    }
}
