<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Faktur;
use App\Models\PpnInputSync;
use App\Models\PpnSyncRun;
use App\Models\User;
use App\Services\FakturPpnCalculationService;
use App\Services\PpnInputVatSyncService;
use App\Services\SapService;
use App\Support\Sap\AoPpnin1Query;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PpnInputVatSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_ao_ppnin1_sql_uses_fp_date_filter_and_journal_keys(): void
    {
        $sql = AoPpnin1Query::sqlText();

        $this->assertSame('AO_PPNIN1', AoPpnin1Query::CODE);
        $this->assertStringContainsString(':startDate', $sql);
        $this->assertStringContainsString(':endDate', $sql);

        // Periode disaring dari tanggal posting jurnal (sederhana & terbukti diterima SAP).
        $this->assertStringContainsString('T0.RefDate >= :startDate', $sql);
        $this->assertStringContainsString('T0.RefDate <= :endDate', $sql);

        // Kunci identitas baris untuk upsert idempoten + kode jenis dokumen mentah.
        $this->assertStringContainsString('T0.TransId AS trans_id', $sql);
        $this->assertStringContainsString('T1.Line_ID AS line_id', $sql);
        $this->assertStringContainsString('T1.TransType AS trans_type', $sql);
        $this->assertStringContainsString("T1.Account = '11603001'", $sql);

        // WAJIB: gaya polos `alias.kolom`, DISTINCT, dan urutan tetap. Tanpa ORDER BY,
        // paginasi $skip bisa bergeser sehingga baris terlewat (kejadian nyata 29 Sep 2026).
        $this->assertStringContainsString('SELECT DISTINCT', $sql);
        $this->assertStringContainsString('FROM OJDT T0', $sql);
        $this->assertStringContainsString('INNER JOIN JDT1 T1', $sql);
        $this->assertStringContainsString('LEFT JOIN OPCH T2', $sql);
        $this->assertStringContainsString('ORDER BY T0.TransId, T1.Line_ID', $sql);

        // DILARANG: sudah terbukti ditolak parser SAP ("Invalid SQL syntax", 29 Sep 2026).
        // Penerjemahan TransType→nama jenis dokumen dilakukan di aplikasi (PpnInputVatSyncService).
        // Join PCH1 tidak dipakai karena menggandakan baris (fan-out invoice).
        $this->assertStringNotContainsString('CASE', $sql);
        $this->assertStringNotContainsString('MIN(', $sql);
        $this->assertStringNotContainsString('COALESCE', $sql);
        $this->assertStringNotContainsString('(SELECT', $sql);
        $this->assertStringNotContainsString('OUSR', $sql);
        $this->assertStringNotContainsString('PCH1', $sql);
        $this->assertStringNotContainsString('[', $sql);
    }

    public function test_date_range_for_lookback_uses_inclusive_window(): void
    {
        $service = new PpnInputVatSyncService(
            Mockery::mock(SapService::class),
            new FakturPpnCalculationService
        );

        $range = $service->dateRangeForLookback(60);

        $this->assertSame(now()->format('Y-m-d'), $range['endDate']);
        $this->assertSame(now()->subDays(60)->format('Y-m-d'), $range['startDate']);
    }

    public function test_upsert_is_idempotent_for_same_trans_and_line(): void
    {
        $sap = Mockery::mock(SapService::class);
        $sampleRow = $this->sampleSapRow();

        $sap->shouldReceive('fetchPpnInputVatLines')
            ->twice()
            ->andReturn([$sampleRow]);

        $user = User::factory()->create();

        $service = new PpnInputVatSyncService($sap, new FakturPpnCalculationService);

        $service->sync(30, $user->id);
        $service->sync(30, $user->id);

        $this->assertSame(1, PpnInputSync::query()->count());
        $this->assertSame(2, PpnSyncRun::query()->count());
        $this->assertSame(1, Faktur::query()->where('type', 'purchase')->count());
    }

    public function test_dispatch_creates_purchase_faktur_with_calculation_and_sap_keys(): void
    {
        $user = User::factory()->create();

        $service = new PpnInputVatSyncService(
            Mockery::mock(SapService::class),
            new FakturPpnCalculationService
        );

        $attrs = $service->mapSapRowToAttributes($this->sampleSapRow(), 'batch-test');
        $this->assertNotNull($attrs);

        $created = $service->dispatchNewRowsToFakturs([$attrs], $user->id);
        $this->assertSame(1, $created);

        $faktur = Faktur::query()->first();
        $this->assertSame('sap_auto', $faktur->sync_source);
        $this->assertSame(1001, $faktur->sap_trans_id);
        $this->assertSame(3, $faktur->sap_line_id);
        $this->assertSame(10_000_000.0, (float) $faktur->dpp);
        $this->assertSame(1_100_000.0, (float) $faktur->ppn);
        $this->assertSame(11.0, (float) $faktur->ppn_rate);

        $this->assertTrue(Customer::query()->where('code', 'V001')->exists());
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleSapRow(): array
    {
        return [
            'trans_id' => 1001,
            'line_id' => 3,
            'document_no' => '500123',
            'creation_date' => '2026-08-01',
            'posting_date' => '2026-08-02',
            'faktur_date' => '2026-08-05',
            'vendor_code' => 'V001',
            'vendor_name' => 'PT Supplier',
            'faktur_no' => '04002600241059362',
            'amount' => 1_100_000,
            'project_code' => 'PRJ01',
            'remark' => 'AP Invoice PPN',
            'sap_user' => 'ACC01',
            'invoice_no' => 'INV-88',
            'invoice_remarks' => 'B111',
            'doc_type' => 'AP Invoice',
        ];
    }
}
