<?php

namespace Tests\Unit;

use App\Support\PettyCashBalanceVariance;
use PHPUnit\Framework\TestCase;

class PettyCashBalanceVarianceTest extends TestCase
{
    public function test_variance_level_thresholds(): void
    {
        $this->assertSame('green', PettyCashBalanceVariance::level(500));
        $this->assertSame('green', PettyCashBalanceVariance::level(-1000));
        $this->assertSame('yellow', PettyCashBalanceVariance::level(1001));
        $this->assertSame('yellow', PettyCashBalanceVariance::level(-500_000));
        $this->assertSame('red', PettyCashBalanceVariance::level(1_000_001));
        $this->assertNull(PettyCashBalanceVariance::level(null));
    }

    public function test_bootstrap_text_class_mapping(): void
    {
        $this->assertSame('text-success', PettyCashBalanceVariance::bootstrapTextClass('green'));
        $this->assertSame('text-warning', PettyCashBalanceVariance::bootstrapTextClass('yellow'));
        $this->assertSame('text-danger', PettyCashBalanceVariance::bootstrapTextClass('red'));
        $this->assertSame('', PettyCashBalanceVariance::bootstrapTextClass(null));
    }
}
