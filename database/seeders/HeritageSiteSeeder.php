<?php

namespace Database\Seeders;

use App\Models\HeritageSite;
use Illuminate\Database\Seeder;

class HeritageSiteSeeder extends Seeder
{
    /** Sample data only: registration IDs are placeholders. */
    public function run(): void
    {
        $sites = [
            ['Candi Prambanan (Kompleks Percandian Roro Jonggrang, Sleman)', 'CB-DIY-SLEMAN-1991-001', 'UNESCO World Heritage Site #642', -7.7520, 110.4914],
            ['Candi Borobudur (Magelang)', 'CB-JTG-MAGELANG-1991-001', 'UNESCO World Heritage Site #592', -7.6079, 110.2038],
            ['Situs Sangiran (Sragen)', 'CB-JTG-SRAGEN-1996-001', 'UNESCO World Heritage Site #593', -7.4500, 110.8333],
        ];

        foreach ($sites as [$name, $reg, $note, $lat, $lng]) {
            HeritageSite::updateOrCreate(
                ['registration_id' => $reg],
                ['name' => $name, 'heritage_note' => $note, 'latitude' => $lat, 'longitude' => $lng],
            );
        }
    }
}
