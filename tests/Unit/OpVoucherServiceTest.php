<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Models\SapSubmissionLog;
use App\Models\User;
use App\Services\OpVoucherService;
use App\Services\SapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpVoucherServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_builds_complete_voucher_for_bpjs_payment(): void
    {
        $submitter = User::factory()->create(['name' => 'Budi Kasir']);

        $log = SapSubmissionLog::query()->create([
            'bpjs_ap_invoice_id' => 1,
            'document_type' => SapSubmissionLog::DOCUMENT_TYPE_BPJS_AP_INVOICE_PAYMENT,
            'status' => 'success',
            'action' => 'submission',
            'sap_doc_entry' => 501,
            'sap_doc_num' => '88001',
            'amount' => 2000000,
            'submitted_by' => $submitter->id,
            'user_id' => $submitter->id,
        ]);

        Project::query()->create([
            'code' => '000H',
            'sap_code' => '000H',
            'name' => 'HO Balikpapan',
            'is_active' => true,
            'is_selectable' => true,
        ]);

        $paymentHeader = [
            'DocEntry' => 501,
            'DocNum' => 88001,
            'DocDate' => '2026-09-07',
            'CardName' => 'BPJS KESEHATAN',
            'CashSum' => 0,
            'TransferSum' => 2000000,
            'TransferAccount' => '11102001',
            'DocCurrency' => 'IDR',
            'JournalRemarks' => 'Payment for Invoice BPJS-2026-09',
            'ProjectCode' => '000H',
        ];

        $paymentLines = [
            [
                'account' => '21101001',
                'description' => 'Utang BPJS',
                'debit' => 2000000,
                'credit' => 0,
            ],
            [
                'account' => '11102001',
                'description' => 'Bank Mandiri',
                'debit' => 0,
                'credit' => 2000000,
            ],
        ];

        $this->mock(SapService::class, function ($mock) use ($paymentHeader, $paymentLines): void {
            $mock->shouldReceive('getVendorPaymentByDocEntry')
                ->once()
                ->with(501)
                ->andReturn($paymentHeader);
            $mock->shouldReceive('getPaymentHeaderFromSql')
                ->once()
                ->with(501)
                ->andReturn(['prj_code' => '000H', 'doc_num' => '88001']);
            $mock->shouldReceive('getPaymentGlLines')
                ->once()
                ->with(501)
                ->andReturn($paymentLines);
            $mock->shouldNotReceive('getProjects');
        });

        $voucher = app(OpVoucherService::class)->build($log);

        $this->assertSame('BPJS KESEHATAN', $voucher['header']['payment_for']);
        $this->assertSame('88001', $voucher['header']['voucher_no']);
        $this->assertSame('07-September-2026', $voucher['header']['voucher_date']);
        $this->assertSame('000H - HO Balikpapan', $voucher['header']['project']);
        $this->assertSame('TRANSFER', $voucher['header']['payment_method']);
        $this->assertSame('IDR', $voucher['header']['currency']);
        $this->assertSame('11102001', $voucher['header']['bank_acc_no']);
        $this->assertSame('Payment for Invoice BPJS-2026-09', $voucher['header']['remarks']);
        $this->assertCount(2, $voucher['lines']);
        $this->assertEquals(2000000, $voucher['totals']['debit']);
        $this->assertEquals(2000000, $voucher['totals']['credit']);
        $this->assertStringContainsString('rupiah', strtolower($voucher['say']));
        $this->assertSame('Rachman J', $voucher['signatures']['reviewed_by_name']);
        $this->assertSame('sign_rj2.png', $voucher['signatures']['reviewed_by_signature']);
        $this->assertSame('sign_checked.png', $voucher['signatures']['checked_by_signature']);
        $this->assertSame('Budi Kasir', $voucher['signatures']['paid_by_name']);
        $this->assertSame('sign_paid.png', $voucher['signatures']['paid_by_signature']);
    }

    public function test_builds_complete_voucher_for_dds_payment_with_cash(): void
    {
        $submitter = User::factory()->create(['name' => 'Siti Cashier']);

        $log = SapSubmissionLog::query()->create([
            'dds_invoice_id' => 42,
            'document_type' => SapSubmissionLog::DOCUMENT_TYPE_INVOICE_PAYMENT,
            'status' => 'success',
            'action' => 'submission',
            'sap_doc_entry' => 777,
            'sap_doc_num' => '99001',
            'amount' => 1500000,
            'submitted_by' => $submitter->id,
            'user_id' => $submitter->id,
        ]);

        $paymentHeader = [
            'DocEntry' => 777,
            'DocNum' => 99001,
            'DocDate' => '2026-08-20',
            'CardName' => 'PT Vendor Satu',
            'CashSum' => 1500000,
            'TransferSum' => 0,
            'CashAccount' => '11101001',
            'DocCurrency' => 'IDR',
            'JournalRemarks' => 'Payment for Invoice INV-001',
            'ProjectCode' => '022C',
        ];

        $paymentLines = [
            [
                'account' => '21102001',
                'description' => 'Utang dagang',
                'debit' => 1500000,
                'credit' => 0,
            ],
            [
                'account' => '11101001',
                'description' => 'Kas kecil',
                'debit' => 0,
                'credit' => 1500000,
            ],
        ];

        $this->mock(SapService::class, function ($mock) use ($paymentHeader, $paymentLines): void {
            $mock->shouldReceive('getVendorPaymentByDocEntry')
                ->once()
                ->with(777)
                ->andReturn($paymentHeader);
            $mock->shouldReceive('getPaymentHeaderFromSql')
                ->once()
                ->with(777)
                ->andReturn(['prj_code' => '022C', 'doc_num' => '99001']);
            $mock->shouldReceive('getPaymentGlLines')
                ->once()
                ->with(777)
                ->andReturn($paymentLines);
            $mock->shouldReceive('getProjects')
                ->once()
                ->andReturn([
                    ['ProjectCode' => '022C', 'ProjectName' => 'Site C'],
                ]);
        });

        $voucher = app(OpVoucherService::class)->build($log);

        $this->assertSame('PT Vendor Satu', $voucher['header']['payment_for']);
        $this->assertSame('99001', $voucher['header']['voucher_no']);
        $this->assertSame('CASH', $voucher['header']['payment_method']);
        $this->assertSame('11101001', $voucher['header']['bank_acc_no']);
        $this->assertSame('022C - Site C', $voucher['header']['project']);
        $this->assertSame('Siti Cashier', $voucher['signatures']['paid_by_name']);
        $this->assertSame('sign_checked.png', $voucher['signatures']['checked_by_signature']);
        $this->assertSame('sign_paid.png', $voucher['signatures']['paid_by_signature']);
        $this->assertEquals(1500000, $voucher['totals']['debit']);
    }

    public function test_builds_voucher_with_empty_check_bg_when_payment_has_no_check_fields(): void
    {
        $submitter = User::factory()->create(['name' => 'Kasir OP']);

        $log = SapSubmissionLog::query()->create([
            'bpjs_ap_invoice_id' => 2,
            'document_type' => SapSubmissionLog::DOCUMENT_TYPE_BPJS_AP_INVOICE_PAYMENT,
            'status' => 'success',
            'action' => 'submission',
            'sap_doc_entry' => 10616,
            'sap_doc_num' => '88016',
            'amount' => 3000000,
            'submitted_by' => $submitter->id,
            'user_id' => $submitter->id,
        ]);

        Project::query()->create([
            'code' => '000H',
            'sap_code' => '000H',
            'name' => 'HO Balikpapan',
            'is_active' => true,
            'is_selectable' => true,
        ]);

        $paymentHeader = [
            'DocEntry' => 10616,
            'DocNum' => 88016,
            'DocDate' => '2026-09-15',
            'CardCode' => 'V-BPJS',
            'CardName' => 'BPJS KESEHATAN',
            'CashSum' => 0,
            'TransferSum' => 3000000,
            'TransferAccount' => '11102001',
            'DocCurrency' => 'IDR',
            'JournalRemarks' => 'Payment tanpa nomor cek/BG',
            'ProjectCode' => '000H',
            'CheckBgNo' => '',
        ];

        $paymentLines = [
            [
                'account' => '21101001',
                'description' => 'Utang BPJS',
                'debit' => 3000000,
                'credit' => 0,
            ],
            [
                'account' => '11102001',
                'description' => 'Bank Mandiri',
                'debit' => 0,
                'credit' => 3000000,
            ],
        ];

        $this->mock(SapService::class, function ($mock) use ($paymentHeader, $paymentLines): void {
            $mock->shouldReceive('getVendorPaymentByDocEntry')
                ->once()
                ->with(10616)
                ->andReturn($paymentHeader);
            $mock->shouldReceive('getPaymentHeaderFromSql')
                ->once()
                ->with(10616)
                ->andReturn(['prj_code' => '000H', 'doc_num' => '88016']);
            $mock->shouldReceive('getPaymentGlLines')
                ->once()
                ->with(10616)
                ->andReturn($paymentLines);
            $mock->shouldNotReceive('getProjects');
        });

        $voucher = app(OpVoucherService::class)->build($log);

        $this->assertSame('', $voucher['header']['check_bg_no']);
        $this->assertSame('88016', $voucher['header']['voucher_no']);
        $this->assertSame('BPJS KESEHATAN', $voucher['header']['payment_for']);
        $this->assertEquals(3000000, $voucher['totals']['debit']);
    }

    public function test_project_label_uses_prj_code_from_sql_query(): void
    {
        $submitter = User::factory()->create(['name' => 'Kasir OP']);

        $log = SapSubmissionLog::query()->create([
            'bpjs_ap_invoice_id' => 3,
            'document_type' => SapSubmissionLog::DOCUMENT_TYPE_BPJS_AP_INVOICE_PAYMENT,
            'status' => 'success',
            'action' => 'submission',
            'sap_doc_entry' => 268812015,
            'sap_doc_num' => '268812015',
            'amount' => 4770000,
            'submitted_by' => $submitter->id,
            'user_id' => $submitter->id,
        ]);

        Project::query()->create([
            'code' => '017C',
            'sap_code' => '017C',
            'name' => 'Project Site C',
            'is_active' => true,
            'is_selectable' => true,
        ]);

        $paymentHeader = [
            'DocEntry' => 268812015,
            'DocNum' => 268812015,
            'DocDate' => '2026-09-15',
            'CardName' => 'BPJS KESEHATAN',
            'CashSum' => 0,
            'TransferSum' => 4770000,
            'TransferAccount' => '11102001',
            'DocCurrency' => 'IDR',
            'JournalRemarks' => 'Payment OP prod sample',
            'ProjectCode' => '000H',
        ];

        $paymentLines = [
            [
                'account' => '21101001',
                'description' => 'Utang BPJS',
                'debit' => 4770000,
                'credit' => 0,
            ],
            [
                'account' => '11102001',
                'description' => 'Bank Mandiri',
                'debit' => 0,
                'credit' => 4770000,
            ],
        ];

        $this->mock(SapService::class, function ($mock) use ($paymentHeader, $paymentLines): void {
            $mock->shouldReceive('getVendorPaymentByDocEntry')
                ->once()
                ->with(268812015)
                ->andReturn($paymentHeader);
            $mock->shouldReceive('getPaymentHeaderFromSql')
                ->once()
                ->with(268812015)
                ->andReturn(['prj_code' => '017C', 'doc_num' => '268812015']);
            $mock->shouldReceive('getPaymentGlLines')
                ->once()
                ->with(268812015)
                ->andReturn($paymentLines);
            $mock->shouldNotReceive('getProjects');
        });

        $voucher = app(OpVoucherService::class)->build($log);

        $this->assertSame('017C - Project Site C', $voucher['header']['project']);
        $this->assertEquals(4770000, $voucher['totals']['debit']);
    }

    public function test_project_is_empty_when_sql_prj_code_is_null(): void
    {
        $submitter = User::factory()->create(['name' => 'Kasir OP']);

        $log = SapSubmissionLog::query()->create([
            'bpjs_ap_invoice_id' => 4,
            'document_type' => SapSubmissionLog::DOCUMENT_TYPE_BPJS_AP_INVOICE_PAYMENT,
            'status' => 'success',
            'action' => 'submission',
            'sap_doc_entry' => 268812015,
            'sap_doc_num' => '268812015',
            'amount' => 1000000,
            'submitted_by' => $submitter->id,
            'user_id' => $submitter->id,
        ]);

        Project::query()->create([
            'code' => '000H',
            'sap_code' => '000H',
            'name' => 'HO Balikpapan',
            'is_active' => true,
            'is_selectable' => true,
        ]);

        $paymentHeader = [
            'DocEntry' => 268812015,
            'DocNum' => 268812015,
            'DocDate' => '2026-09-15',
            'CardName' => 'BPJS KESEHATAN',
            'CashSum' => 0,
            'TransferSum' => 1000000,
            'TransferAccount' => '11102001',
            'DocCurrency' => 'IDR',
            'JournalRemarks' => 'Payment tanpa project',
            'ProjectCode' => '000H',
        ];

        $paymentLines = [
            [
                'account' => '21101001',
                'description' => 'Utang BPJS',
                'debit' => 1000000,
                'credit' => 0,
            ],
            [
                'account' => '11102001',
                'description' => 'Bank Mandiri',
                'debit' => 0,
                'credit' => 1000000,
            ],
        ];

        $this->mock(SapService::class, function ($mock) use ($paymentHeader, $paymentLines): void {
            $mock->shouldReceive('getVendorPaymentByDocEntry')
                ->once()
                ->with(268812015)
                ->andReturn($paymentHeader);
            $mock->shouldReceive('getPaymentHeaderFromSql')
                ->once()
                ->with(268812015)
                ->andReturn(['prj_code' => '', 'doc_num' => '268812015']);
            $mock->shouldReceive('getPaymentGlLines')
                ->once()
                ->with(268812015)
                ->andReturn($paymentLines);
            $mock->shouldNotReceive('getProjects');
        });

        $voucher = app(OpVoucherService::class)->build($log);

        $this->assertSame('', $voucher['header']['project']);
    }

    public function test_project_is_empty_when_sql_query_returns_no_rows(): void
    {
        $submitter = User::factory()->create(['name' => 'Kasir OP']);

        $log = SapSubmissionLog::query()->create([
            'bpjs_ap_invoice_id' => 5,
            'document_type' => SapSubmissionLog::DOCUMENT_TYPE_BPJS_AP_INVOICE_PAYMENT,
            'status' => 'success',
            'action' => 'submission',
            'sap_doc_entry' => 99999,
            'sap_doc_num' => '99999',
            'amount' => 500000,
            'submitted_by' => $submitter->id,
            'user_id' => $submitter->id,
        ]);

        $paymentHeader = [
            'DocEntry' => 99999,
            'DocNum' => 99999,
            'DocDate' => '2026-09-15',
            'CardName' => 'BPJS KESEHATAN',
            'CashSum' => 0,
            'TransferSum' => 500000,
            'TransferAccount' => '11102001',
            'DocCurrency' => 'IDR',
            'JournalRemarks' => 'Payment tanpa baris SQL header',
            'ProjectCode' => '000H',
        ];

        $paymentLines = [
            [
                'account' => '21101001',
                'description' => 'Utang BPJS',
                'debit' => 500000,
                'credit' => 0,
            ],
            [
                'account' => '11102001',
                'description' => 'Bank Mandiri',
                'debit' => 0,
                'credit' => 500000,
            ],
        ];

        $this->mock(SapService::class, function ($mock) use ($paymentHeader, $paymentLines): void {
            $mock->shouldReceive('getVendorPaymentByDocEntry')
                ->once()
                ->with(99999)
                ->andReturn($paymentHeader);
            $mock->shouldReceive('getPaymentHeaderFromSql')
                ->once()
                ->with(99999)
                ->andReturn(['prj_code' => '', 'doc_num' => '']);
            $mock->shouldReceive('getPaymentGlLines')
                ->once()
                ->with(99999)
                ->andReturn($paymentLines);
            $mock->shouldNotReceive('getProjects');
        });

        $voucher = app(OpVoucherService::class)->build($log);

        $this->assertSame('', $voucher['header']['project']);
    }
}
