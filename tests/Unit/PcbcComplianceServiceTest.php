<?php

namespace Tests\Unit;

use App\Services\PcbcComplianceService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PcbcComplianceServiceTest extends TestCase
{
    #[Test]
    public function it_marks_configured_project_codes_as_exempt(): void
    {
        $service = new PcbcComplianceService;

        $this->assertTrue($service->isExemptProject('APS'));
        $this->assertTrue($service->isExemptProject('026C'));
        $this->assertTrue($service->isExemptProject('023C'));
        $this->assertFalse($service->isExemptProject('OTHER'));
    }

    #[Test]
    public function it_treats_empty_project_as_exempt_from_rules(): void
    {
        $service = new PcbcComplianceService;

        $this->assertTrue($service->isExemptProject(null));
        $this->assertTrue($service->isExemptProject(''));
    }
}
