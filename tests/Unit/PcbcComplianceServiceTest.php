<?php

namespace Tests\Unit;

use App\Models\Dokumen;
use App\Models\User;
use App\Services\PcbcComplianceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PcbcComplianceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

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

    #[Test]
    public function it_does_not_show_banner_when_only_current_week_is_missing(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-28 10:00:00', 'Asia/Makassar'));

        $user = User::factory()->create(['project' => '000H']);
        $this->seedPcbcUpload($user, '2026-07-20');
        $this->seedPcbcUpload($user, '2026-07-13');

        $status = (new PcbcComplianceService)->getStatus($user);

        $this->assertFalse($status['show_banner']);
        $this->assertSame('warning', $status['variant']);
    }

    #[Test]
    public function it_shows_banner_when_last_week_is_missing(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-28 10:00:00', 'Asia/Makassar'));

        $user = User::factory()->create(['project' => '000H']);
        $this->seedPcbcUpload($user, '2026-07-27');
        $this->seedPcbcUpload($user, '2026-07-13');

        $status = (new PcbcComplianceService)->getStatus($user);

        $this->assertTrue($status['show_banner']);
        $this->assertSame('warning', $status['variant']);
        $this->assertSame('You missed last week\'s PCBC', $status['title']);
    }

    private function seedPcbcUpload(User $user, string $dokumenDate): void
    {
        Dokumen::query()->create([
            'type' => 'pcbc',
            'project' => $user->project,
            'dokumen_date' => $dokumenDate,
            'validation_status' => Dokumen::VALIDATION_VALIDATED,
            'filename1' => 'pcbc_'.$dokumenDate.'.pdf',
            'created_by' => $user->id,
        ]);
    }
}
