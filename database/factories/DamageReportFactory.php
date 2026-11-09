<?php

namespace Database\Factories;

use App\Enums\DamageCategory;
use App\Enums\ReportStatus;
use App\Models\DamageReport;
use App\Models\HeritageSite;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Dipakai lewat DamageReportFactory::new() (model belum memakai HasFactory).
 *
 * @extends Factory<DamageReport>
 */
class DamageReportFactory extends Factory
{
    protected $model = DamageReport::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'heritage_site_id' => HeritageSiteFactory::new(),
            'status' => ReportStatus::Pending->value,
            'category' => fake()->randomElement(DamageCategory::cases()),
            'severity' => fake()->numberBetween(1, 5),
            'latitude' => fake()->latitude(-8.5, -6.0),
            'longitude' => fake()->longitude(106.0, 114.0),
            'gps_accuracy_m' => fake()->numberBetween(3, 25),
            'description' => fake()->paragraph(),
            'is_anonymous' => false,
            'declaration_accepted_at' => now(),
            'submitted_at' => now(),
        ];
    }

    public function status(ReportStatus $status): static
    {
        return $this->state(['status' => $status->value]);
    }

    public function anonymous(): static
    {
        return $this->state(['is_anonymous' => true]);
    }

    /** Laporan berada tepat (atau dekat) di titik situs. */
    public function atSite(HeritageSite $site, float $offsetMeters = 0): static
    {
        $deg = $offsetMeters / 111_320;

        return $this->state([
            'heritage_site_id' => $site->id,
            'latitude' => (float) $site->latitude + $deg,
            'longitude' => (float) $site->longitude,
        ]);
    }

    public function submittedAt(\DateTimeInterface $at): static
    {
        return $this->state(['submitted_at' => $at, 'declaration_accepted_at' => $at, 'created_at' => $at]);
    }
}
