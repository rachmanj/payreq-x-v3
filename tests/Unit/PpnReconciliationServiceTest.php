<?php

namespace Tests\Unit;

use App\Models\CoretaxInputVat;
use App\Models\Customer;
use App\Models\Faktur;
use App\Models\User;
use App\Services\PpnReconciliationService;
use App\Services\SapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PpnReconciliationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_build_missing_faktur_exposure_only_vat_with_empty_fpnum(): void
    {
        $service = new PpnReconciliationService(Mockery::mock(SapService::class));

        $exposure = $service->buildMissingFakturExposure([
            ['DocNum' => 1, 'VatSum' => 0, 'U_MIS_FPNum' => '', 'CardCode' => 'V001'],
            ['DocNum' => 2, 'VatSum' => 100, 'U_MIS_FPNum' => '04002600241059362', 'CardCode' => 'V002'],
            [
                'DocNum' => 3,
                'DocDate' => '2026-08-10',
                'VatSum' => 41_206_622,
                'DocTotal' => 500_000_000,
                'U_MIS_FPNum' => '',
                'CardCode' => 'V003',
                'CardName' => 'Supplier Tanpa FP',
            ],
            ['DocNum' => 4, 'VatSum' => 50_000, 'U_MIS_FPNum' => null, 'CardCode' => 'V004'],
        ]);

        $this->assertCount(2, $exposure);
        $this->assertSame('3', $exposure[0]['doc_num']);
        $this->assertSame(41_206_622.0, $exposure[0]['vat_sum']);
    }

    public function test_reconcile_aggregates_sap_and_app_with_mocked_sap_client(): void
    {
        $user = User::factory()->create();
        $customer = Customer::query()->create([
            'code' => 'CUST01',
            'name' => 'Customer',
            'type' => 'vendor',
        ]);

        Faktur::query()->create([
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-1',
            'invoice_date' => '2026-08-01',
            'type' => 'sales',
            'masa_pajak' => '2026-08',
            'dpp' => 10_000_000,
            'ppn' => 1_100_000,
            'created_by' => $user->id,
        ]);

        Faktur::query()->create([
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-2',
            'invoice_date' => '2026-08-05',
            'type' => 'purchase',
            'masa_pajak' => '2026-08',
            'dpp' => 5_000_000,
            'ppn' => 550_000,
            'created_by' => $user->id,
        ]);

        $sap = Mockery::mock(SapService::class);
        $sap->shouldReceive('fetchPurchaseInvoicesForDocDateRange')
            ->once()
            ->with('2026-08-01', '2026-08-31')
            ->andReturn([
                ['DocNum' => 10, 'VatSum' => 600_000, 'U_MIS_FPNum' => 'FP1'],
                ['DocNum' => 11, 'VatSum' => 50_000, 'U_MIS_FPNum' => ''],
            ]);
        $sap->shouldReceive('fetchArInvoicesForDocDateRange')
            ->once()
            ->with('2026-08-01', '2026-08-31')
            ->andReturn([
                ['DocNum' => 20, 'VatSum' => 1_200_000],
            ]);

        $service = new PpnReconciliationService($sap);
        $result = $service->reconcile('2026-08');

        $this->assertSame(1_200_000.0, $result['sap']['pk_total']);
        $this->assertSame(650_000.0, $result['sap']['pm_total']);
        $this->assertSame(1_100_000.0, $result['app']['pk_total']);
        $this->assertSame(550_000.0, $result['app']['pm_total']);
        $this->assertSame(100_000.0, $result['diff_detail']['pk_sap_minus_app']);
        $this->assertSame(100_000.0, $result['diff_detail']['pm_sap_minus_app']);
        $this->assertSame(200_000.0, $result['totals']['diff_sap_app']);
        $this->assertCount(1, $result['missing_faktur_exposure']);
    }

    public function test_three_way_reconciliation_classifies_findings(): void
    {
        $user = User::factory()->create();
        $customer = Customer::query()->create([
            'code' => 'V1',
            'name' => 'Supplier',
            'type' => 'vendor',
        ]);

        Faktur::query()->create([
            'customer_id' => $customer->id,
            'invoice_no' => 'A1',
            'invoice_date' => '2026-09-10',
            'type' => 'purchase',
            'masa_pajak' => '2026-09',
            'faktur_no' => '04002600000000001',
            'dpp' => 1_000_000,
            'ppn' => 100_000,
            'created_by' => $user->id,
        ]);

        Faktur::query()->create([
            'customer_id' => $customer->id,
            'invoice_no' => 'A2',
            'invoice_date' => '2026-09-11',
            'type' => 'purchase',
            'masa_pajak' => '2026-09',
            'faktur_no' => '04002600000000002',
            'dpp' => 2_000_000,
            'ppn' => 200_000,
            'created_by' => $user->id,
        ]);

        CoretaxInputVat::query()->create([
            'masa_pajak' => '2026-09',
            'import_batch' => 'T1',
            'npwp' => '1',
            'supplier_name' => 'S1',
            'faktur_no' => '04002600000000001',
            'dpp' => 1_000_000,
            'ppn' => 100_000,
            'status_faktur' => 'APPROVED',
            'imported_by' => $user->id,
        ]);

        CoretaxInputVat::query()->create([
            'masa_pajak' => '2026-09',
            'import_batch' => 'T1',
            'npwp' => '2',
            'supplier_name' => 'S3',
            'faktur_no' => '04002600000000003',
            'dpp' => 500_000,
            'ppn' => 50_000,
            'status_faktur' => 'CREDITED',
            'imported_by' => $user->id,
        ]);

        $sap = Mockery::mock(SapService::class);
        $sap->shouldReceive('fetchPurchaseInvoicesForDocDateRange')
            ->andReturn([
                ['DocNum' => 1, 'VatSum' => 100_000, 'U_MIS_FPNum' => '04002600000000001', 'CardName' => 'S1'],
                ['DocNum' => 2, 'VatSum' => 200_000, 'U_MIS_FPNum' => '04002600000000002', 'CardName' => 'S2'],
            ]);
        $sap->shouldReceive('fetchArInvoicesForDocDateRange')->andReturn([]);

        $service = new PpnReconciliationService($sap);
        $result = $service->reconcile('2026-09');

        $three = $result['three_way'];
        $this->assertSame(1, $three['counts']['matched_three_way']);
        $this->assertSame(1, $three['counts']['sap_app_not_coretax']);
        $this->assertSame(1, $three['counts']['coretax_only']);
        $this->assertSame('04002600000000001', $three['matched_three_way'][0]['faktur_no']);
        $this->assertNotNull($result['totals']['diff_coretax_app']);
    }
}
