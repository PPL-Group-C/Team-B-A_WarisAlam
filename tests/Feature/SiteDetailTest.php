<?php

namespace Tests\Feature;

use App\Models\HeritageSite;
use App\Models\SitePhoto;
use Database\Factories\HeritageSiteFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Detail Situs (Raihan): PBI-13 info & galeri. */
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
}
