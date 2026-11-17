<?php

namespace Tests\Feature;

use App\Models\DamageReport;
use App\Models\HeritageSite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function report(?User $owner = null, string $status = 'submitted'): DamageReport
    {
        $site = HeritageSite::firstOrCreate(
            ['registration_id' => 'CB-TEST-003'],
            ['name' => 'Candi Uji', 'latitude' => -7.75, 'longitude' => 110.49],
        );

        return DamageReport::create([
            'user_id' => ($owner ?? User::factory()->create())->id,
            'heritage_site_id' => $site->id,
            'status' => $status,
            'category' => 'vandalism',
            'severity' => 3,
            'description' => 'Coretan spidol pada relief.',
            'submitted_at' => now(),
        ]);
    }

    public function test_guest_cannot_verify(): void
    {
        $report = $this->report();

        $this->postJson(route('reports.verify', $report), ['verdict' => 'confirm'])->assertUnauthorized();
        $this->assertDatabaseCount('report_verifications', 0);
    }

    public function test_user_can_confirm_another_users_report(): void
    {
        $report = $this->report();
        $verifier = User::factory()->create();

        $this->actingAs($verifier)
            ->post(route('reports.verify', $report), ['verdict' => 'confirm'])
            ->assertRedirect(route('reports.show', $report));

        $this->assertDatabaseHas('report_verifications', [
            'damage_report_id' => $report->id, 'user_id' => $verifier->id, 'verdict' => 'confirm',
        ]);
    }

    public function test_dispute_needs_a_reason(): void
    {
        $report = $this->report();
        $verifier = User::factory()->create();

        $this->actingAs($verifier)
            ->post(route('reports.verify', $report), ['verdict' => 'dispute'])
            ->assertSessionHasErrors('comment');

        $this->actingAs($verifier)
            ->post(route('reports.verify', $report), ['verdict' => 'dispute', 'comment' => 'Foto ini bukan dari lokasi tersebut.'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('report_verifications', ['verdict' => 'dispute']);
    }

    public function test_user_cannot_verify_their_own_report(): void
    {
        $owner = User::factory()->create();
        $report = $this->report($owner);

        $this->actingAs($owner)
            ->post(route('reports.verify', $report), ['verdict' => 'confirm'])
            ->assertForbidden();
    }

    public function test_drafts_cannot_be_verified(): void
    {
        $report = $this->report(status: 'draft');

        $this->actingAs(User::factory()->create())
            ->post(route('reports.verify', $report), ['verdict' => 'confirm'])
            ->assertNotFound();
    }

    public function test_voting_again_replaces_the_earlier_vote(): void
    {
        $report = $this->report();
        $verifier = User::factory()->create();

        $this->actingAs($verifier)->post(route('reports.verify', $report), ['verdict' => 'confirm']);
        $this->actingAs($verifier)->post(route('reports.verify', $report), [
            'verdict' => 'dispute', 'comment' => 'Setelah dicek ulang, kondisinya tidak sesuai.',
        ]);

        $this->assertDatabaseCount('report_verifications', 1);
        $this->assertDatabaseHas('report_verifications', ['user_id' => $verifier->id, 'verdict' => 'dispute']);
    }

    public function test_enough_confirmations_mark_the_report_verified(): void
    {
        $report = $this->report();

        User::factory()->count(DamageReport::CONFIRMS_TO_VERIFY)->create()->each(
            fn (User $u) => $report->verifications()->create(['user_id' => $u->id, 'verdict' => 'confirm'])
        );

        $fresh = DamageReport::withVerificationCounts()->find($report->id);

        $this->assertSame('verified', $fresh->verificationStatus());
    }

    public function test_json_exposes_counts_but_masks_anonymous_reporter(): void
    {
        $report = $this->report();
        $report->update(['is_anonymous' => true]);
        $report->verifications()->create(['user_id' => User::factory()->create()->id, 'verdict' => 'confirm']);

        $this->getJson(route('reports.show', $report))
            ->assertOk()
            ->assertJsonPath('data.reporter', 'Pelapor Anonim')
            ->assertJsonPath('data.verification.confirms', 1)
            ->assertJsonMissingPath('data.user_id');
    }
}
