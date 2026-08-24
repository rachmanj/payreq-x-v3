<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\SapBusinessPartner;
use App\Models\UtilityApInvoice;
use App\Models\UtilityBill;
use App\Models\UtilityCustomer;
use App\Models\UtilityVendor;
use App\Services\SapUtilityApInvoiceBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SapUtilityApInvoiceBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected Account $account;

    protected SapBusinessPartner $partner;

    protected UtilityVendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.sap.ap_invoice.utility.default_tax_code' => 'B100']);

        $this->account = Account::query()->create([
            'account_number' => '61208001',
            'account_name' => 'Electricity',
            'type' => 'expense',
        ]);

        $this->partner = SapBusinessPartner::query()->create([
            'code' => 'VPLNPIDR01',
            'name' => 'PLN (Persero)',
            'type' => SapBusinessPartner::TYPE_SUPPLIER,
            'active' => true,
        ]);

        $this->vendor = UtilityVendor::query()->where('jenis_utilitas', 'pln')->firstOrFail();
        $this->vendor->update(['sap_business_partner_id' => $this->partner->id]);
        $this->vendor->load('sapBusinessPartner');
    }

    public function test_build_payload_maps_service_lines_and_dimensions(): void
    {
        $billA = $this->createBill([
            'id_pelanggan' => '90060',
            'nama' => 'HO Office',
            'lokasi' => 'HO Office',
            'project' => '000H',
            'department' => '20',
            'periode' => '2026-08',
            'jumlah_tagihan' => 14007142,
        ]);
        $billB = $this->createBill([
            'id_pelanggan' => '52763',
            'nama' => 'APS Office',
            'lokasi' => 'APS Office',
            'project' => 'APS',
            'department' => '140',
            'periode' => '2026-08',
            'jumlah_tagihan' => 8106553,
        ]);

        $builder = new SapUtilityApInvoiceBuilder(
            collect([$billA->fresh('customer.account'), $billB->fresh('customer.account')]),
            $this->vendor->fresh('sapBusinessPartner')
        );

        $this->assertSame([], $builder->validate());

        $payload = $builder->build();

        $this->assertSame('VPLNPIDR01', $payload['CardCode']);
        $this->assertSame('dDocument_Service', $payload['DocType']);
        $this->assertSame('PLN 8/26', $payload['NumAtCard']);
        $this->assertSame('IDR', $payload['DocCurrency']);
        $this->assertSame('30', $payload['U_MIS_CCDepartment']);
        $this->assertCount(2, $payload['DocumentLines']);
        $this->assertSame('61208001', $payload['DocumentLines'][0]['AccountCode']);
        $this->assertSame('PLN 000H Aug-2026 90060', $payload['DocumentLines'][0]['ItemDescription']);
        $this->assertSame(1, $payload['DocumentLines'][0]['U_MIS_QtyService']);
        $this->assertSame(14007142.0, $payload['DocumentLines'][0]['U_MIS_PriceService']);
        $this->assertSame('B100', $payload['DocumentLines'][0]['VatGroup']);
        $this->assertSame('tNO', $payload['DocumentLines'][0]['WTLiable']);
        $this->assertSame('000H', $payload['DocumentLines'][0]['ProjectCode']);
        $this->assertSame('20', $payload['DocumentLines'][0]['CostingCode']);
        $this->assertSame(14007142.0, $payload['DocumentLines'][0]['LineTotal']);
        $this->assertSame('APS', $payload['DocumentLines'][1]['ProjectCode']);
        $this->assertSame('140', $payload['DocumentLines'][1]['CostingCode']);
        $this->assertSame('PLN APS Aug-2026 52763', $payload['DocumentLines'][1]['ItemDescription']);
    }

    public function test_reference_number_appends_suffix_when_already_used(): void
    {
        UtilityApInvoice::factory()->create([
            'jenis_utilitas' => 'pln',
            'sap_business_partner_id' => $this->partner->id,
            'num_at_card' => 'PLN 8/26',
        ]);

        $bill = $this->createBill(['periode' => '2026-08']);
        $builder = new SapUtilityApInvoiceBuilder(
            collect([$bill->fresh('customer.account')]),
            $this->vendor->fresh('sapBusinessPartner')
        );

        $this->assertSame('PLN 8/26-2', $builder->buildReferenceNumber());
        $this->assertSame('PLN 8/26-2', $builder->build()['NumAtCard']);
    }

    public function test_validate_rejects_mixed_jenis(): void
    {
        $plnBill = $this->createBill(['jenis_utilitas' => 'pln']);
        $pdamBill = $this->createBill(['jenis_utilitas' => 'pdam', 'id_pelanggan' => 'PDAM-1']);

        $builder = new SapUtilityApInvoiceBuilder(
            collect([$plnBill->fresh('customer.account'), $pdamBill->fresh('customer.account')]),
            $this->vendor->fresh('sapBusinessPartner')
        );

        $this->assertContains(
            'Tagihan harus dari satu jenis utilitas yang sama.',
            $builder->validate()
        );
    }

    public function test_validate_rejects_paid_bills(): void
    {
        $bill = $this->createBill(['tanggal_bayar' => now()->toDateString()]);
        $builder = new SapUtilityApInvoiceBuilder(
            collect([$bill->fresh('customer.account')]),
            $this->vendor->fresh('sapBusinessPartner')
        );

        $errors = $builder->validate();
        $this->assertNotEmpty($errors);
        $this->assertTrue(
            collect($errors)->contains(fn (string $error) => str_contains($error, 'belum lunas'))
        );
    }

    public function test_validate_rejects_prepaid_bills(): void
    {
        $bill = $this->createBill(['tipe' => 'prepaid']);
        $builder = new SapUtilityApInvoiceBuilder(
            collect([$bill->fresh('customer.account')]),
            $this->vendor->fresh('sapBusinessPartner')
        );

        $errors = $builder->validate();
        $this->assertTrue(
            collect($errors)->contains(fn (string $error) => str_contains($error, 'pascabayar'))
        );
    }

    public function test_validate_rejects_missing_department_and_account(): void
    {
        $bill = $this->createBill(['department' => null, 'account_id' => null]);
        $builder = new SapUtilityApInvoiceBuilder(
            collect([$bill->fresh('customer.account')]),
            $this->vendor->fresh('sapBusinessPartner')
        );

        $errors = implode(' ', $builder->validate());
        $this->assertStringContainsString('belum punya akun COA', $errors);
        $this->assertStringContainsString('belum punya department/cost center', $errors);
    }

    public function test_validate_rejects_missing_and_inactive_vendor(): void
    {
        $bill = $this->createBill();
        $this->vendor->update(['sap_business_partner_id' => null]);

        $builder = new SapUtilityApInvoiceBuilder(
            collect([$bill->fresh('customer.account')]),
            $this->vendor->fresh('sapBusinessPartner')
        );
        $this->assertTrue(
            collect($builder->validate())->contains(fn (string $error) => str_contains($error, 'belum di-mapping'))
        );

        $this->partner->update(['active' => false]);
        $this->vendor->update(['sap_business_partner_id' => $this->partner->id]);
        $builder = new SapUtilityApInvoiceBuilder(
            collect([$bill->fresh('customer.account')]),
            $this->vendor->fresh('sapBusinessPartner')
        );
        $this->assertTrue(
            collect($builder->validate())->contains(fn (string $error) => str_contains($error, 'tidak aktif'))
        );
    }

    public function test_preview_data_includes_header_and_lines(): void
    {
        $bill = $this->createBill(['periode' => '2026-08', 'jumlah_tagihan' => 1000]);
        $builder = new SapUtilityApInvoiceBuilder(
            collect([$bill->fresh('customer.account')]),
            $this->vendor->fresh('sapBusinessPartner')
        );

        $preview = $builder->getPreviewData();

        $this->assertSame('VPLNPIDR01', $preview['vendor']['code']);
        $this->assertSame('B100', $preview['tax_code']);
        $this->assertSame('PLN 8/26', $preview['num_at_card']);
        $this->assertSame(1000.0, $preview['total']);
        $this->assertCount(1, $preview['lines']);
        $this->assertSame('61208001', $preview['lines'][0]['account_number']);
        $this->assertSame('PLN 000H Aug-2026 '.$preview['lines'][0]['id_pelanggan'], $preview['lines'][0]['line_memo']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createBill(array $overrides = []): UtilityBill
    {
        $customer = UtilityCustomer::query()->create([
            'jenis_utilitas' => $overrides['jenis_utilitas'] ?? 'pln',
            'tipe' => $overrides['tipe'] ?? 'postpaid',
            'id_pelanggan' => $overrides['id_pelanggan'] ?? fake()->unique()->numerify('#####'),
            'nama' => $overrides['nama'] ?? 'HO Office',
            'lokasi' => $overrides['lokasi'] ?? 'HO Office',
            'project' => $overrides['project'] ?? '000H',
            'department' => array_key_exists('department', $overrides) ? $overrides['department'] : '20',
            'account_id' => array_key_exists('account_id', $overrides) ? $overrides['account_id'] : $this->account->id,
            'is_active' => true,
        ]);

        return UtilityBill::query()->create([
            'utility_customer_id' => $customer->id,
            'periode' => $overrides['periode'] ?? '2026-08',
            'jumlah_tagihan' => $overrides['jumlah_tagihan'] ?? 1000,
            'tanggal_jatuh_tempo' => $overrides['tanggal_jatuh_tempo'] ?? '2026-08-31',
            'tanggal_bayar' => $overrides['tanggal_bayar'] ?? null,
        ]);
    }
}
