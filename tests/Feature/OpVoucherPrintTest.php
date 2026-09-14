<?php

namespace Tests\Feature;

use App\Models\BpjsApInvoice;
use App\Models\SapSubmissionLog;
use App\Models\User;
use App\Services\OpVoucherService;
use App\Services\SapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OpVoucherPrintTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'akses_ap_invoice_bpjs', 'guard_name' => 'web']);
    }

    public function test_bpjs_print_route_rejects_without_successful_payment_log(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('akses_ap_invoice_bpjs');

        $invoice = BpjsApInvoice::factory()->posted()->create();

        $this->actingAs($user)
            ->get(route('bpjs-ap-invoices.print-op', $invoice))
            ->assertNotFound();
    }

    public function test_dds_print_route_rejects_without_successful_payment_log(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('cashier.invoice-payment.print-op', ['ddsInvoiceId' => 99]))
            ->assertNotFound();
    }

    public function test_bpjs_print_page_renders_signature_and_paid_by_name(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('akses_ap_invoice_bpjs');

        $submitter = User::factory()->create(['name' => 'Andi Pembayar']);
        $invoice = BpjsApInvoice::factory()->posted()->create();

        SapSubmissionLog::query()->create([
            'bpjs_ap_invoice_id' => $invoice->id,
            'document_type' => SapSubmissionLog::DOCUMENT_TYPE_BPJS_AP_INVOICE_PAYMENT,
            'status' => 'success',
            'action' => 'submission',
            'sap_doc_entry' => 1001,
            'sap_doc_num' => '88010',
            'amount' => 1000000,
            'submitted_by' => $submitter->id,
            'user_id' => $submitter->id,
        ]);

        $voucher = [
            'header' => [
                'payment_for' => 'BPJS KESEHATAN',
                'voucher_no' => '88010',
                'voucher_date' => '07-Sep-2026',
                'project' => '000H - HO Balikpapan',
                'payment_method' => 'TRANSFER',
                'currency' => 'IDR',
                'bank_acc_no' => '11102001',
                'check_bg_no' => '',
                'remarks' => 'Payment for Invoice BPJS',
            ],
            'lines' => [
                [
                    'account' => '21101001',
                    'description' => 'Utang BPJS',
                    'debit' => 1000000,
                    'credit' => 0,
                ],
                [
                    'account' => '11102001',
                    'description' => 'Bank',
                    'debit' => 0,
                    'credit' => 1000000,
                ],
            ],
            'totals' => [
                'debit' => 1000000,
                'credit' => 1000000,
            ],
            'say' => 'Satu juta rupiah',
            'signatures' => [
                'reviewed_by_name' => 'Rachman J',
                'reviewed_by_signature' => 'sign_rj2.png',
                'paid_by_name' => 'Andi Pembayar',
                'checked_by_name' => null,
                'received_by_name' => null,
            ],
        ];

        $this->mock(OpVoucherService::class, function ($mock) use ($voucher): void {
            $mock->shouldReceive('build')->once()->andReturn($voucher);
        });

        $this->mock(SapService::class);

        $response = $this->actingAs($user)
            ->get(route('bpjs-ap-invoices.print-op', $invoice));

        $response->assertOk();
        $response->assertSee('sign_rj2.png', false);
        $response->assertSee('Rachman J', false);
        $response->assertSee('Andi Pembayar', false);
        $response->assertSee('Cash Bank Voucher Out', false);
    }

    public function test_dds_print_page_renders_signature_and_paid_by_name(): void
    {
        $submitter = User::factory()->create(['name' => 'Dewi Kasir']);

        SapSubmissionLog::query()->create([
            'dds_invoice_id' => 55,
            'document_type' => SapSubmissionLog::DOCUMENT_TYPE_INVOICE_PAYMENT,
            'status' => 'success',
            'action' => 'submission',
            'sap_doc_entry' => 2002,
            'sap_doc_num' => '99055',
            'amount' => 500000,
            'submitted_by' => $submitter->id,
            'user_id' => $submitter->id,
        ]);

        $voucher = [
            'header' => [
                'payment_for' => 'PT Vendor DDS',
                'voucher_no' => '99055',
                'voucher_date' => '20-Aug-2026',
                'project' => '022C - Site C',
                'payment_method' => 'TRANSFER',
                'currency' => 'IDR',
                'bank_acc_no' => '11102002',
                'check_bg_no' => '',
                'remarks' => 'Payment for Invoice INV-055',
            ],
            'lines' => [
                [
                    'account' => '21102001',
                    'description' => 'Utang',
                    'debit' => 500000,
                    'credit' => 0,
                ],
                [
                    'account' => '11102002',
                    'description' => 'Bank',
                    'debit' => 0,
                    'credit' => 500000,
                ],
            ],
            'totals' => [
                'debit' => 500000,
                'credit' => 500000,
            ],
            'say' => 'Lima ratus ribu rupiah',
            'signatures' => [
                'reviewed_by_name' => 'Rachman J',
                'reviewed_by_signature' => 'sign_rj2.png',
                'paid_by_name' => 'Dewi Kasir',
                'checked_by_name' => null,
                'received_by_name' => null,
            ],
        ];

        $this->mock(OpVoucherService::class, function ($mock) use ($voucher): void {
            $mock->shouldReceive('build')->once()->andReturn($voucher);
        });

        $this->mock(SapService::class);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('cashier.invoice-payment.print-op', ['ddsInvoiceId' => 55]));

        $response->assertOk();
        $response->assertSee('sign_rj2.png', false);
        $response->assertSee('Rachman J', false);
        $response->assertSee('Dewi Kasir', false);
    }
}
