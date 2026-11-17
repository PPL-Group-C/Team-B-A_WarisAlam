<?php

namespace Tests\Unit;

use App\Enums\SiteStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SiteStatusTest extends TestCase
{
    /** @return array<string, array{float, SiteStatus}> */
    public static function scores(): array
    {
        return [
            'nol' => [0.0, SiteStatus::Safe],
            'tepat di bawah waspada' => [4.99, SiteStatus::Safe],
            'batas waspada' => [5.0, SiteStatus::Watch],
            'tepat di bawah terancam' => [14.99, SiteStatus::Watch],
            'batas terancam' => [15.0, SiteStatus::Threatened],
            'sangat tinggi' => [42.0, SiteStatus::Threatened],
        ];
    }

    #[DataProvider('scores')]
    public function test_status_follows_fr24_thresholds(float $score, SiteStatus $expected): void
    {
        $this->assertSame($expected, SiteStatus::fromScore($score));
    }

    public function test_labels_and_colors_match_the_map_legend(): void
    {
        $this->assertSame(['Aman', 'Waspada', 'Terancam'], array_map(fn (SiteStatus $s) => $s->label(), SiteStatus::cases()));
        $this->assertSame(['#059669', '#f59e0b', '#dc2626'], array_map(fn (SiteStatus $s) => $s->color(), SiteStatus::cases()));
    }
}
