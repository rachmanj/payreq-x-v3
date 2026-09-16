<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Bilyet;
use App\Models\GeneralOutgoingPayment;
use App\Models\Giro;
use App\Models\SapSubmissionLog;
use App\Models\User;
use App\Services\OpVoucherService;
use App\Services\SapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class GeneralOutgoingPaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Giro $giro;

    private Bilyet $bilyet;

    private Account $cashAccount;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'create_general_op', 'guard_name' => 'web']);

        $this->user = User::factory()->create(['project' => '000H']);
        $this->user->givePermissionTo('create_general_op');

        $bankId = DB::table('banks')->insertGetId([
            'name' => 'Mandiri',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->giro = Giro::query()->create([
            'acc_no' => '14903',
            'acc_name' => 'Bank Mandiri Balikpapan',
            'bank_id' => $bankId,
            'type' => 'giro',
            'project' => '000H',
            'sap_account' => '11201001',
        ]);

        $this->bilyet = Bilyet::query()->create([
            'giro_id' => $this->giro->id,
            'prefix' => 'JM',
            'nomor' => '130552',
            'type' => 'cek',
            'status' => 'onhand',
            'project' => '000H',
        ]);

        $this->cashAccount = Account::query()->create([
            'account_number' => '11101001',
            'account_name' => 'Petty Cash',
            'type' => 'cash',
            'project' => '000H',
            'sap_account' => '11101001',
            'app_balance' => 1000000,
            'is_active' => true,
        ]);

        Account::query()->create([
            'account_number' => '11030101',
            'account_name' => 'Advance',
            'type' => 'advance',
            'project' => '000H',
            'app_balance' => 101439500,
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_routes_require_create_general_op_permission(): void
    {
        $unauthorizedUser = User::factory()->create(['project' => '000H']);

        $this->actingAs($unauthorizedUser)
            ->getJson(route('cashier.general-op.index'))
            ->assertForbidden();

        $this->actingAs($unauthorizedUser)
            ->getJson(route('cashier.general-op.create'))
            ->assertForbidden();

        $this->actingAs($unauthorizedUser)
            ->postJson(route('cashier.general-op.preview'), $this->submissionPayload())
            ->assertForbidden();

        $this->actingAs($unauthorizedUser)
            ->postJson(route('cashier.general-op.submit'), $this->submissionPayload())
            ->assertForbidden();
    }

    public function test_create_page_renders_form(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('cashier.general-op.create'));

        $response->assertOk();
        $response->assertSee('Buat OP Umum (Pinbuk Bank → Cash)', false);
        $response->assertSee('Giro Bank', false);
        $response->assertSee('Bilyet (onhand)', false);
        $response->assertSee('Petty Cash', false);
        $response->assertSee('Preview', false);
    }

    public function test_preview_returns_summary_payload(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('cashier.general-op.preview'), $this->submissionPayload());

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'success',
            'preview' => [
                'giro',
                'bilyet',
                'amount',
                'doc_date',
                'destination_accounts',
            ],
            'sap_payload' => [
                'DocType',
                'TransferAccount',
                'TransferSum',
                'TransferDate',
                'TransferReference',
                'PaymentAccounts',
            ],
            'local_impact' => [
                'destination_accounts',
                'note',
            ],
        ]);
        $response->assertJsonPath('preview.bilyet.nomor', '130552');
        $response->assertJsonPath('sap_payload.DocType', 'rAccount');
        $response->assertJsonPath('sap_payload.TransferReference', 'JM 130552');
        $response->assertJsonMissing(['sap_payload' => ['PaymentChecks' => []]]);
        $response->assertJsonPath('sap_payload.PaymentAccounts.0.ProfitCenter', '30');
        $response->assertJsonPath('local_impact.note', 'Saldo kas akan bertambah, akun advance berkurang sesuai nominal tiap akun tujuan.');
    }

    public function test_submit_success_creates_record_and_redirects(): void
    {
        $this->mockSapSuccess('268811748', 10271);

        $response = $this->actingAs($this->user)
            ->post(route('cashier.general-op.submit'), $this->submissionPayload());

        $response->assertRedirect(route('cashier.general-op.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('general_outgoing_payments', [
            'giro_id' => $this->giro->id,
            'bilyet_id' => $this->bilyet->id,
            'sap_doc_num' => '268811748',
            'status' => GeneralOutgoingPayment::STATUS_SUCCESS,
        ]);
    }

    public function test_print_page_renders_when_sap_doc_num_exists(): void
    {
        $payment = GeneralOutgoingPayment::query()->create([
            'giro_id' => $this->giro->id,
            'bilyet_id' => $this->bilyet->id,
            'posting_date' => '2026-08-12',
            'doc_date' => '2026-08-12',
            'amount' => 100934000,
            'remarks' => 'Operational PC',
            'project' => '000H',
            'akun_tujuan_utama' => '11101001',
            'sap_doc_num' => '268811748',
            'sap_doc_entry' => 10271,
            'status' => GeneralOutgoingPayment::STATUS_SUCCESS,
            'created_by' => $this->user->id,
            'submitted_at' => now(),
        ]);

        SapSubmissionLog::query()->create([
            'document_type' => SapSubmissionLog::DOCUMENT_TYPE_GENERAL_OUTGOING_PAYMENT,
            'status' => 'success',
            'action' => 'submission',
            'sap_doc_entry' => 10271,
            'sap_doc_num' => '268811748',
            'amount' => 100934000,
            'submitted_by' => $this->user->id,
            'user_id' => $this->user->id,
        ]);

        $voucher = [
            'header' => [
                'payment_for' => '11101001',
                'voucher_no' => '268811748',
                'voucher_date' => '12-August-2026',
                'project' => '000H - HO',
                'payment_method' => 'CHEQUE',
                'currency' => 'IDR',
                'bank_acc_no' => '11201001',
                'check_bg_no' => '130552',
                'remarks' => 'Operational PC',
            ],
            'lines' => [
                [
                    'account' => '11101001',
                    'description' => 'Petty Cash',
                    'debit' => 100934000,
                    'credit' => 0,
                ],
                [
                    'account' => '11201001',
                    'description' => 'Bank Mandiri',
                    'debit' => 0,
                    'credit' => 100934000,
                ],
            ],
            'totals' => [
                'debit' => 100934000,
                'credit' => 100934000,
            ],
            'say' => 'Seratus juta rupiah',
            'signatures' => [
                'reviewed_by_name' => 'Rachman J',
                'reviewed_by_signature' => 'sign_rj2.png',
                'checked_by_signature' => 'sign_checked.png',
                'paid_by_name' => $this->user->name,
                'paid_by_signature' => 'sign_paid.png',
                'checked_by_name' => null,
                'received_by_name' => null,
            ],
        ];

        $this->mock(OpVoucherService::class, function ($mock) use ($voucher): void {
            $mock->shouldReceive('build')->once()->andReturn($voucher);
        });

        $this->mock(SapService::class);

        $response = $this->actingAs($this->user)
            ->get(route('cashier.general-op.print-op', $payment->id));

        $response->assertOk();
        $response->assertSee('Cash Bank Voucher Out', false);
        $response->assertSee('CHEQUE', false);
        $response->assertSee('130552', false);
        $response->assertSee('268811748', false);
    }

    public function test_print_route_returns_not_found_without_sap_doc_num(): void
    {
        $payment = GeneralOutgoingPayment::query()->create([
            'giro_id' => $this->giro->id,
            'bilyet_id' => $this->bilyet->id,
            'posting_date' => '2026-08-12',
            'doc_date' => '2026-08-12',
            'amount' => 100934000,
            'remarks' => 'Operational PC',
            'project' => '000H',
            'akun_tujuan_utama' => '11101001',
            'sap_doc_num' => null,
            'sap_doc_entry' => null,
            'status' => GeneralOutgoingPayment::STATUS_FAILED,
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('cashier.general-op.print-op', $payment->id))
            ->assertNotFound();
    }

    /**
     * @return array<string, mixed>
     */
    private function submissionPayload(): array
    {
        return [
            'giro_id' => $this->giro->id,
            'bilyet_id' => $this->bilyet->id,
            'amount' => 100934000,
            'doc_date' => '2026-08-12',
            'posting_date' => '2026-08-12',
            'project' => '000H',
            'remarks' => 'Operational PC by Payreq & PMT BPJS TK',
            'destination_accounts' => [
                [
                    'account_id' => $this->cashAccount->id,
                    'amount' => 100934000,
                    'description' => 'Petty Cash line',
                ],
            ],
        ];
    }

    private function mockSapSuccess(string $docNum, int $docEntry): void
    {
        $mock = Mockery::mock(SapService::class);
        $mock->shouldReceive('createGeneralOutgoingPayment')
            ->once()
            ->andReturn([
                'success' => true,
                'doc_num' => $docNum,
                'doc_entry' => $docEntry,
                'data' => ['DocNum' => $docNum, 'DocEntry' => $docEntry],
            ]);
        $this->app->instance(SapService::class, $mock);
    }
}
