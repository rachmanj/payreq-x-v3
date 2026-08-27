<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\SapBusinessPartner;
use App\Models\SapSubmissionLog;
use App\Models\User;
use App\Services\SapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InvoicePaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('DDS_API_URL=http://dds.test');
        putenv('DDS_API_KEY=test-api-key');
        putenv('DDS_DEPARTMENT_CODE=');

        Permission::firstOrCreate(['name' => 'submit_sap_invoice_payment', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'mark_invoice_paid_without_sap', 'guard_name' => 'web']);
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
            ->assertJsonPath('preview.ap_invoice.doc_entry', 555)
            ->assertJsonPath('preview.ap_invoice.doc_num', 9001)
            ->assertJsonPath('preview.partner.code', 'VSUP01')
            ->assertJsonPath('accounts.0.sap_account', '11010101');
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
                'payment_date' => '2026-08-20',
                'remarks' => 'Paid via bank transfer',
                'payment_project' => '001H',
                'payment_means' => 'transfer',
                'account_id' => $account->id,
                'close_invoice_in_dds' => true,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('sap_doc_num', '12345');

        $this->assertDatabaseHas('sap_submission_logs', [
            'document_type' => SapSubmissionLog::DOCUMENT_TYPE_INVOICE_PAYMENT,
            'dds_invoice_id' => 42,
            'status' => 'success',
            'sap_doc_num' => '12345',
            'sap_doc_entry' => 88,
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
                'payment_date' => '2026-08-20',
                'payment_means' => 'transfer',
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
            'sap_doc_num' => '11111',
            'sap_doc_entry' => 70,
            'attempt_number' => 1,
            'submitted_by' => $user->id,
            'user_id' => $user->id,
        ]);

        $this->mock(SapService::class, function ($mock) {
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

    public function test_submit_sap_payment_blocks_resubmission_when_already_posted(): void
    {
        $account = $this->seedVendorAndAccount();
        $user = $this->authorizedUser();

        SapSubmissionLog::create([
            'dds_invoice_id' => 42,
            'dds_invoice_number' => 'INV-001',
            'document_type' => SapSubmissionLog::DOCUMENT_TYPE_INVOICE_PAYMENT,
            'status' => 'success',
            'action' => 'submission',
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
                'payment_means' => 'transfer',
                'account_id' => $account->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'Already posted');
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
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson(route('cashier.invoice-payment.sap-payment.submit', ['invoiceId' => 42]), [
                'invoice_number' => 'INV-001',
                'supplier_sap_code' => 'VSUP01',
                'amount' => 1500000,
                'payment_date' => '2026-08-20',
                'payment_means' => 'transfer',
                'account_id' => 1,
            ])
            ->assertForbidden();
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
        ];
    }
}
