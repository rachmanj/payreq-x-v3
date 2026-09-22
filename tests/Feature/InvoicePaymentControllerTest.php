<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\BpjsApInvoice;
use App\Models\Parameter;
use App\Models\SapBusinessPartner;
use App\Models\SapSubmissionLog;
use App\Models\User;
use App\Services\SapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InvoicePaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.dds.api_url' => 'http://dds.test',
            'services.dds.api_key' => 'test-api-key',
            'services.dds.department_code' => '',
        ]);

        Permission::firstOrCreate(['name' => 'submit_sap_invoice_payment', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'mark_invoice_paid_without_sap', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'akses_invoice_payment', 'guard_name' => 'web']);

        Parameter::query()
            ->where('name1', 'invoice_payment_accounts')
            ->delete();

        Cache::forget('dds.departments');
    }

    public function test_dashboard_counts_waiting_and_paid_by_payment_date(): void
    {
        Http::preventStrayRequests();

        Http::fake(function ($request) {
            $url = $request->url();

            if (str_ends_with($url, '/api/v1/departments')) {
                return Http::response([
                    'success' => true,
                    'data' => [
                        'departments' => [
                            ['location_code' => '000HCASHO', 'name' => 'Cashier HO'],
                        ],
                    ],
                ]);
            }

            if (str_contains($url, '/api/v1/departments/000HCASHO/invoices')) {
                return Http::response([
                    'success' => true,
                    'data' => [
                        'invoices' => [
                            [
                                'id' => 1,
                                'amount' => 1000000,
                                'receive_date' => '2025-01-01',
                                'payment_date' => null,
                                'status' => 'open',
                            ],
                            [
                                'id' => 2,
                                'amount' => 500000,
                                'receive_date' => '2025-01-15',
                                'payment_date' => '2025-02-01',
                                'status' => 'open',
                            ],
                            [
                                'id' => 3,
                                'amount' => 250000,
                                'receive_date' => '2024-01-01',
                                'payment_date' => null,
                                'status' => 'overdue',
                            ],
                        ],
                    ],
                ]);
            }

            return Http::response(['success' => false], 404);
        });

        $user = User::factory()->create([
            'dds_department_code' => '000HCASHO',
        ]);

        $this->actingAs($user)
            ->getJson(route('cashier.invoice-payment.dashboard'))
            ->assertOk()
            ->assertJson([
                'total_invoices' => 3,
                'waiting_invoices' => 2,
                'paid_invoices' => 1,
                'overdue_invoices' => 2,
            ]);
    }

    public function test_department_scoped_endpoints_reject_invalid_department_code(): void
    {
        Http::preventStrayRequests();

        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/api/v1/departments')) {
                return Http::response([
                    'success' => true,
                    'data' => [
                        'departments' => [
                            ['location_code' => '000HCASHO', 'name' => 'Cashier HO'],
                        ],
                    ],
                ]);
            }

            return Http::response(['success' => false], 404);
        });

        $user = User::factory()->create([
            'dds_department_code' => 'INVALID',
        ]);

        $this->actingAs($user)
            ->getJson(route('cashier.invoice-payment.waiting'))
            ->assertStatus(400)
            ->assertJson([
                'error' => 'Invalid department code',
            ])
            ->assertJsonFragment(['department_code' => 'INVALID']);
    }

    public function test_update_payment_requires_mark_paid_without_sap_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson(route('cashier.invoice-payment.update-payment', ['invoiceId' => 42]), [
                'payment_date' => '2025-08-20',
            ])
            ->assertForbidden();
    }

    public function test_update_payment_sends_closed_status_and_paid_payment_status_to_dds(): void
    {
        Http::preventStrayRequests();

        Http::fake(function ($request) {
            if (str_contains($request->url(), '/api/v1/invoices/42/payment')) {
                return Http::response([
                    'success' => true,
                    'message' => 'Invoice payment updated successfully',
                    'data' => ['id' => 42],
                ]);
            }

            return Http::response(['success' => false], 404);
        });

        $user = User::factory()->create();
        $user->givePermissionTo('mark_invoice_paid_without_sap');

        $this->actingAs($user)
            ->putJson(route('cashier.invoice-payment.update-payment', ['invoiceId' => 42]), [
                'payment_date' => '2025-08-20',
                'remarks' => 'Paid via bank transfer',
                'payment_project' => '001H',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/v1/invoices/42/payment')
                && $request['payment_status'] === 'paid'
                && $request['status'] === 'closed'
                && $request['payment_date'] === '2025-08-20'
                && $request['payment_project'] === '001H'
                && ! array_key_exists('sap_doc', $request->data());
        });
    }

    public function test_preview_sap_payment_returns_ap_invoice_and_accounts(): void
    {
        $this->seedVendorAndAccount();

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getPurchaseInvoiceByNumAtCard')
                ->once()
                ->with('INV-001')
                ->andReturn($this->openApInvoice());
        });

        $this->actingAs($this->authorizedUser())
            ->getJson(route('cashier.invoice-payment.sap-payment.preview', [
                'invoiceId' => 42,
                'invoice_number' => 'INV-001',
                'supplier_sap_code' => 'VSUP01',
                'amount' => 1500000,
                'payment_date' => '2026-08-20',
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('fully_paid', false)
            ->assertJsonPath('preview.ap_invoice.doc_entry', 555)
            ->assertJsonPath('preview.ap_invoice.doc_num', 9001)
            ->assertJsonPath('preview.ap_invoice.remaining_balance', 1500000)
            ->assertJsonPath('preview.partner.code', 'VSUP01')
            ->assertJsonPath('accounts.0.sap_account', '11010101');
    }

    public function test_preview_sap_payment_includes_withholding_and_default_net_amount(): void
    {
        $this->seedVendorAndAccount();

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getPurchaseInvoiceByNumAtCard')
                ->once()
                ->with('INV-WTAX')
                ->andReturn($this->apInvoiceWithOpenWithholdingTax());
        });

        $this->actingAs($this->authorizedUser())
            ->getJson(route('cashier.invoice-payment.sap-payment.preview', [
                'invoiceId' => 42,
                'invoice_number' => 'INV-WTAX',
                'supplier_sap_code' => 'VSUP01',
                'amount' => 1831500,
                'payment_date' => '2026-08-20',
            ]))
            ->assertOk()
            ->assertJsonPath('preview.withholding.total', 33000)
            ->assertJsonPath('preview.withholding.entries.0.WTCode', '1019')
            ->assertJsonPath('preview.withholding.entries.0.WTAmount', 33000)
            ->assertJsonPath('preview.net_amount', 1798500)
            ->assertJsonPath('preview.payment_amount', 1798500)
            ->assertJsonPath('preview.gross_applied', 1831500)
            ->assertJsonPath('preview.ap_invoice.remaining_balance', 1831500);
    }

    public function test_preview_sap_payment_without_withholding_tax_unchanged(): void
    {
        $this->seedVendorAndAccount();

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getPurchaseInvoiceByNumAtCard')
                ->once()
                ->with('INV-001')
                ->andReturn($this->openApInvoice());
        });

        $this->actingAs($this->authorizedUser())
            ->getJson(route('cashier.invoice-payment.sap-payment.preview', [
                'invoiceId' => 42,
                'invoice_number' => 'INV-001',
                'supplier_sap_code' => 'VSUP01',
                'amount' => 1500000,
                'payment_date' => '2026-08-20',
            ]))
            ->assertOk()
            ->assertJsonPath('preview.payment_amount', 1500000)
            ->assertJsonPath('preview.withholding.total', 0)
            ->assertJsonPath('preview.withholding.entries', [])
            ->assertJsonPath('preview.gross_applied', 1500000)
            ->assertJsonMissingPath('preview.withholdingTax');
    }

    public function test_preview_sap_payment_returns_fully_paid_when_sap_balance_is_zero(): void
    {
        $this->seedVendorAndAccount();
        $user = $this->authorizedUser();

        SapSubmissionLog::create([
            'dds_invoice_id' => 42,
            'dds_invoice_number' => 'INV-001',
            'document_type' => SapSubmissionLog::DOCUMENT_TYPE_INVOICE_PAYMENT,
            'status' => 'success',
            'action' => 'submission',
            'amount' => 1500000,
            'sap_doc_num' => '11111',
            'sap_doc_entry' => 70,
            'attempt_number' => 1,
            'submitted_by' => $user->id,
            'user_id' => $user->id,
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getPurchaseInvoiceByNumAtCard')
                ->once()
                ->andReturn($this->fullyPaidApInvoice());
        });

        $this->actingAs($user)
            ->getJson(route('cashier.invoice-payment.sap-payment.preview', [
                'invoiceId' => 42,
                'invoice_number' => 'INV-001',
                'supplier_sap_code' => 'VSUP01',
                'amount' => 1500000,
            ]))
            ->assertOk()
            ->assertJsonPath('fully_paid', true)
            ->assertJsonPath('preview.ap_invoice.remaining_balance', 0)
            ->assertJsonPath('payment_history.0.doc_num', '11111');
    }

    public function test_preview_sap_payment_ignores_closed_withholding_tax_entries(): void
    {
        $this->seedVendorAndAccount();

        $apInvoice = $this->openApInvoice();
        $apInvoice['WithholdingTaxDataCollection'] = [
            ['WTCode' => '1019', 'WTAmount' => 33000, 'Status' => 'bost_Closed'],
        ];

        $this->mock(SapService::class, function ($mock) use ($apInvoice) {
            $mock->shouldReceive('getPurchaseInvoiceByNumAtCard')
                ->once()
                ->with('INV-001')
                ->andReturn($apInvoice);
        });

        $this->actingAs($this->authorizedUser())
            ->getJson(route('cashier.invoice-payment.sap-payment.preview', [
                'invoiceId' => 42,
                'invoice_number' => 'INV-001',
                'supplier_sap_code' => 'VSUP01',
                'amount' => 1500000,
                'payment_date' => '2026-08-20',
            ]))
            ->assertOk()
            ->assertJsonPath('preview.payment_amount', 1500000)
            ->assertJsonPath('preview.withholding.total', 0)
            ->assertJsonPath('preview.gross_applied', 1500000);
    }

    public function test_submit_sap_payment_logs_success_and_writes_remarks_to_dds(): void
    {
        Http::preventStrayRequests();
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/api/v1/invoices/42/payment')) {
                return Http::response([
                    'success' => true,
                    'data' => ['id' => 42],
                ]);
            }

            return Http::response(['success' => false], 404);
        });

        $account = $this->seedVendorAndAccount();

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getPurchaseInvoiceByNumAtCard')
                ->once()
                ->with('INV-001')
                ->andReturn($this->openApInvoice());

            $mock->shouldReceive('createOutgoingPayment')
                ->once()
                ->andReturn([
                    'success' => true,
                    'doc_entry' => 88,
                    'doc_num' => '12345',
                    'data' => ['DocEntry' => 88, 'DocNum' => 12345],
                ]);
        });

        $this->actingAs($this->authorizedUser())
            ->postJson(route('cashier.invoice-payment.sap-payment.submit', ['invoiceId' => 42]), [
                'invoice_number' => 'INV-001',
                'supplier_sap_code' => 'VSUP01',
                'amount' => 1500000,
                'payment_amount' => 1500000,
                'payment_date' => '2026-08-20',
                'remarks' => 'Paid via bank transfer',
                'payment_project' => '001H',
                'payment_means' => 'transfer',
                'prepared_by' => 'John Preparer',
                'approved_by' => 'Jane Approver',
                'account_id' => $account->id,
                'close_invoice_in_dds' => true,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('sap_doc_num', '12345')
            ->assertJsonPath('fully_paid', true);

        $this->assertDatabaseHas('sap_submission_logs', [
            'document_type' => SapSubmissionLog::DOCUMENT_TYPE_INVOICE_PAYMENT,
            'dds_invoice_id' => 42,
            'status' => 'success',
            'sap_doc_num' => '12345',
            'sap_doc_entry' => 88,
            'amount' => 1500000,
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/v1/invoices/42/payment')
                && $request['payment_status'] === 'paid'
                && $request['status'] === 'closed'
                && $request['payment_date'] === '2026-08-20'
                && str_contains((string) $request['remarks'], 'SAP OP #12345 (DocEntry 88)');
        });
    }

    public function test_submit_sap_payment_returns_422_when_ap_invoice_is_missing(): void
    {
        $account = $this->seedVendorAndAccount();

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getPurchaseInvoiceByNumAtCard')
                ->once()
                ->andReturn(null);
            $mock->shouldNotReceive('createOutgoingPayment');
        });

        $this->actingAs($this->authorizedUser())
            ->postJson(route('cashier.invoice-payment.sap-payment.submit', ['invoiceId' => 42]), [
                'invoice_number' => 'INV-001',
                'supplier_sap_code' => 'VSUP01',
                'amount' => 1500000,
                'payment_amount' => 1500000,
                'payment_date' => '2026-08-20',
                'payment_means' => 'transfer',
                'prepared_by' => 'John Preparer',
                'approved_by' => 'Jane Approver',
                'account_id' => $account->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'AP Invoice not found');

        $this->assertDatabaseMissing('sap_submission_logs', [
            'document_type' => SapSubmissionLog::DOCUMENT_TYPE_INVOICE_PAYMENT,
            'dds_invoice_id' => 42,
            'status' => 'success',
        ]);
    }

    public function test_submit_sap_payment_closes_dds_only_when_already_posted_from_waiting(): void
    {
        Http::preventStrayRequests();
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/api/v1/invoices/42/payment')) {
                return Http::response([
                    'success' => true,
                    'data' => ['id' => 42],
                ]);
            }

            return Http::response(['success' => false], 404);
        });

        $user = $this->authorizedUser();

        SapSubmissionLog::create([
            'dds_invoice_id' => 42,
            'dds_invoice_number' => 'INV-001',
            'document_type' => SapSubmissionLog::DOCUMENT_TYPE_INVOICE_PAYMENT,
            'status' => 'success',
            'action' => 'submission',
            'amount' => 1500000,
            'sap_doc_num' => '11111',
            'sap_doc_entry' => 70,
            'attempt_number' => 1,
            'submitted_by' => $user->id,
            'user_id' => $user->id,
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldNotReceive('getPurchaseInvoiceByNumAtCard');
            $mock->shouldNotReceive('createOutgoingPayment');
        });

        $this->actingAs($user)
            ->postJson(route('cashier.invoice-payment.sap-payment.submit', ['invoiceId' => 42]), [
                'invoice_number' => 'INV-001',
                'supplier_sap_code' => 'VSUP01',
                'amount' => 1500000,
                'payment_date' => '2026-08-20',
                'remarks' => 'Paid via bank transfer',
                'payment_project' => '001H',
                'close_invoice_in_dds' => true,
                'close_dds_only' => true,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('sap_doc_num', '11111');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/v1/invoices/42/payment')
                && $request['payment_status'] === 'paid'
                && $request['status'] === 'closed';
        });
    }

    public function test_submit_sap_payment_blocks_resubmission_when_fully_paid_in_sap(): void
    {
        $account = $this->seedVendorAndAccount();
        $user = $this->authorizedUser();

        SapSubmissionLog::create([
            'dds_invoice_id' => 42,
            'dds_invoice_number' => 'INV-001',
            'document_type' => SapSubmissionLog::DOCUMENT_TYPE_INVOICE_PAYMENT,
            'status' => 'success',
            'action' => 'submission',
            'amount' => 1500000,
            'sap_doc_num' => '11111',
            'sap_doc_entry' => 70,
            'attempt_number' => 1,
            'submitted_by' => $user->id,
            'user_id' => $user->id,
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getPurchaseInvoiceByNumAtCard')
                ->once()
                ->andReturn($this->fullyPaidApInvoice());
            $mock->shouldNotReceive('createOutgoingPayment');
        });

        $this->actingAs($user)
            ->postJson(route('cashier.invoice-payment.sap-payment.submit', ['invoiceId' => 42]), [
                'invoice_number' => 'INV-001',
                'supplier_sap_code' => 'VSUP01',
                'amount' => 1500000,
                'payment_amount' => 500000,
                'payment_date' => '2026-08-20',
                'payment_means' => 'transfer',
                'prepared_by' => 'John Preparer',
                'approved_by' => 'Jane Approver',
                'account_id' => $account->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'Already posted');
    }

    public function test_submit_sap_payment_allows_partial_payment_and_keeps_dds_open(): void
    {
        Http::preventStrayRequests();
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/api/v1/invoices/42/payment')) {
                return Http::response([
                    'success' => true,
                    'data' => ['id' => 42],
                ]);
            }

            return Http::response(['success' => false], 404);
        });

        $account = $this->seedVendorAndAccount();

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getPurchaseInvoiceByNumAtCard')
                ->once()
                ->andReturn($this->openApInvoice());

            $mock->shouldReceive('createOutgoingPayment')
                ->once()
                ->with(\Mockery::on(function (array $payload) {
                    return $payload['TransferSum'] === 500000.0
                        && $payload['PaymentInvoices'][0]['SumApplied'] === 500000.0
                        && $payload['U_MIS_Signature1'] === 'John Preparer'
                        && $payload['U_MIS_Signature2'] === 'Jane Approver';
                }))
                ->andReturn([
                    'success' => true,
                    'doc_entry' => 89,
                    'doc_num' => '12346',
                    'data' => ['DocEntry' => 89, 'DocNum' => 12346],
                ]);
        });

        $this->actingAs($this->authorizedUser())
            ->postJson(route('cashier.invoice-payment.sap-payment.submit', ['invoiceId' => 42]), [
                'invoice_number' => 'INV-001',
                'supplier_sap_code' => 'VSUP01',
                'amount' => 1500000,
                'payment_amount' => 500000,
                'payment_date' => '2026-08-20',
                'payment_means' => 'transfer',
                'prepared_by' => 'John Preparer',
                'approved_by' => 'Jane Approver',
                'account_id' => $account->id,
                'close_invoice_in_dds' => true,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('fully_paid', false)
            ->assertJsonPath('remaining_balance', 1000000);

        $this->assertDatabaseHas('sap_submission_logs', [
            'dds_invoice_id' => 42,
            'status' => 'success',
            'amount' => 500000,
            'sap_doc_num' => '12346',
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/v1/invoices/42/payment')
                && ! array_key_exists('status', $request->data())
                && str_contains((string) $request['remarks'], 'Remaining Rp 1.000.000');
        });
    }

    public function test_submit_sap_payment_allows_second_partial_until_fully_paid(): void
    {
        Http::preventStrayRequests();
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/api/v1/invoices/42/payment')) {
                return Http::response([
                    'success' => true,
                    'data' => ['id' => 42],
                ]);
            }

            return Http::response(['success' => false], 404);
        });

        $account = $this->seedVendorAndAccount();
        $user = $this->authorizedUser();

        SapSubmissionLog::create([
            'dds_invoice_id' => 42,
            'dds_invoice_number' => 'INV-001',
            'document_type' => SapSubmissionLog::DOCUMENT_TYPE_INVOICE_PAYMENT,
            'status' => 'success',
            'action' => 'submission',
            'amount' => 500000,
            'sap_doc_num' => '12346',
            'sap_doc_entry' => 89,
            'attempt_number' => 1,
            'submitted_by' => $user->id,
            'user_id' => $user->id,
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getPurchaseInvoiceByNumAtCard')
                ->once()
                ->andReturn($this->partiallyPaidApInvoice());

            $mock->shouldReceive('createOutgoingPayment')
                ->once()
                ->andReturn([
                    'success' => true,
                    'doc_entry' => 90,
                    'doc_num' => '12347',
                    'data' => ['DocEntry' => 90, 'DocNum' => 12347],
                ]);
        });

        $this->actingAs($user)
            ->postJson(route('cashier.invoice-payment.sap-payment.submit', ['invoiceId' => 42]), [
                'invoice_number' => 'INV-001',
                'supplier_sap_code' => 'VSUP01',
                'amount' => 1500000,
                'payment_amount' => 1000000,
                'payment_date' => '2026-08-20',
                'payment_means' => 'transfer',
                'prepared_by' => 'John Preparer',
                'approved_by' => 'Jane Approver',
                'account_id' => $account->id,
                'close_invoice_in_dds' => true,
            ])
            ->assertOk()
            ->assertJsonPath('fully_paid', true);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/v1/invoices/42/payment')
                && $request['payment_status'] === 'paid'
                && $request['status'] === 'closed';
        });
    }

    public function test_submit_sap_payment_rejects_amount_above_remaining_balance(): void
    {
        $account = $this->seedVendorAndAccount();

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getPurchaseInvoiceByNumAtCard')
                ->once()
                ->andReturn($this->partiallyPaidApInvoice());
            $mock->shouldNotReceive('createOutgoingPayment');
        });

        $this->actingAs($this->authorizedUser())
            ->postJson(route('cashier.invoice-payment.sap-payment.submit', ['invoiceId' => 42]), [
                'invoice_number' => 'INV-001',
                'supplier_sap_code' => 'VSUP01',
                'amount' => 1500000,
                'payment_amount' => 1200000,
                'payment_date' => '2026-08-20',
                'payment_means' => 'transfer',
                'prepared_by' => 'John Preparer',
                'approved_by' => 'Jane Approver',
                'account_id' => $account->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'Validation failed');
    }

    public function test_sap_payment_routes_require_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('cashier.invoice-payment.sap-payment.preview', [
                'invoiceId' => 42,
                'invoice_number' => 'INV-001',
                'supplier_sap_code' => 'VSUP01',
                'amount' => 1500000,
            ]))
            ->assertForbidden()
            ->assertJson([
                'error' => 'forbidden',
                'reason' => 'permission',
            ]);

        $this->actingAs($user)
            ->postJson(route('cashier.invoice-payment.sap-payment.submit', ['invoiceId' => 42]), [
                'invoice_number' => 'INV-001',
                'supplier_sap_code' => 'VSUP01',
                'amount' => 1500000,
                'payment_amount' => 1500000,
                'payment_date' => '2026-08-20',
                'payment_means' => 'transfer',
                'prepared_by' => 'John Preparer',
                'approved_by' => 'Jane Approver',
                'account_id' => 1,
            ])
            ->assertForbidden()
            ->assertJson([
                'error' => 'forbidden',
                'reason' => 'permission',
            ]);
    }

    public function test_preview_sap_payment_without_permission_returns_forbidden_json_shape(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('cashier.invoice-payment.sap-payment.preview', [
                'invoiceId' => 42,
                'invoice_number' => 'INV-001',
                'supplier_sap_code' => 'VSUP01',
                'amount' => 1500000,
            ]))
            ->assertStatus(403)
            ->assertJsonPath('error', 'forbidden')
            ->assertJsonPath('reason', 'permission');
    }

    public function test_index_remains_accessible_without_submit_sap_invoice_payment_permission(): void
    {
        Http::preventStrayRequests();
        Http::fake($this->ddsDepartmentFake());

        $user = User::factory()->create(['dds_department_code' => '000HCASHO']);

        $this->actingAs($user)
            ->get(route('cashier.invoice-payment.index'))
            ->assertOk();
    }

    public function test_index_includes_sap_preview_permission_and_invalid_response_messages(): void
    {
        Http::preventStrayRequests();
        Http::fake($this->ddsDepartmentFake());

        $user = User::factory()->create(['dds_department_code' => '000HCASHO']);

        $this->actingAs($user)
            ->get(route('cashier.invoice-payment.index'))
            ->assertOk()
            ->assertSee('submit_sap_invoice_payment', false)
            ->assertSee('Respons server tidak valid (kosong). Muat ulang halaman; bila berulang laporkan ke admin.', false)
            ->assertSee('Anda tidak punya izin submit Outgoing Payment ke SAP (permission: submit_sap_invoice_payment). Minta admin memberikan izin ini ke akun Anda.', false);
    }

    protected function authorizedUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('submit_sap_invoice_payment');

        return $user;
    }

    protected function seedVendorAndAccount(): Account
    {
        SapBusinessPartner::query()->create([
            'code' => 'VSUP01',
            'name' => 'PT Vendor Satu',
            'type' => SapBusinessPartner::TYPE_SUPPLIER,
            'active' => true,
        ]);

        return Account::query()->create([
            'account_number' => '110101',
            'account_name' => 'Bank BCA HO',
            'type' => 'bank',
            'sap_account' => '11010101',
            'is_active' => true,
            'is_hidden' => false,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function openApInvoice(): array
    {
        return [
            'DocEntry' => 555,
            'DocNum' => 9001,
            'CardCode' => 'VSUP01',
            'DocumentStatus' => 'bost_Open',
            'Cancelled' => 'N',
            'NumAtCard' => 'INV-001',
            'DocTotal' => 1500000,
            'PaidToDate' => 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function partiallyPaidApInvoice(): array
    {
        return [
            'DocEntry' => 555,
            'DocNum' => 9001,
            'CardCode' => 'VSUP01',
            'DocumentStatus' => 'bost_Open',
            'Cancelled' => 'N',
            'NumAtCard' => 'INV-001',
            'DocTotal' => 1500000,
            'PaidToDate' => 500000,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function fullyPaidApInvoice(): array
    {
        return [
            'DocEntry' => 555,
            'DocNum' => 9001,
            'CardCode' => 'VSUP01',
            'DocumentStatus' => 'bost_Open',
            'Cancelled' => 'N',
            'NumAtCard' => 'INV-001',
            'DocTotal' => 1500000,
            'PaidToDate' => 1500000,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function apInvoiceWithOpenWithholdingTax(): array
    {
        return [
            'DocEntry' => 777,
            'DocNum' => 267007511,
            'CardCode' => 'VSUP01',
            'DocumentStatus' => 'bost_Open',
            'Cancelled' => 'N',
            'NumAtCard' => 'INV-WTAX',
            'DocTotal' => 1831500,
            'PaidToDate' => 0,
            'WithholdingTaxDataCollection' => [
                [
                    'WTCode' => '1019',
                    'WTAmount' => 33000,
                    'Status' => 'bost_Open',
                ],
            ],
        ];
    }

    public function test_waiting_payment_source_bpjs_returns_only_bpjs_without_dds_http(): void
    {
        $this->cacheValidDdsDepartment();

        Http::preventStrayRequests();
        Http::fake();

        $bpjs = BpjsApInvoice::factory()->posted()->create([
            'amount' => 2000000,
            'paid_amount' => 0,
            'doc_date' => '2026-09-01',
        ]);

        $user = User::factory()->create(['dds_department_code' => '000HCASHO']);

        $response = $this->actingAs($user)
            ->getJson(route('cashier.invoice-payment.waiting', ['source' => 'bpjs']))
            ->assertOk();

        $invoices = $response->json('invoices');
        $this->assertCount(1, $invoices);
        $this->assertSame('bpjs', $invoices[0]['source']);
        $this->assertSame($bpjs->id, $invoices[0]['local_id']);

        Http::assertNothingSent();
    }

    public function test_waiting_payment_source_dds_excludes_bpjs_rows(): void
    {
        $this->cacheValidDdsDepartment();

        Http::preventStrayRequests();

        Http::fake(function ($request) {
            if (str_contains($request->url(), '/wait-payment-invoices')) {
                return Http::response([
                    'success' => true,
                    'data' => [
                        'invoices' => [
                            [
                                'id' => 501,
                                'invoice_number' => 'DDS-INV-501',
                                'amount' => 750000,
                                'receive_date' => '2026-08-01',
                                'payment_date' => null,
                                'status' => 'open',
                            ],
                        ],
                    ],
                ]);
            }

            return Http::response(['success' => false], 404);
        });

        BpjsApInvoice::factory()->posted()->create([
            'amount' => 2000000,
            'paid_amount' => 0,
            'doc_date' => '2026-09-01',
        ]);

        $user = User::factory()->create(['dds_department_code' => '000HCASHO']);

        $response = $this->actingAs($user)
            ->getJson(route('cashier.invoice-payment.waiting', ['source' => 'dds']))
            ->assertOk();

        $invoices = $response->json('invoices');
        $this->assertCount(1, $invoices);
        $this->assertSame(501, $invoices[0]['id']);
        $this->assertArrayNotHasKey('source', $invoices[0]);

        Http::assertSentCount(1);
    }

    public function test_waiting_payment_without_source_includes_dds_and_bpjs(): void
    {
        $this->cacheValidDdsDepartment();

        Http::preventStrayRequests();

        Http::fake(function ($request) {
            if (str_contains($request->url(), '/wait-payment-invoices')) {
                return Http::response([
                    'success' => true,
                    'data' => [
                        'invoices' => [
                            [
                                'id' => 88,
                                'invoice_number' => 'DDS-INV-88',
                                'amount' => 100000,
                                'receive_date' => '2026-08-15',
                                'payment_date' => null,
                                'status' => 'open',
                            ],
                        ],
                    ],
                ]);
            }

            return Http::response(['success' => false], 404);
        });

        $bpjs = BpjsApInvoice::factory()->posted()->create([
            'amount' => 2000000,
            'paid_amount' => 0,
            'doc_date' => '2026-09-01',
        ]);

        $user = User::factory()->create(['dds_department_code' => '000HCASHO']);

        $response = $this->actingAs($user)
            ->getJson(route('cashier.invoice-payment.waiting'))
            ->assertOk();

        $ids = collect($response->json('invoices'))->pluck('id')->all();
        $this->assertContains(88, $ids);
        $this->assertContains('bpjs:'.$bpjs->id, $ids);
    }

    public function test_waiting_payment_invalid_source_treated_as_all_sources(): void
    {
        $this->cacheValidDdsDepartment();

        Http::preventStrayRequests();

        Http::fake(function ($request) {
            if (str_contains($request->url(), '/wait-payment-invoices')) {
                return Http::response([
                    'success' => true,
                    'data' => [
                        'invoices' => [
                            [
                                'id' => 77,
                                'invoice_number' => 'DDS-INV-77',
                                'amount' => 50000,
                                'receive_date' => '2026-08-10',
                                'payment_date' => null,
                                'status' => 'open',
                            ],
                        ],
                    ],
                ]);
            }

            return Http::response(['success' => false], 404);
        });

        $bpjs = BpjsApInvoice::factory()->posted()->create([
            'amount' => 1500000,
            'paid_amount' => 0,
            'doc_date' => '2026-09-02',
        ]);

        $user = User::factory()->create(['dds_department_code' => '000HCASHO']);

        $response = $this->actingAs($user)
            ->getJson(route('cashier.invoice-payment.waiting', ['source' => 'xyz']))
            ->assertOk();

        $ids = collect($response->json('invoices'))->pluck('id')->all();
        $this->assertContains(77, $ids);
        $this->assertContains('bpjs:'.$bpjs->id, $ids);
    }

    public function test_index_includes_source_filter_dropdown(): void
    {
        Http::preventStrayRequests();
        Http::fake($this->ddsDepartmentFake());

        $user = User::factory()->create(['dds_department_code' => '000HCASHO']);

        $this->actingAs($user)
            ->get(route('cashier.invoice-payment.index'))
            ->assertOk()
            ->assertSee('id="filter_source"', false)
            ->assertSee('Semua Source', false)
            ->assertSee('value="bpjs"', false)
            ->assertSee('value="dds"', false);
    }

    public function test_waiting_payment_includes_bpjs_posted_invoices(): void
    {
        Http::preventStrayRequests();
        Http::fake($this->ddsDepartmentFake());

        $bpjs = BpjsApInvoice::factory()->posted()->create([
            'amount' => 2000000,
            'paid_amount' => 0,
            'doc_date' => '2026-09-01',
        ]);

        $this->assertDatabaseHas('bpjs_ap_invoices', [
            'id' => $bpjs->id,
            'status' => BpjsApInvoice::STATUS_POSTED,
        ]);

        $user = User::factory()->create(['dds_department_code' => '000HCASHO']);

        $this->actingAs($user)
            ->getJson(route('cashier.invoice-payment.waiting'))
            ->assertOk()
            ->assertJsonFragment([
                'source' => 'bpjs',
                'local_id' => $bpjs->id,
                'invoice_number' => $bpjs->invoiceNumber(),
            ]);
    }

    public function test_submit_sap_payment_bpjs_full_payment_marks_invoice_paid(): void
    {
        $account = $this->seedVendorAndAccount();

        SapBusinessPartner::query()->create([
            'code' => 'VBPKEIDR01',
            'name' => 'BPJS KESEHATAN',
            'type' => SapBusinessPartner::TYPE_SUPPLIER,
            'active' => true,
        ]);

        $bpjs = BpjsApInvoice::factory()->posted()->create([
            'jenis' => BpjsApInvoice::JENIS_KESEHATAN,
            'unit' => '000H',
            'amount' => 2000000,
            'paid_amount' => 0,
            'sap_doc_entry' => 28625,
            'sap_doc_num' => '55001',
            'num_at_card' => '10/26',
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getPurchaseInvoiceByDocEntry')
                ->once()
                ->with(28625)
                ->andReturn([
                    'DocEntry' => 28625,
                    'DocNum' => 55001,
                    'CardCode' => 'VBPKEIDR01',
                    'DocumentStatus' => 'bost_Open',
                    'Cancelled' => 'N',
                    'NumAtCard' => '10/26',
                    'DocTotal' => 2000000,
                    'PaidToDate' => 0,
                ]);

            $mock->shouldReceive('createOutgoingPayment')
                ->once()
                ->andReturn([
                    'success' => true,
                    'doc_entry' => 99,
                    'doc_num' => '88001',
                    'data' => ['DocEntry' => 99, 'DocNum' => 88001],
                ]);
        });

        $this->actingAs($this->authorizedUser())
            ->postJson(route('cashier.invoice-payment.sap-payment.submit', ['invoiceId' => 'bpjs:'.$bpjs->id]), [
                'invoice_number' => $bpjs->invoiceNumber(),
                'supplier_sap_code' => 'VBPKEIDR01',
                'amount' => 2000000,
                'payment_amount' => 2000000,
                'payment_date' => '2026-09-07',
                'payment_means' => 'transfer',
                'prepared_by' => 'Preparer',
                'approved_by' => 'Approver',
                'account_id' => $account->id,
                'close_invoice_in_dds' => true,
            ])
            ->assertOk()
            ->assertJsonPath('fully_paid', true)
            ->assertJsonPath('source', 'bpjs');

        $bpjs->refresh();
        $this->assertSame(BpjsApInvoice::STATUS_PAID, $bpjs->status);
        $this->assertSame(2000000.0, (float) $bpjs->paid_amount);

        $this->assertDatabaseHas('sap_submission_logs', [
            'bpjs_ap_invoice_id' => $bpjs->id,
            'document_type' => SapSubmissionLog::DOCUMENT_TYPE_BPJS_AP_INVOICE_PAYMENT,
            'status' => 'success',
        ]);

        Http::assertNothingSent();
    }

    public function test_submit_sap_payment_bpjs_partial_payment_keeps_posted_status(): void
    {
        $account = $this->seedVendorAndAccount();

        SapBusinessPartner::query()->create([
            'code' => 'VBPKEIDR01',
            'name' => 'BPJS KESEHATAN',
            'type' => SapBusinessPartner::TYPE_SUPPLIER,
            'active' => true,
        ]);

        $bpjs = BpjsApInvoice::factory()->posted()->create([
            'amount' => 2000000,
            'paid_amount' => 0,
            'sap_doc_entry' => 28625,
            'sap_doc_num' => '55001',
            'num_at_card' => '10/26',
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getPurchaseInvoiceByDocEntry')
                ->once()
                ->andReturn([
                    'DocEntry' => 28625,
                    'DocNum' => 55001,
                    'CardCode' => 'VBPKEIDR01',
                    'DocumentStatus' => 'bost_Open',
                    'Cancelled' => 'N',
                    'NumAtCard' => '10/26',
                    'DocTotal' => 2000000,
                    'PaidToDate' => 0,
                ]);

            $mock->shouldReceive('createOutgoingPayment')
                ->once()
                ->andReturn([
                    'success' => true,
                    'doc_entry' => 100,
                    'doc_num' => '88002',
                    'data' => ['DocEntry' => 100, 'DocNum' => 88002],
                ]);
        });

        $this->actingAs($this->authorizedUser())
            ->postJson(route('cashier.invoice-payment.sap-payment.submit', ['invoiceId' => 'bpjs:'.$bpjs->id]), [
                'invoice_number' => $bpjs->invoiceNumber(),
                'supplier_sap_code' => 'VBPKEIDR01',
                'amount' => 2000000,
                'payment_amount' => 1000000,
                'payment_date' => '2026-09-07',
                'payment_means' => 'transfer',
                'prepared_by' => 'Preparer',
                'approved_by' => 'Approver',
                'account_id' => $account->id,
            ])
            ->assertOk()
            ->assertJsonPath('fully_paid', false);

        $bpjs->refresh();
        $this->assertSame(BpjsApInvoice::STATUS_POSTED, $bpjs->status);
        $this->assertSame(1000000.0, (float) $bpjs->paid_amount);
    }

    public function test_update_payment_marks_bpjs_invoice_paid_without_sap(): void
    {
        $bpjs = BpjsApInvoice::factory()->posted()->create([
            'amount' => 1500000,
            'paid_amount' => 0,
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo('mark_invoice_paid_without_sap');

        $this->actingAs($user)
            ->putJson(route('cashier.invoice-payment.update-payment', ['invoiceId' => 'bpjs:'.$bpjs->id]), [
                'payment_date' => '2026-09-07',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $bpjs->refresh();
        $this->assertSame(BpjsApInvoice::STATUS_PAID, $bpjs->status);
        $this->assertSame(1500000.0, (float) $bpjs->paid_amount);
    }

    public function test_index_includes_print_op_action_scripts(): void
    {
        Http::preventStrayRequests();
        Http::fake($this->ddsDepartmentFake());

        $user = User::factory()->create(['dds_department_code' => '000HCASHO']);

        $this->actingAs($user)
            ->get(route('cashier.invoice-payment.index'))
            ->assertOk()
            ->assertSee('renderPrintOpAction', false)
            ->assertSee('Print OP', false)
            ->assertSee(route('cashier.invoice-payment.print-op', ['ddsInvoiceId' => ':id']), false)
            ->assertSee(route('bpjs-ap-invoices.print-op', ['bpjsApInvoice' => ':id']), false);
    }

    public function test_sap_payment_detail_maps_payment_logs_and_sap_response(): void
    {
        $this->seedVendorAndAccount();

        $submitter = User::factory()->create(['name' => 'Kasir Utama']);
        $submitter->givePermissionTo('akses_invoice_payment');

        $bpjs = BpjsApInvoice::factory()->posted()->create([
            'jenis' => BpjsApInvoice::JENIS_KESEHATAN,
            'unit' => '000H',
            'amount' => 2000000,
            'paid_amount' => 2000000,
            'status' => BpjsApInvoice::STATUS_PAID,
        ]);

        SapSubmissionLog::create([
            'bpjs_ap_invoice_id' => $bpjs->id,
            'document_type' => SapSubmissionLog::DOCUMENT_TYPE_BPJS_AP_INVOICE_PAYMENT,
            'status' => 'success',
            'action' => 'submission',
            'amount' => 2000000,
            'sap_doc_num' => '88001',
            'sap_doc_entry' => 501,
            'attempt_number' => 1,
            'submitted_by' => $submitter->id,
            'user_id' => $submitter->id,
            'sap_response' => [
                'DocNum' => 88001,
                'DocEntry' => 501,
                'DocDate' => '2026-09-07',
                'TransferAccount' => '11010101',
                'TransferSum' => 2000000,
                'TransferReference' => 'TRF-001',
                'Reference1' => 'REF1',
                'Reference2' => 'REF2',
                'Remarks' => 'Remarks OP',
                'JournalRemarks' => 'Journal OP',
                'PaymentInvoices' => [
                    ['DocNum' => 55001, 'SumApplied' => 2000000.0],
                ],
            ],
        ]);

        $this->actingAs($submitter)
            ->getJson(route('cashier.invoice-payment.sap-payment.detail', ['invoiceId' => 'bpjs:'.$bpjs->id]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('invoice.source', 'bpjs')
            ->assertJsonPath('payments.0.doc_num', '88001')
            ->assertJsonPath('payments.0.means', 'transfer')
            ->assertJsonPath('payments.0.account', '11010101')
            ->assertJsonPath('payments.0.account_label', 'Bank BCA HO (110101)')
            ->assertJsonPath('payments.0.applied_invoices.0.doc_num', '55001')
            ->assertJsonPath('payments.0.applied_invoices.0.sum_applied', 2000000)
            ->assertJsonPath('summary.payment_count', 1)
            ->assertJsonPath('print_op_url', route('bpjs-ap-invoices.print-op', $bpjs));
    }

    public function test_sap_payment_detail_requires_akses_invoice_payment_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('cashier.invoice-payment.sap-payment.detail', ['invoiceId' => 42]))
            ->assertForbidden();
    }

    public function test_sap_payment_detail_returns_404_when_invoice_not_found(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('akses_invoice_payment');

        $this->actingAs($user)
            ->getJson(route('cashier.invoice-payment.sap-payment.detail', ['invoiceId' => 'bpjs:999999']))
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'Not found');
    }

    public function test_index_includes_detail_op_action_scripts(): void
    {
        Http::preventStrayRequests();
        Http::fake($this->ddsDepartmentFake());

        $user = User::factory()->create(['dds_department_code' => '000HCASHO']);

        $this->actingAs($user)
            ->get(route('cashier.invoice-payment.index'))
            ->assertOk()
            ->assertSee('Detail pembayaran (OP)', false)
            ->assertSee('sap-payment-detail-btn', false)
            ->assertSee('renderDetailOpAction', false)
            ->assertSee(route('cashier.invoice-payment.sap-payment.detail', ['invoiceId' => ':invoiceId']), false);
    }

    public function test_index_encodes_invoice_id_in_sap_payment_and_print_urls(): void
    {
        Http::preventStrayRequests();
        Http::fake($this->ddsDepartmentFake());

        $user = User::factory()->create(['dds_department_code' => '000HCASHO']);
        $user->givePermissionTo('submit_sap_invoice_payment');

        $html = $this->actingAs($user)
            ->get(route('cashier.invoice-payment.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            "sapPreviewUrlTemplate.replace(':invoiceId', encodeURIComponent(row.id))",
            $html
        );
        $this->assertStringContainsString(
            "sapSubmitUrlTemplate.replace(':invoiceId', encodeURIComponent(invoiceId))",
            $html
        );
        $this->assertStringContainsString(
            "bpjsPrintOpUrlTemplate.replace(':id', encodeURIComponent(row.local_id))",
            $html
        );
        $this->assertStringContainsString(
            "ddsPrintOpUrlTemplate.replace(':id', encodeURIComponent(row.id))",
            $html
        );
        $this->assertStringContainsString(
            "sapPaymentDetailUrlTemplate.replace(':invoiceId', encodeURIComponent(row.id))",
            $html
        );
        $this->assertStringContainsString(
            ".replace(':invoiceId', encodeURIComponent(invoiceId))",
            $html
        );
    }

    public function test_preview_sap_payment_with_url_encoded_bpjs_invoice_id_returns_accounts(): void
    {
        $this->seedVendorAndAccount();

        SapBusinessPartner::query()->create([
            'code' => 'VBPKEIDR01',
            'name' => 'BPJS KESEHATAN',
            'type' => SapBusinessPartner::TYPE_SUPPLIER,
            'active' => true,
        ]);

        $bpjs = BpjsApInvoice::factory()->posted()->create([
            'jenis' => BpjsApInvoice::JENIS_KESEHATAN,
            'unit' => '000H',
            'amount' => 2000000,
            'paid_amount' => 0,
            'sap_doc_entry' => 28625,
            'sap_doc_num' => '55001',
            'num_at_card' => '10/26',
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getPurchaseInvoiceByDocEntry')
                ->once()
                ->with(28625)
                ->andReturn([
                    'DocEntry' => 28625,
                    'DocNum' => 55001,
                    'CardCode' => 'VBPKEIDR01',
                    'DocumentStatus' => 'bost_Open',
                    'Cancelled' => 'N',
                    'NumAtCard' => '10/26',
                    'DocTotal' => 2000000,
                    'PaidToDate' => 0,
                ]);
        });

        $previewPath = '/cashier/invoice-payment/invoices/bpjs%3A'.$bpjs->id.'/sap-payment/preview';

        $this->actingAs($this->authorizedUser())
            ->getJson($previewPath.'?'.http_build_query([
                'invoice_number' => $bpjs->invoiceNumber(),
                'supplier_sap_code' => 'VBPKEIDR01',
                'amount' => 2000000,
                'payment_date' => '2026-08-20',
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('fully_paid', false)
            ->assertJsonCount(1, 'accounts')
            ->assertJsonPath('accounts.0.sap_account', '11010101');
    }

    public function test_index_includes_pph23_withholding_ui_markup(): void
    {
        Http::preventStrayRequests();
        Http::fake($this->ddsDepartmentFake());

        $user = User::factory()->create(['dds_department_code' => '000HCASHO']);
        $user->givePermissionTo('submit_sap_invoice_payment');

        $this->actingAs($user)
            ->get(route('cashier.invoice-payment.index'))
            ->assertOk()
            ->assertSee('id="sapPaymentWithholdingInfo"', false)
            ->assertSee('id="sapPaymentWithholdingBreakdown"', false)
            ->assertSee('Total invoice (bruto)', false)
            ->assertSee('PPh23 (WTCode 1019)', false)
            ->assertSee('Dibayar netto', false)
            ->assertSee('renderSapWithholdingUi', false)
            ->assertSee('Invoice ini mengandung PPh23 sebesar', false);
    }

    public function test_dashboard_returns_rate_limit_message_when_dds_responds_with_429(): void
    {
        Http::preventStrayRequests();

        Http::fake(function ($request) {
            $url = $request->url();

            if (str_ends_with($url, '/api/v1/departments')) {
                return Http::response([
                    'success' => false,
                    'error' => 'Rate limit exceeded',
                    'message' => 'Hourly rate limit exceeded. Please try again later.',
                    'retry_after' => 3600,
                ], 429);
            }

            if (str_contains($url, '/invoices')) {
                return Http::response([
                    'success' => false,
                    'error' => 'Rate limit exceeded',
                    'retry_after' => 3600,
                ], 429);
            }

            return Http::response(['success' => false], 404);
        });

        $user = User::factory()->create(['dds_department_code' => '000HCASHO']);

        $this->actingAs($user)
            ->getJson(route('cashier.invoice-payment.dashboard'))
            ->assertStatus(429)
            ->assertJson([
                'rate_limited' => true,
                'retry_after_minutes' => 60,
            ])
            ->assertJsonPath('message', 'API DDS sedang membatasi permintaan (rate limit). Coba lagi dalam 60 menit.');

        $this->assertStringNotContainsString(
            'API URL and key',
            (string) $this->actingAs($user)->getJson(route('cashier.invoice-payment.dashboard'))->json('message')
        );
    }

    public function test_department_list_is_cached_across_requests_within_ttl(): void
    {
        Http::preventStrayRequests();

        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/api/v1/departments')) {
                return Http::response([
                    'success' => true,
                    'data' => [
                        'departments' => [
                            ['location_code' => '000HCASHO', 'name' => 'Cashier HO'],
                        ],
                    ],
                ]);
            }

            if (str_contains($url = $request->url(), '/wait-payment-invoices')) {
                return Http::response([
                    'success' => true,
                    'data' => ['invoices' => []],
                ]);
            }

            return Http::response(['success' => false], 404);
        });

        $user = User::factory()->create(['dds_department_code' => '000HCASHO']);

        $this->actingAs($user)->get(route('cashier.invoice-payment.index'))->assertOk();
        $this->actingAs($user)->get(route('cashier.invoice-payment.index'))->assertOk();

        Http::assertSentCount(1);
    }

    public function test_failed_department_fetch_does_not_fill_cache_and_retries_http(): void
    {
        Http::preventStrayRequests();

        $departmentCalls = 0;

        Http::fake(function ($request) use (&$departmentCalls) {
            if (str_ends_with($request->url(), '/api/v1/departments')) {
                $departmentCalls++;

                return Http::response([
                    'success' => false,
                    'error' => 'Rate limit exceeded',
                    'retry_after' => 3600,
                ], 429);
            }

            return Http::response(['success' => false], 404);
        });

        $user = User::factory()->create(['dds_department_code' => '000HCASHO']);

        $this->actingAs($user)->get(route('cashier.invoice-payment.index'))->assertOk();
        $this->actingAs($user)->get(route('cashier.invoice-payment.index'))->assertOk();

        $this->assertSame(2, $departmentCalls);
        $this->assertNull(Cache::get('dds.departments'));

        Cache::put('dds.departments', ['000HCASHO'], now()->addMinutes(10));

        $this->actingAs($user)->get(route('cashier.invoice-payment.index'))->assertOk();
        $this->assertSame(2, $departmentCalls);
        $this->assertTrue(Cache::has('dds.departments'));
    }

    public function test_index_shows_rate_limit_banner_not_connection_issue(): void
    {
        Http::preventStrayRequests();

        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/api/v1/departments')) {
                return Http::response([
                    'success' => false,
                    'error' => 'Rate limit exceeded',
                    'retry_after' => 3600,
                ], 429);
            }

            return Http::response(['success' => false], 404);
        });

        $user = User::factory()->create(['dds_department_code' => '000HCASHO']);

        $this->actingAs($user)
            ->get(route('cashier.invoice-payment.index'))
            ->assertOk()
            ->assertSee('Batas permintaan API DDS (rate limit)', false)
            ->assertSee('rate limit', false)
            ->assertDontSee('Check API URL and key', false)
            ->assertDontSee('DDS Connection Issue', false);
    }

    protected function cacheValidDdsDepartment(): void
    {
        Cache::put('dds.departments', ['000HCASHO'], now()->addMinutes(10));
    }

    protected function ddsDepartmentFake(): callable
    {
        return function ($request) {
            $url = $request->url();

            if (str_ends_with($url, '/api/v1/departments')) {
                return Http::response([
                    'success' => true,
                    'data' => [
                        'departments' => [
                            ['location_code' => '000HCASHO', 'name' => 'Cashier HO'],
                        ],
                    ],
                ]);
            }

            if (str_contains($url, '/wait-payment-invoices')) {
                return Http::response([
                    'success' => true,
                    'data' => ['invoices' => []],
                ]);
            }

            return Http::response(['success' => false], 404);
        };
    }
}
