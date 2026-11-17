<?php

namespace Tests\Feature;

use App\Models\HeritageSite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DamageReportTest extends TestCase
{
    use RefreshDatabase;

    private function site(): HeritageSite
    {
        return HeritageSite::create([
            'name' => 'Candi Prambanan', 'registration_id' => 'CB-TEST-001',
            'latitude' => -7.7520, 'longitude' => 110.4914,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'intent' => 'submit',
            'heritage_site_id' => $this->site()->id,
            'latitude' => -7.7520, 'longitude' => 110.4914,
            'category' => 'vandalism', 'severity' => 3,
            'description' => 'Ditemukan coretan spidol permanen pada relief bagian selatan.',
            'photos' => [UploadedFile::fake()->image('bukti.jpg')],
            'declaration' => '1',
        ], $overrides);
    }

    public function test_guest_cannot_submit(): void
    {
        $this->post(route('reports.store'), [])->assertRedirect();
        $this->assertDatabaseCount('damage_reports', 0);
    }

    public function test_reporter_can_submit_photo_category_and_severity(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())
            ->post(route('reports.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('damage_reports', ['status' => 'submitted', 'category' => 'vandalism', 'severity' => 3]);
        $this->assertDatabaseCount('report_photos', 1);
    }

    public function test_submit_requires_photo_category_and_severity(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('reports.store'), $this->payload(['photos' => null, 'category' => null, 'severity' => null]))
            ->assertSessionHasErrors(['photos', 'category', 'severity']);
    }

    public function test_severity_must_be_between_one_and_five(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('reports.store'), $this->payload(['severity' => 6]))
            ->assertSessionHasErrors('severity');
    }

    public function test_more_than_five_photos_are_rejected(): void
    {
        $photos = array_map(fn ($i) => UploadedFile::fake()->image("f{$i}.jpg"), range(1, 6));

        $this->actingAs(User::factory()->create())
            ->post(route('reports.store'), $this->payload(['photos' => $photos]))
            ->assertSessionHasErrors('photos');
    }

    public function test_draft_only_needs_a_site(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('reports.store'), ['intent' => 'draft', 'heritage_site_id' => $this->site()->id])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('damage_reports', ['status' => 'draft']);
    }
}
