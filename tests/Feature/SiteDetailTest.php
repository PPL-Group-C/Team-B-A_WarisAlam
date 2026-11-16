<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\DamageReport;
use App\Models\HeritageSite;
use App\Models\SiteAdoption;
use App\Models\SitePhoto;
use App\Models\User;
use App\Services\ReportStatusService;
use App\Support\ReportPresenter;
use Database\Factories\DamageReportFactory;
use Database\Factories\HeritageSiteFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Detail Situs (Raihan): PBI-13 info & galeri, PBI-14 status & skor, PBI-15 riwayat laporan. */
class SiteDetailTest extends TestCase
{
    use RefreshDatabase;

    private function site(array $attributes = []): HeritageSite
    {
        return HeritageSiteFactory::new()->create([
            'name' => 'Candi Uji Coba',
            'type' => 'cultural',
            'category' => 'Cagar Budaya Nasional',
            'province' => 'Jawa Tengah',
            'city' => 'Kabupaten Magelang',
            'description' => 'Candi untuk pengujian halaman detail.',
            'history' => "Dibangun pada abad ke-9.\n\nDipugar pada 1980.",
            'facts' => json_encode([['label' => 'Tinggi', 'value' => '35 meter']]),
            'status' => 'safe',
            'threat_score' => 1.5,
            ...$attributes,
        ]);
    }

    private function report(HeritageSite $site, ReportStatus $status = ReportStatus::Verified, array $attributes = []): DamageReport
    {
        return DamageReportFactory::new()->atSite($site)->create(['status' => $status->value, ...$attributes]);
    }

    // ---- PBI-13 -----------------------------------------------------------

    public function test_detail_page_is_public_and_shows_history_and_facts(): void
    {
        $site = $this->site();

        $this->get(route('sites.show', $site))
            ->assertOk()
            ->assertSee('Candi Uji Coba')
            ->assertSee('Cagar Budaya')
            ->assertSee('Kabupaten Magelang, Jawa Tengah')
            ->assertSee($site->registration_id)
            ->assertSee('Dibangun pada abad ke-9.')
            ->assertSee('Dipugar pada 1980.')
            ->assertSeeInOrder(['Tinggi', '35 meter']);
    }

    public function test_detail_url_matches_the_map_popup_link(): void
    {
        $site = $this->site();

        // Peta (Fasya) membentuk link popup sebagai url('/sites/{id}').
        $this->assertSame(url('/sites/'.$site->id), route('sites.show', $site));
    }

    public function test_unknown_site_returns_404(): void
    {
        $this->get('/sites/999999')->assertNotFound();
    }

    public function test_gallery_lists_photos_in_display_order(): void
    {
        $site = $this->site();
        SitePhoto::create(['heritage_site_id' => $site->id, 'path' => 'sites/b.jpg', 'caption' => 'Foto kedua', 'sort_order' => 2]);
        SitePhoto::create(['heritage_site_id' => $site->id, 'path' => 'sites/a.jpg', 'caption' => 'Foto pertama', 'sort_order' => 1]);
        SitePhoto::create(['heritage_site_id' => $this->site(['name' => 'Situs Lain'])->id, 'path' => 'sites/x.jpg', 'caption' => 'Foto situs lain']);

        $this->get(route('sites.show', $site))
            ->assertOk()
            ->assertSee('2 foto')
            ->assertSeeInOrder(['Foto pertama', 'Foto kedua'])
            ->assertDontSee('Foto situs lain');
    }

    public function test_gallery_shows_empty_state_without_photos(): void
    {
        $this->get(route('sites.show', $this->site()))
            ->assertOk()
            ->assertSee('Belum ada foto untuk situs ini.');
    }

    // ---- PBI-14 -----------------------------------------------------------

    public function test_status_score_and_adopter_count_are_shown(): void
    {
        $site = $this->site(['status' => 'watch', 'threat_score' => 8.7]);
        User::factory()->count(2)->create()->each(
            fn (User $user) => SiteAdoption::create(['heritage_site_id' => $site->id, 'user_id' => $user->id]),
        );

        $this->get(route('sites.show', $site))
            ->assertOk()
            ->assertSee('Status: Waspada')
            ->assertSee('<b class="text-2xl" data-testid="threat-score">8,7</b>', false)
            ->assertSee('<dd class="text-lg font-bold text-forest" data-testid="adopters-count">2</dd>', false);
    }

    public function test_threatened_site_shows_threatened_status(): void
    {
        $this->get(route('sites.show', $this->site(['status' => 'threatened', 'threat_score' => 18.3])))
            ->assertOk()
            ->assertSee('Status: Terancam');
    }

    // ---- PBI-15 -----------------------------------------------------------

