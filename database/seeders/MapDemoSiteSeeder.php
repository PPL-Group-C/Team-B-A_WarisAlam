<?php

namespace Database\Seeders;

use App\Enums\SiteStatus;
use App\Models\HeritageSite;
use Illuminate\Database\Seeder;

/**
 * Demo sites for the public map (PBI-10–12). Safe to run more than once.
 *
 *   php artisan db:seed --class=MapDemoSiteSeeder
 *
 * Sites are matched by registration_id, so sites created by SiteDetailDemoSeeder
 * or ModerationDemoSeeder keep their name and are not duplicated.
 * Status follows the threat score thresholds until the Sprint 2 scheduler runs.
 */
class MapDemoSiteSeeder extends Seeder
{
    public function run(): void
    {
        foreach (require database_path('data/demo_sites.php') as $data) {
            $site = HeritageSite::firstOrCreate(
                ['registration_id' => $data['registration_id']],
                [
                    'name' => $data['name'],
                    'heritage_note' => $data['category'],
                    'latitude' => $data['lat'],
                    'longitude' => $data['lng'],
                ],
            );

            $site->fill([
                'type' => $data['type'],
                'category' => $data['category'],
                'province' => $data['province'],
                'city' => $data['city'],
                'status' => SiteStatus::fromScore($data['threat_score'])->value,
                'threat_score' => $data['threat_score'],
            ])->save();
        }
    }
}
