<?php

namespace Tests\Unit;

use App\Support\ActivityPeriodOptions;
use Carbon\Carbon;
use Tests\TestCase;

class ActivityPeriodOptionsTest extends TestCase
{
    public function test_monthly_returns_thirty_six_months_with_current_month_first(): void
    {
        Carbon::setTestNow('2026-09-15 10:00:00');

        $monthly = ActivityPeriodOptions::monthly();

        $this->assertCount(36, $monthly);
        $this->assertSame(array_key_first($monthly), '2026-09');
        $this->assertArrayHasKey('2025-01', $monthly);
        $this->assertArrayHasKey('2027-12', $monthly);
    }

    public function test_annual_returns_three_years(): void
    {
        Carbon::setTestNow('2026-09-15 10:00:00');

        $annual = ActivityPeriodOptions::annual();

        $this->assertSame([
            '2025' => 'Tahunan 2025',
            '2026' => 'Tahunan 2026',
            '2027' => 'Tahunan 2027',
        ], $annual);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
