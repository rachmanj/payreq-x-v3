<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\SapBusinessPartner;
use App\Services\SapVendorPaymentBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SapVendorPaymentBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected Account $account;

    protected SapBusinessPartner $partner;

    /**
     * @var array<string, mixed>
     */
    protected array $invoice;

    /**
     * @var array<string, mixed>
     */
    protected array $apInvoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->account = Account::query()->create([
            'account_number' => '110101',
            'account_name' => 'Bank BCA HO',
            'type' => 'bank',
            'sap_account' => '11010101',
            'is_active' => true,
            'is_hidden' => false,
        ]);

        $this->partner = SapBusinessPartner::query()->create([
            'code' => 'VSUP01',
            'name' => 'PT Vendor Satu',
            'type' => SapBusinessPartner::TYPE_SUPPLIER,
            'active' => true,
        ]);

        $this->invoice = [
            'invoice_number' => 'INV-001',
            'amount' => 1500000,
            'payment_date' => '2026-08-20',
            'remarks' => 'Paid via transfer',
        ];

        $this->apInvoice = [
            'DocEntry' => 555,
            'DocNum' => 9001,
            'CardCode' => 'VSUP01',
            'DocumentStatus' => 'bost_Open',
            'Cancelled' => 'N',
            'NumAtCard' => 'INV-001',
            'DocTotal' => 1500000,
        ];
    }

    public function test_build_transfer_payload_maps_vendor_payment_fields(): void
    {
        $builder = $this->makeBuilder(SapVendorPaymentBuilder::MEANS_TRANSFER);

        $this->assertSame([], $builder->validate(requirePaymentAccount: true));

        $payload = $builder->build();

        $this->assertSame('VSUP01', $payload['CardCode']);
        $this->assertSame('rSupplier', $payload['DocType']);
        $this->assertSame('2026-08-20', $payload['DocDate']);
        $this->assertSame('11010101', $payload['TransferAccount']);
        $this->assertSame(1500000.0, $payload['TransferSum']);
        $this->assertSame('2026-08-20', $payload['TransferDate']);
        $this->assertArrayNotHasKey('CashAccount', $payload);
        $this->assertSame([
            [
                'DocEntry' => 555,
                'InvoiceType' => 'it_PurchaseInvoice',
                'SumApplied' => 1500000.0,
            ],
        ], $payload['PaymentInvoices']);
        $this->assertArrayNotHasKey('WithholdingTaxDataCollection', $payload['PaymentInvoices'][0]);
        $this->assertSame('Payment for Invoice INV-001', $payload['JournalRemarks']);
        $this->assertArrayNotHasKey('Comments', $payload);
        $this->assertSame('John Preparer', $payload['U_MIS_Signature1']);
        $this->assertSame('Jane Approver', $payload['U_MIS_Signature2']);
    }

    public function test_build_cash_payload_uses_cash_account_and_sum(): void
    {
        $builder = $this->makeBuilder(SapVendorPaymentBuilder::MEANS_CASH);

        $payload = $builder->build();

        $this->assertSame('11010101', $payload['CashAccount']);
        $this->assertSame(1500000.0, $payload['CashSum']);
        $this->assertArrayNotHasKey('TransferAccount', $payload);
        $this->assertArrayNotHasKey('TransferSum', $payload);
    }

    public function test_validate_rejects_cancelled_ap_invoice(): void
    {
        $this->apInvoice['Cancelled'] = 'Y';
        $builder = $this->makeBuilder();

        $this->assertContains(
            'Linked SAP AP Invoice is cancelled and cannot be paid.',
            $builder->validate()
        );
    }

    public function test_validate_rejects_card_code_mismatch(): void
    {
        $this->apInvoice['CardCode'] = 'VOTHER';
        $builder = $this->makeBuilder();

        $this->assertContains(
            'SAP AP Invoice belongs to vendor VOTHER, expected VSUP01.',
            $builder->validate()
        );
    }

    public function test_validate_rejects_missing_sap_account(): void
    {
        $this->account->update(['sap_account' => null]);
        $builder = $this->makeBuilder();

        $errors = $builder->validate(requirePaymentAccount: true);

        $this->assertNotEmpty($errors);
        $this->assertTrue(
            collect($errors)->contains(fn (string $error) => str_contains($error, 'sap_account'))
        );
    }

    public function test_preview_data_flags_amount_mismatch(): void
    {
        $this->apInvoice['DocTotal'] = 2000000;
        $builder = $this->makeBuilder();

        $preview = $builder->getPreviewData();

        $this->assertTrue($preview['amount_mismatch']);
        $this->assertSame(9001, $preview['ap_invoice']['doc_num']);
        $this->assertSame(555, $preview['ap_invoice']['doc_entry']);
        $this->assertSame(1500000.0, $preview['invoice']['amount']);
    }

    public function test_validate_rejects_payment_above_remaining_balance(): void
    {
        $this->apInvoice['DocTotal'] = 1500000;
        $this->apInvoice['PaidToDate'] = 500000;
        $builder = new SapVendorPaymentBuilder(
            $this->invoice,
            $this->apInvoice,
            $this->partner->fresh(),
            $this->account->fresh(),
            SapVendorPaymentBuilder::MEANS_TRANSFER,
            $this->invoice['payment_date'],
            1200000,
            'John Preparer',
            'Jane Approver',
        );

        $errors = $builder->validate();

        $this->assertNotEmpty($errors);
        $this->assertTrue(
            collect($errors)->contains(fn (string $error) => str_contains($error, 'remaining SAP balance'))
        );
    }

    public function test_preview_data_exposes_remaining_balance_and_partial_flags(): void
    {
        $this->apInvoice['DocTotal'] = 1500000;
        $this->apInvoice['PaidToDate'] = 500000;
        $builder = new SapVendorPaymentBuilder(
            $this->invoice,
            $this->apInvoice,
            $this->partner->fresh(),
            $this->account->fresh(),
            SapVendorPaymentBuilder::MEANS_TRANSFER,
            $this->invoice['payment_date'],
            400000,
            'John Preparer',
            'Jane Approver',
        );

        $preview = $builder->getPreviewData();

        $this->assertSame(500000.0, $preview['ap_invoice']['paid_to_date']);
        $this->assertSame(1000000.0, $preview['ap_invoice']['remaining_balance']);
        $this->assertTrue($preview['is_partial']);
        $this->assertFalse($preview['fully_paid_after']);
    }

    public function test_open_withholding_tax_sums_only_open_entries(): void
    {
        $result = SapVendorPaymentBuilder::openWithholdingTax([
            'WithholdingTaxDataCollection' => [
                ['WTCode' => '1019', 'WTAmount' => 33000, 'Status' => 'bost_Open'],
                ['WTCode' => '1020', 'WTAmount' => 10000, 'Status' => 'bost_Closed'],
                ['WTCode' => '1021', 'WTAmount' => 5000],
            ],
        ]);

        $this->assertSame(38000.0, $result['total']);
        $this->assertSame([
            ['WTCode' => '1019', 'WTAmount' => 33000.0],
            ['WTCode' => '1021', 'WTAmount' => 5000.0],
        ], $result['entries']);
    }

    public function test_open_withholding_tax_returns_empty_when_collection_missing(): void
    {
        $result = SapVendorPaymentBuilder::openWithholdingTax([]);

        $this->assertSame(0.0, $result['total']);
        $this->assertSame([], $result['entries']);
    }

    public function test_build_with_open_withholding_tax_applies_gross_sum_and_wtax_collection(): void
    {
        $this->apInvoice['DocTotal'] = 1831500;
        $this->apInvoice['WithholdingTaxDataCollection'] = [
            ['WTCode' => '1019', 'WTAmount' => 33000, 'Status' => 'bost_Open'],
        ];

        $builder = new SapVendorPaymentBuilder(
            $this->invoice,
            $this->apInvoice,
            $this->partner->fresh(),
            $this->account->fresh(),
            SapVendorPaymentBuilder::MEANS_TRANSFER,
            $this->invoice['payment_date'],
            1798500,
            'John Preparer',
            'Jane Approver',
        );

        $payload = $builder->build();

        $this->assertSame(1798500.0, $payload['TransferSum']);
        $this->assertSame(1831500.0, $payload['PaymentInvoices'][0]['SumApplied']);
        $this->assertSame([
            ['WTCode' => '1019', 'WTAmount' => 33000.0],
        ], $payload['PaymentInvoices'][0]['WithholdingTaxDataCollection']);
    }

    public function test_validate_rejects_partial_net_payment_when_withholding_tax_is_open(): void
    {
        $this->apInvoice['DocTotal'] = 1831500;
        $this->apInvoice['WithholdingTaxDataCollection'] = [
            ['WTCode' => '1019', 'WTAmount' => 33000, 'Status' => 'bost_Open'],
        ];

        $builder = new SapVendorPaymentBuilder(
            $this->invoice,
            $this->apInvoice,
            $this->partner->fresh(),
            $this->account->fresh(),
            SapVendorPaymentBuilder::MEANS_TRANSFER,
            $this->invoice['payment_date'],
            1000000,
            'John Preparer',
            'Jane Approver',
        );

        $errors = $builder->validate();

        $this->assertNotEmpty($errors);
        $this->assertTrue(
            collect($errors)->contains(fn (string $error) => str_contains($error, 'PPh23'))
        );
    }

    public function test_preview_data_includes_withholding_and_gross_applied(): void
    {
        $this->apInvoice['DocTotal'] = 1831500;
        $this->apInvoice['WithholdingTaxDataCollection'] = [
            ['WTCode' => '1019', 'WTAmount' => 33000, 'Status' => 'bost_Open'],
        ];

        $builder = new SapVendorPaymentBuilder(
            $this->invoice,
            $this->apInvoice,
            $this->partner->fresh(),
            $this->account->fresh(),
            SapVendorPaymentBuilder::MEANS_TRANSFER,
            $this->invoice['payment_date'],
            1798500,
            'John Preparer',
            'Jane Approver',
        );

        $preview = $builder->getPreviewData();

        $this->assertSame(33000.0, $preview['withholding']['total']);
        $this->assertSame('1019', $preview['withholding']['entries'][0]['WTCode']);
        $this->assertSame(1798500.0, $preview['net_amount']);
        $this->assertSame(1831500.0, $preview['gross_applied']);
        $this->assertTrue($preview['fully_paid_after']);
        $this->assertFalse($preview['is_partial']);
    }

    protected function makeBuilder(string $paymentMeans = SapVendorPaymentBuilder::MEANS_TRANSFER): SapVendorPaymentBuilder
    {
        return new SapVendorPaymentBuilder(
            $this->invoice,
            $this->apInvoice,
            $this->partner->fresh(),
            $this->account->fresh(),
            $paymentMeans,
            $this->invoice['payment_date'],
            (float) $this->invoice['amount'],
            'John Preparer',
            'Jane Approver',
        );
    }
}
