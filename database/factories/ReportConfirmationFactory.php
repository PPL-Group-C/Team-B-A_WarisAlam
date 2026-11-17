<?php

namespace Database\Factories;

use App\Enums\ConfirmationType;
use App\Models\ReportConfirmation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Dipakai lewat ReportConfirmationFactory::new().
 *
 * @extends Factory<ReportConfirmation>
 */
class ReportConfirmationFactory extends Factory
{
    protected $model = ReportConfirmation::class;

    public function definition(): array
    {
        return [
            'report_id' => DamageReportFactory::new(),
            'user_id' => \App\Models\User::factory(),
            'type' => ConfirmationType::Confirm,
        ];
    }

    public function dispute(): static
    {
        return $this->state(['type' => ConfirmationType::Dispute]);
    }
}