    public function test_history_only_lists_verified_non_duplicate_reports_of_this_site(): void
    {
        $site = $this->site();
        $verified = $this->report($site, ReportStatus::Verified);
        $resolved = $this->report($site, ReportStatus::Resolved);
        $pending = $this->report($site, ReportStatus::Pending);
        $rejected = $this->report($site, ReportStatus::Rejected);
        $duplicate = $this->report($site, ReportStatus::Verified, ['duplicate_of_id' => $verified->id]);
        $otherSite = $this->report($this->site(['name' => 'Situs Lain']), ReportStatus::Verified);

        $response = $this->get(route('sites.show', $site))->assertOk();

        $response->assertSee(ReportPresenter::code($verified->id))
            ->assertSee(ReportPresenter::code($resolved->id))
            ->assertSee('2 laporan');

        foreach ([$pending, $rejected, $duplicate, $otherSite] as $hidden) {
            $response->assertDontSee(ReportPresenter::code($hidden->id));
        }
    }

    public function test_history_shows_empty_state_without_verified_reports(): void
    {
        $site = $this->site();
        $this->report($site, ReportStatus::Pending);

        $this->get(route('sites.show', $site))
            ->assertOk()
            ->assertSee('Belum ada laporan kerusakan terverifikasi.');
    }

    public function test_anonymous_reporter_identity_is_hidden(): void
    {
        $site = $this->site();
        $this->report($site, ReportStatus::Verified, [
            'user_id' => User::factory()->create(['name' => 'Nama Rahasia Pelapor'])->id,
            'is_anonymous' => true,
        ]);
        $this->report($site, ReportStatus::Verified, [
            'user_id' => User::factory()->create(['name' => 'Budi Terbuka'])->id,
        ]);

        $this->get(route('sites.show', $site))
            ->assertOk()
            ->assertSee('Pelapor Anonim')
            ->assertSee('Budi Terbuka')
            ->assertDontSee('Nama Rahasia Pelapor');
    }

    public function test_history_is_paginated(): void
    {
        $site = $this->site();
        $reports = collect(range(1, 6))->map(fn (int $i) => $this->report($site, ReportStatus::Verified, [
            'submitted_at' => now()->subDays($i),
        ]));

        $this->get(route('sites.show', $site))
            ->assertSee(ReportPresenter::code($reports->first()->id))
            ->assertDontSee(ReportPresenter::code($reports->last()->id));

        $this->get(route('sites.show', ['site' => $site, 'page' => 2]))
            ->assertOk()
            ->assertSee(ReportPresenter::code($reports->last()->id));
    }

    public function test_case_page_shows_the_handling_timeline_in_order(): void
    {
        $site = $this->site();
        $moderator = User::factory()->create();
        $report = $this->report($site, ReportStatus::Pending, [
            'user_id' => User::factory()->create(['name' => 'Pelapor Rahasia'])->id,
            'is_anonymous' => true,
        ]);

        $statuses = app(ReportStatusService::class);
        $statuses->transition($report, ReportStatus::Verified, $moderator, 'Foto dan lokasi sesuai.');
        $statuses->transition($report, ReportStatus::Forwarded, null, 'Diteruskan ke BPK Wilayah X.');
        $statuses->transition($report, ReportStatus::InProgress, null, 'Tim BPK menjadwalkan pembersihan.');

        $this->get(route('sites.show', $site))
            ->assertSee(route('sites.reports.show', [$site, $report]), false);

        $this->get(route('sites.reports.show', [$site, $report]))
            ->assertOk()
            ->assertSee('Kasus '.ReportPresenter::code($report->id))
            ->assertSeeInOrder([
                'Laporan dikirim warga',
                'Terverifikasi', 'Foto dan lokasi sesuai.',
                'Diteruskan', 'Diteruskan ke BPK Wilayah X.',
                'Dalam Penanganan', 'Tim BPK menjadwalkan pembersihan.',
            ])
            ->assertSee('Pelapor Anonim')
            ->assertDontSee('Pelapor Rahasia');
    }

    public function test_case_page_is_hidden_for_unverified_duplicate_or_mismatched_reports(): void
    {
        $site = $this->site();
        $verified = $this->report($site, ReportStatus::Verified);

        $this->get(route('sites.reports.show', [$site, $this->report($site, ReportStatus::Pending)]))->assertNotFound();
        $this->get(route('sites.reports.show', [$site, $this->report($site, ReportStatus::Rejected)]))->assertNotFound();
        $this->get(route('sites.reports.show', [$site, $this->report($site, ReportStatus::Verified, ['duplicate_of_id' => $verified->id])]))->assertNotFound();

        $otherSite = $this->site(['name' => 'Situs Lain']);
        $this->get(route('sites.reports.show', [$otherSite, $verified]))->assertNotFound();
    }
}
