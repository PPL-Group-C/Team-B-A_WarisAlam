<?php

namespace Database\Factories;

use App\Models\ReportPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Dipakai lewat ReportPhotoFactory::new() (model belum memakai HasFactory).
 *
 * @extends Factory<ReportPhoto>
 */
class ReportPhotoFactory extends Factory
{
    protected $model = ReportPhoto::class;

    public function definition(): array
    {
        return [
            'damage_report_id' => DamageReportFactory::new(),
            'path' => 'reports/demo/'.fake()->uuid().'.jpg',
            'original_name' => 'IMG_'.fake()->numerify('########_######').'.JPG',
            'size_kb' => fake()->numberBetween(400, 4000),
            'exif_valid' => false,
            'exif_latitude' => null,
            'exif_longitude' => null,
            'taken_at' => null,
        ];
    }
}
