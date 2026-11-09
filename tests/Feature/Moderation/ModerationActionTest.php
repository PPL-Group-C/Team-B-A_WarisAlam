<?php

namespace Tests\Feature\Moderation;

use App\Enums\ReportStatus;
use App\Events\ReportStatusChanged;
use App\Models\DamageReport;
use App\Models\User;
use App\Services\ReportStatusService;
use Database\Factories\DamageReportFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ModerationActionTest extends TestCase
{
    use RefreshDatabase;

    private User $moderator;

    protected function setUp(): void
    {
        parent::setUp();

        config(['moderation.moderator_emails' => ['mod@warisalam.test']]);
        $this->moderator = User::factory()->create(['email' => 'mod@warisalam.test']);
    }

    private function pending(array $attributes = []): DamageReport
    {
        return DamageReportFactory::new()->create($attributes);
    }

    private function inProgress(): DamageReport
    {
        return DamageReportFactory::new()->status(ReportStatus::InProgress)->create();
    }

    // ---- Setujui ----------------------------------------------------------

    public function test_moderator_can_approve_pending_report(): void
    {
        Event::fake([ReportStatusChanged::class]);
        $report = $this->pending(['severity' => 3]);

        $this->actingAs($this->moderator)
            ->post(route('moderation.approve', $report), ['severity' => 3, 'moderator_note' => 'Foto valid, lokasi sesuai.'])
            ->assertRedirect(route('moderation.index'))
            ->assertSessionHas('success');

        $report->refresh();
        $this->assertSame('verified', $report->status);
        $this->assertSame('Foto valid, lokasi sesuai.', $report->moderator_note);
        $this->assertNull($report->severity_original, 'Severity tidak berubah, nilai asli tidak perlu disimpan.');
        $this->assertSame($this->moderator->id, $report->moderated_by);
        $this->assertDatabaseHas('report_status_histories', [
            'report_id' => $report->id, 'from_status' => 'pending', 'to_status' => 'verified',
            'changed_by' => $this->moderator->id, 'note' => 'Foto valid, lokasi sesuai.',
        ]);
        Event::assertDispatched(ReportStatusChanged::class, fn ($e) => $e->to === ReportStatus::Verified);
    }

    public function test_approve_without_note_is_allowed(): void
    {
        $report = $this->pending();

        $this->actingAs($this->moderator)->post(route('moderation.approve', $report))->assertSessionHasNoErrors();

        $this->assertSame('verified', $report->fresh()->status);
        $this->assertNull($report->fresh()->moderator_note);
    }

    public function test_approve_with_adjusted_severity_keeps_reporters_original_value(): void
    {
        $report = $this->pending(['severity' => 3]);

        $this->actingAs($this->moderator)
            ->post(route('moderation.approve', $report), ['severity' => 5])
            ->assertSessionHasNoErrors();

        $report->refresh();
        $this->assertSame(5, (int) $report->severity);
        $this->assertSame(3, (int) $report->severity_original);
        $this->assertStringContainsString('L3 → L5', $report->statusHistories()->first()->note);
    }

    public function test_severity_original_is_not_overwritten_by_later_adjustment(): void
    {
        // Disesuaikan sebelumnya (mis. oleh moderator lain) dari 2 → 4, lalu disesuaikan lagi.
        $report = $this->pending(['severity' => 4, 'severity_original' => 2]);

        $this->actingAs($this->moderator)->post(route('moderation.approve', $report), ['severity' => 1]);

        $this->assertSame(1, (int) $report->fresh()->severity);
        $this->assertSame(2, (int) $report->fresh()->severity_original);
    }

    public function test_approve_rejects_invalid_severity(): void
    {
        $report = $this->pending();

        $this->actingAs($this->moderator)
            ->post(route('moderation.approve', $report), ['severity' => 9])
            ->assertSessionHasErrors('severity');

        $this->assertSame('pending', $report->fresh()->status);
    }

    // ---- Tolak ------------------------------------------------------------

    public function test_moderator_can_reject_with_reason_readable_by_reporter(): void
    {
        $report = $this->pending();
        $reason = 'Foto buram dan tidak menunjukkan kerusakan yang dilaporkan.';

        $this->actingAs($this->moderator)
            ->post(route('moderation.reject', $report), ['rejection_reason' => $reason, 'moderator_note' => 'Cek ulang bila dikirim lagi.'])
            ->assertRedirect(route('moderation.index'))
            ->assertSessionHas('success');

        $report->refresh();
        $this->assertSame('rejected', $report->status);
        $this->assertSame($reason, $report->rejection_reason);
        $this->assertSame('Cek ulang bila dikirim lagi.', $report->moderator_note);
        $this->assertDatabaseHas('report_status_histories', [
            'report_id' => $report->id, 'to_status' => 'rejected', 'note' => $reason,
        ]);
    }

    public static function invalidReasons(): array
    {
        return [
            'tanpa alasan' => [[]],
            'alasan kosong' => [['rejection_reason' => '']],
            'alasan hanya spasi' => [['rejection_reason' => '          ']],
            'kurang dari 10 karakter' => [['rejection_reason' => 'Buram']],
        ];
    }

    #[DataProvider('invalidReasons')]
    public function test_reject_without_valid_reason_fails(array $payload): void
    {
        Event::fake([ReportStatusChanged::class]);
        $report = $this->pending();

        $this->actingAs($this->moderator)
            ->from(route('moderation.index', ['report' => $report->id]))
            ->post(route('moderation.reject', $report), $payload)
            ->assertRedirect(route('moderation.index', ['report' => $report->id]))
            ->assertSessionHasErrors('rejection_reason');

        $this->assertSame('pending', $report->fresh()->status);
        $this->assertNull($report->fresh()->rejection_reason);
        $this->assertDatabaseCount('report_status_histories', 0);
        Event::assertNotDispatched(ReportStatusChanged::class);
    }

    // ---- Konfirmasi selesai ---------------------------------------------

    public function test_moderator_can_confirm_resolution_of_in_progress_report(): void
    {
        $report = $this->inProgress();

        $this->actingAs($this->moderator)
            ->post(route('moderation.resolve', $report), ['resolution_note' => 'Foto pasca-penanganan menunjukkan coretan sudah bersih.'])
            ->assertSessionHas('success');

        $this->assertSame('resolved', $report->fresh()->status);
        $this->assertDatabaseHas('report_status_histories', [
            'report_id' => $report->id, 'from_status' => 'in_progress', 'to_status' => 'resolved',
            'note' => 'Foto pasca-penanganan menunjukkan coretan sudah bersih.',
        ]);
    }

    public function test_resolution_requires_a_note(): void
    {
        $report = $this->inProgress();

        $this->actingAs($this->moderator)
            ->post(route('moderation.resolve', $report), ['resolution_note' => ''])
            ->assertSessionHasErrors('resolution_note');

        $this->assertSame('in_progress', $report->fresh()->status);
    }

    // ---- Transisi tidak valid ---------------------------------------------

    public static function invalidActions(): array
    {
        $reason = ['rejection_reason' => 'Alasan yang cukup panjang.'];
        $note = ['resolution_note' => 'Catatan penyelesaian cukup panjang.'];

        return [
            'setujui laporan ditolak' => ['rejected', 'moderation.approve', []],
            'tolak laporan terverifikasi' => ['verified', 'moderation.reject', $reason],
            'setujui laporan in_progress' => ['in_progress', 'moderation.approve', []],
            'selesaikan laporan pending' => ['pending', 'moderation.resolve', $note],
            'selesaikan laporan diteruskan' => ['forwarded', 'moderation.resolve', $note],
            'selesaikan laporan sudah selesai' => ['resolved', 'moderation.resolve', $note],
        ];
    }

    #[DataProvider('invalidActions')]
    public function test_action_on_wrong_status_is_refused_by_service(string $status, string $route, array $payload): void
    {
        $report = DamageReportFactory::new()->state(['status' => $status])->create();

        $this->actingAs($this->moderator)
            ->post(route($route, $report), $payload)
            ->assertRedirect(route('moderation.index', ['report' => $report->id]))
            ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'tidak diizinkan'));

        $this->assertSame($status, $report->fresh()->status);
        $this->assertDatabaseCount('report_status_histories', 0);
    }

    // ---- Otorisasi --------------------------------------------------------

    public static function actions(): array
    {
        return [
            'setujui' => ['moderation.approve', []],
            'tolak' => ['moderation.reject', ['rejection_reason' => 'Alasan yang cukup panjang.']],
            'selesai' => ['moderation.resolve', ['resolution_note' => 'Catatan penyelesaian.']],
        ];
    }

    #[DataProvider('actions')]
    public function test_non_moderator_cannot_run_actions(string $route, array $payload): void
    {
        $report = $this->pending();

        $this->actingAs(User::factory()->create())->post(route($route, $report), $payload)->assertForbidden();

        $this->assertSame('pending', $report->fresh()->status);
    }

    public function test_guest_cannot_run_actions(): void
    {
        $report = $this->pending();

        $this->post(route('moderation.approve', $report))->assertForbidden();
        $this->assertSame('pending', $report->fresh()->status);
    }

    // ---- Alur antrean -----------------------------------------------------

    public function test_after_action_next_report_in_filtered_queue_is_selected(): void
    {
        $first = $this->pending(['severity' => 5, 'submitted_at' => now()->subDays(2)]);
        $next = $this->pending(['severity' => 4, 'submitted_at' => now()->subDay()]);
        $this->pending(['severity' => 1, 'submitted_at' => now()->subDays(3)]);

        $redirect = $this->actingAs($this->moderator)
            ->post(route('moderation.approve', $first), ['return_query' => 'sort=severity&report=999&evil=1'])
            ->assertRedirect(route('moderation.index', ['sort' => 'severity']));

        $this->followRedirects($redirect)->assertOk()->assertSee('data-selected-report="'.$next->id.'"', false);
    }

    public function test_page_shows_actions_matching_status(): void
    {
        $pending = $this->pending();
        $inProgress = $this->inProgress();
        $verified = DamageReportFactory::new()->status(ReportStatus::Verified)->create();

        $this->actingAs($this->moderator);

        $page = $this->get(route('moderation.index', ['report' => $pending->id]))->assertOk();
        $page->assertSee('data-action="approve"', false)->assertSee('data-action="reject"', false)
            ->assertDontSee('data-action="resolve"', false);
        $this->assertSame([ReportStatus::Verified, ReportStatus::Rejected], $page->viewData('allowedTransitions'));

        $page = $this->get(route('moderation.index', ['report' => $inProgress->id, 'status' => 'all']))->assertOk();
        $page->assertSee('data-action="resolve"', false);
        $this->assertSame([ReportStatus::Resolved], $page->viewData('allowedTransitions'));

        $page = $this->get(route('moderation.index', ['report' => $verified->id]))->assertOk();
        $page->assertDontSee('data-action="resolve"', false)
            ->assertSee('Setujui/Tolak hanya tersedia');
    }

    public function test_rejected_report_detail_shows_reason(): void
    {
        $report = $this->pending();
        app(ReportStatusService::class)->transition($report, ReportStatus::Rejected, $this->moderator, null, [
            'rejection_reason' => 'Lokasi tidak sesuai dengan situs yang dipilih.',
        ]);

        $this->actingAs($this->moderator)
            ->get(route('moderation.index', ['report' => $report->id]))
            ->assertSee('Alasan Penolakan')
            ->assertSee('Lokasi tidak sesuai dengan situs yang dipilih.');
    }

    public function test_curation_log_lists_latest_ten_actions_newest_first(): void
    {
        $service = app(ReportStatusService::class);
        $reports = DamageReportFactory::new()->count(12)->create();
        foreach ($reports as $report) {
            $service->transition($report, ReportStatus::Verified, $this->moderator, "Catatan {$report->id}");
        }

        $log = $this->actingAs($this->moderator)->get('/moderasi')->assertOk()
            ->assertSee('Log Tindakan Kurasi Terkini')
            ->viewData('recentActions');

        $this->assertCount(10, $log);
        $this->assertSame($reports->last()->id, $log->first()->report_id);
        $this->assertTrue($log->first()->relationLoaded('changedBy'));
    }
}
