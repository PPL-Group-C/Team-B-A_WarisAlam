<?php

namespace Database\Factories;

use App\Models\HeritageSite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Dipakai lewat HeritageSiteFactory::new() (model belum memakai HasFactory).
 *
 * @extends Factory<HeritageSite>
 */
class HeritageSiteFactory extends Factory
{
    protected $model = HeritageSite::class;

    public function definition(): array
    {
        return [
            'name' => 'Situs '.fake()->unique()->city(),
            'registration_id' => 'CB-TEST-'.fake()->unique()->numerify('######'),
            'heritage_note' => fake()->optional()->sentence(4),
            'latitude' => fake()->latitude(-8.5, -6.0),
            'longitude' => fake()->longitude(106.0, 114.0),
        ];
    }
}
