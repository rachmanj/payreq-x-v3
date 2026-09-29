<?php

namespace Tests\Unit;

use App\Services\FakturPpnCalculationService;
use PHPUnit\Framework\TestCase;

class FakturPpnCalculationServiceTest extends TestCase
{
    private FakturPpnCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FakturPpnCalculationService;
    }

    public function test_resolves_eleven_percent_from_round_dpp(): void
    {
        $result = $this->service->calculateFromPpnAmount(1_100_000.0);

        $this->assertSame(11.0, $result['ppn_rate']);
        $this->assertSame(10_000_000.0, $result['dpp']);
        $this->assertSame(10_000_000.0, $result['dpp_calculated']);
        $this->assertSame('formula', $result['dpp_source']);
        $this->assertNull($result['review_note']);
    }

    public function test_resolves_twelve_percent_from_round_dpp(): void
    {
        $result = $this->service->calculateFromPpnAmount(600_000.0);

        $this->assertSame(12.0, $result['ppn_rate']);
        $this->assertSame(5_000_000.0, $result['dpp']);
        $this->assertSame('formula', $result['dpp_source']);
    }

    public function test_uses_tax_code_from_remarks_when_present(): void
    {
        $result = $this->service->calculateFromPpnAmount(120_000.0, null, 'AP invoice B112 barang mewah');

        $this->assertSame(12.0, $result['ppn_rate']);
        $this->assertSame(1_000_000.0, $result['dpp']);
        $this->assertSame('gl', $result['dpp_source']);
    }

    public function test_flags_ambiguous_eleven_and_twelve_percent_dpp_nilai_lain(): void
    {
        $result = $this->service->calculateFromPpnAmount(1_320_000.0);

        $this->assertNull($result['ppn_rate']);
        $this->assertNull($result['dpp_calculated']);
        $this->assertSame('belum_diperiksa', $result['validation_status']);
        $this->assertNotNull($result['review_note']);
    }

    public function test_flags_unknown_rate_when_no_integer_dpp_candidate(): void
    {
        $result = $this->service->calculateFromPpnAmount(1_234_567.89);

        $this->assertNull($result['ppn_rate']);
        $this->assertNull($result['dpp_calculated']);
        $this->assertNotNull($result['review_note']);
    }

    public function test_masa_pajak_from_date(): void
    {
        $this->assertSame('2026-08', $this->service->masaPajakFromDate('2026-08-15'));
        $this->assertNull($this->service->masaPajakFromDate(null));
    }
}
