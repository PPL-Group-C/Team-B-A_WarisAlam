<?php

namespace Tests\Browser\Concerns;

use App\Models\DamageReport;
use App\Models\HeritageSite;
use App\Models\User;
use Laravel\Dusk\Browser;

trait InteractsWithReportForm
{
    protected function site(): HeritageSite
    {
        return HeritageSite::create([
            'name' => 'Candi Prambanan (Kompleks Percandian Roro Jonggrang, Sleman)',
            'registration_id' => 'CB-DIY-SLEMAN-1991-001',
            'heritage_note' => 'UNESCO World Heritage Site #642',
            'latitude' => -7.7520, 'longitude' => 110.4914,
        ]);
    }

    protected function reporter(string $name = 'Budi Santoso'): User
    {
        return User::factory()->create(['name' => $name]);
    }

    protected function fixture(string $file): string
    {
        return base_path("tests/Browser/fixtures/{$file}");
    }

    /** Step 1: search and pick the seeded site. The pin moves to the site (source "site"). */
    protected function chooseSite(Browser $browser): Browser
    {
        return $browser->type('#site-search', 'Prambanan')
            ->waitFor('#site-search ~ ul button')
            ->click('#site-search ~ ul button')
            ->waitForText('Pin di titik situs');
    }

    /** Step 2: upload one photo (the file input is visually hidden but attachable). */
    protected function attachPhoto(Browser $browser, string $file = 'geotagged.jpg'): Browser
    {
        return $browser->attach('photos[]', $this->fixture($file))
            ->waitForText('1 / 5 FOTO TERUNGGAH', 10);
    }

    /** Steps 3-4: category, description, declaration. */
    protected function completeDetails(Browser $browser): Browser
    {
        return $browser->click('label:has(input[value="vandalism"])')
            ->type('description', 'Ditemukan coretan spidol permanen pada relief bagian selatan.')
            ->check('declaration');
    }

    protected function submit(Browser $browser): Browser
    {
        return $browser->click('button[name="intent"][value="submit"]');
    }

    protected function createReport(User $owner, bool $anonymous, string $status = 'submitted'): DamageReport
    {
        return DamageReport::create([
            'user_id' => $owner->id,
            'heritage_site_id' => ($this->site())->id,
            'status' => $status,
            'category' => 'vandalism',
            'severity' => 3,
            'description' => 'Coretan spidol pada relief.',
            'is_anonymous' => $anonymous,
            'submitted_at' => now(),
        ]);
    }
}
