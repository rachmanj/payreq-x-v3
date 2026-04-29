<?php

namespace Tests\Unit;

use App\Support\RealizationDetailOdometerMonotonicityValidator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RealizationDetailOdometerMonotonicityValidatorTest extends TestCase
{
    #[Test]
    public function it_allows_earlier_lower_hm_before_a_later_rung(): void
    {
        $buckets = [
            '2026-04-25' => [9000],
            '2026-05-01' => [10000],
        ];

        $this->assertFalse(RealizationDetailOdometerMonotonicityValidator::breaksCrossDayMonotonicity($buckets));
    }

    #[Test]
    public function it_rejects_hm_dropping_after_a_prior_day_already_recorded_higher(): void
    {
        $buckets = [
            '2026-05-01' => [10000],
            '2026-05-02' => [9000],
        ];

        $this->assertTrue(RealizationDetailOdometerMonotonicityValidator::breaksCrossDayMonotonicity($buckets));
    }

    #[Test]
    public function it_rejects_earlier_hm_above_a_later_days_known_reading(): void
    {
        $buckets = [
            '2026-04-25' => [11000],
            '2026-05-01' => [10000],
        ];

        $this->assertTrue(RealizationDetailOdometerMonotonicityValidator::breaksCrossDayMonotonicity($buckets));
    }

    #[Test]
    public function it_allows_increasing_hm_after_prior_day(): void
    {
        $buckets = [
            '2026-05-01' => [10000],
            '2026-05-02' => [11000],
        ];

        $this->assertFalse(RealizationDetailOdometerMonotonicityValidator::breaksCrossDayMonotonicity($buckets));
    }

    #[Test]
    public function it_allows_equal_hm_on_the_same_calendar_day(): void
    {
        $buckets = [
            '2026-05-02' => [10000, 10000],
        ];

        $this->assertFalse(RealizationDetailOdometerMonotonicityValidator::breaksCrossDayMonotonicity($buckets));
    }

    #[Test]
    public function empty_buckets_do_not_break(): void
    {
        $this->assertFalse(RealizationDetailOdometerMonotonicityValidator::breaksCrossDayMonotonicity([]));
    }
}
