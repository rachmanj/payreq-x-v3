<?php

namespace Tests\Unit;

use App\Models\Creditor;
use App\Models\Installment;
use App\Models\Loan;
use App\Models\SapBusinessPartner;
use App\Models\User;
use App\Services\SapInstallmentApInvoiceBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SapInstallmentApInvoiceBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create(['id' => 1]);
    }

    public function test_build_with_adm_produces_three_lines_including_administration_account(): void
    {
        $installment = $this->createInstallment([
            'angsuran_ke' => 1,
            'bilyet_amount' => 539440000,
            'principal_amount' => 538440000,
            'interest_amount' => 500000,
            'adm_amount' => 500000,
        ], [
            'ref_vendor_label' => 'UT',
        ]);

        $payload = (new SapInstallmentApInvoiceBuilder($installment))->build();
        $lines = $payload['DocumentLines'];

        $this->assertCount(3, $lines);
        $admLine = $lines[1];
        $this->assertSame('71201003', $admLine['AccountCode']);
        $this->assertSame('Adm Expense', $admLine['ItemDescription']);
        $this->assertSame(500000.0, $admLine['LineTotal']);
    }

    public function test_build_first_installment_with_adm_only_and_zero_interest_has_two_lines(): void
    {
        $installment = $this->createInstallment([
            'angsuran_ke' => 1,
            'bilyet_amount' => 538940000,
            'principal_amount' => 538440000,
            'interest_amount' => 0,
            'adm_amount' => 500000,
        ]);

        $payload = (new SapInstallmentApInvoiceBuilder($installment))->build();
        $lines = $payload['DocumentLines'];

        $this->assertCount(2, $lines);
        $this->assertSame('71201003', $lines[1]['AccountCode']);
        $this->assertSame(500000.0, $lines[1]['LineTotal']);
    }

    public function test_build_omits_interest_line_when_zero(): void
    {
        $installment = $this->createInstallment([
            'bilyet_amount' => 1000000,
            'principal_amount' => 1000000,
            'interest_amount' => 0,
        ]);

        $payload = (new SapInstallmentApInvoiceBuilder($installment))->build();

        $this->assertCount(1, $payload['DocumentLines']);
        $this->assertSame('13101005', $payload['DocumentLines'][0]['AccountCode']);
    }

    public function test_validate_passes_when_principal_interest_and_adm_match_bilyet(): void
    {
        $installment = $this->createInstallment([
            'bilyet_amount' => 538940000,
            'principal_amount' => 538440000,
            'interest_amount' => 0,
            'adm_amount' => 500000,
        ]);

        $errors = (new SapInstallmentApInvoiceBuilder($installment))->validate();

        $this->assertSame([], $errors);
    }

    public function test_validate_fails_with_clear_message_when_totals_mismatch(): void
    {
        $installment = $this->createInstallment([
            'bilyet_amount' => 538940000,
            'principal_amount' => 538440000,
            'interest_amount' => 0,
            'adm_amount' => 400000,
        ]);

        $errors = (new SapInstallmentApInvoiceBuilder($installment))->validate();

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('pokok', $errors[0]);
        $this->assertStringContainsString('bunga', $errors[0]);
        $this->assertStringContainsString('adm', $errors[0]);
        $this->assertStringContainsString('nominal angsuran', $errors[0]);
    }

    public function test_build_includes_default_series_3834(): void
    {
        $installment = $this->createInstallment([
            'bilyet_amount' => 1000,
            'principal_amount' => 1000,
            'interest_amount' => 0,
        ], [
            'sap_series' => null,
            'ref_vendor_label' => null,
        ]);

        $payload = (new SapInstallmentApInvoiceBuilder($installment))->build();

        $this->assertSame(3834, $payload['Series']);
    }

    public function test_build_reference_includes_vendor_label(): void
    {
        $installment = $this->createInstallment([
            'angsuran_ke' => 1,
            'bilyet_amount' => 538940000,
            'principal_amount' => 538440000,
            'interest_amount' => 0,
            'adm_amount' => 500000,
        ], [
            'loan_code' => '22.10.26.04330',
            'tenor' => 3,
            'ref_vendor_label' => 'UT',
            'kode_unit' => null,
        ]);

        $reference = (new SapInstallmentApInvoiceBuilder($installment))->buildReference();

        $this->assertSame('1 of 3 CSUL UT (22.10.26.04330)', $reference);
    }

    public function test_regression_without_vendor_label_and_series(): void
    {
        $installment = $this->createInstallment([
            'angsuran_ke' => 2,
            'bilyet_amount' => 722959000,
            'principal_amount' => 715782117.46,
            'interest_amount' => 7176882.54,
        ], [
            'loan_code' => '22.10.26.03907',
            'tenor' => 3,
            'kode_unit' => 'TU',
            'ref_vendor_label' => null,
            'sap_series' => null,
        ]);

        $builder = new SapInstallmentApInvoiceBuilder($installment);

        $this->assertSame('2 of 3 CSUL TU (22.10.26.03907)', $builder->buildReference());
        $this->assertSame(3834, $builder->build()['Series']);
        $this->assertSame([], $builder->validate());
    }

    public function test_preview_includes_adm_and_component_total(): void
    {
        $installment = $this->createInstallment([
            'bilyet_amount' => 538940000,
            'principal_amount' => 538440000,
            'interest_amount' => 0,
            'adm_amount' => 500000,
        ]);

        $preview = (new SapInstallmentApInvoiceBuilder($installment))->getPreviewData();

        $this->assertSame(500000.0, $preview['adm_amount']);
        $this->assertSame(538940000.0, $preview['total']);
        $this->assertSame('71201003', $preview['adm_account']);
        $this->assertSame('B100', $preview['vat_group']);
        $this->assertSame('tNO', $preview['wt_liable']);
        foreach ($preview['lines'] as $line) {
            $this->assertSame('B100', $line['vat_group']);
            $this->assertSame('tNO', $line['wt_liable']);
        }
    }

    public function test_build_document_lines_use_vat_group_b100_and_wt_liable_no(): void
    {
        $installment = $this->createInstallment([
            'bilyet_amount' => 539440000,
            'principal_amount' => 538440000,
            'interest_amount' => 500000,
            'adm_amount' => 500000,
        ]);

        $lines = (new SapInstallmentApInvoiceBuilder($installment))->build()['DocumentLines'];

        $this->assertCount(3, $lines);
        foreach ($lines as $line) {
            $this->assertSame('B100', $line['VatGroup']);
            $this->assertSame('tNO', $line['WTLiable']);
        }
    }

    public function test_build_payload_uses_only_non_taxable_vat_group_b100(): void
    {
        $installment = $this->createInstallment([
            'bilyet_amount' => 539440000,
            'principal_amount' => 538440000,
            'interest_amount' => 500000,
            'adm_amount' => 500000,
        ]);

        $payload = (new SapInstallmentApInvoiceBuilder($installment))->build();
        $encoded = json_encode($payload, JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString('B111', $encoded);
        $this->assertStringNotContainsString('VatSum', $encoded);

        foreach ($payload['DocumentLines'] as $line) {
            $this->assertSame('B100', $line['VatGroup']);
        }

        $lineTotalSum = array_sum(array_column($payload['DocumentLines'], 'LineTotal'));
        $this->assertSame(539440000.0, $lineTotalSum);
    }

    /**
     * @param  array<string, mixed>  $installmentAttributes
     * @param  array<string, mixed>  $loanAttributes
     */
    protected function createInstallment(array $installmentAttributes = [], array $loanAttributes = []): Installment
    {
        $partner = SapBusinessPartner::query()->create([
            'code' => 'VCSULIDR01',
            'name' => 'PT. CSUL Finance',
            'type' => SapBusinessPartner::TYPE_SUPPLIER,
            'active' => true,
        ]);

        $creditor = Creditor::query()->create([
            'name' => 'PT. CSUL Finance',
            'nama_singkat' => 'CSUL',
            'sap_business_partner_id' => $partner->id,
        ]);

        $loan = Loan::query()->create(array_merge([
            'loan_code' => '22.10.26.04330',
            'creditor_id' => $creditor->id,
            'start_date' => '2026-09-01',
            'principal' => 1600000000,
            'tenor' => 3,
            'user_id' => 1,
            'akun_pokok_gl' => '13101005',
            'costing_code' => '40',
            'project_code' => '000H',
        ], $loanAttributes));

        return Installment::query()->create(array_merge([
            'loan_id' => $loan->id,
            'due_date' => '2026-09-14',
            'angsuran_ke' => 1,
            'payment_method' => 'bilyet',
            'created_by' => 1,
        ], $installmentAttributes));
    }
}
