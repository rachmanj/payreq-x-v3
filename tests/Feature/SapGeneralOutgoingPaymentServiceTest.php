<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Bilyet;
use App\Models\Department;
use App\Models\GeneralOutgoingPayment;
use App\Models\Giro;
use App\Models\SapSubmissionLog;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\SapGeneralOutgoingPaymentService;
use App\Services\SapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class SapGeneralOutgoingPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Giro $giro;

    private Bilyet $bilyet;

    private Account $cashAccountA;

    private Account $cashAccountB;

    private Account $advanceAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $department = Department::query()->create([
            'department_name' => 'Accounting',
            'akronim' => 'ACC',
            'sap_code' => '30',
        ]);

        $this->user = User::factory()->create([
            'project' => '000H',
            'department_id' => $department->id,
        ]);

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

        $this->cashAccountA = Account::query()->create([
            'account_number' => '11101001',
            'account_name' => 'Petty Cash',
            'type' => 'cash',
            'project' => '000H',
            'sap_account' => '11101001',
            'app_balance' => 1000000,
            'is_active' => true,
        ]);

        $this->cashAccountB = Account::query()->create([
            'account_number' => '11101020',
            'account_name' => 'Intransit',
            'type' => 'cash',
            'project' => '000H',
            'sap_account' => '11101020',
            'app_balance' => 500000,
            'is_active' => true,
        ]);

        $this->advanceAccount = Account::query()->create([
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

    public function test_submit_success_creates_records_updates_bilyet_and_balances(): void
    {
        $this->mockSapSuccess('268811748', 10271);

        $this->actingAs($this->user);

        $result = $this->service()->submit(
            $this->giro->id,
            $this->bilyet->id,
            100934000,
            '2026-08-12',
            '2026-08-12',
            '000H',
            'Operational PC by Payreq & PMT BPJS TK',
            $this->destinationPayload(),
            $this->user,
            profitCenter: '30',
        );

        $this->assertTrue($result['success']);
        $this->assertSame('268811748', $result['doc_num']);

        $payment = GeneralOutgoingPayment::query()->first();
        $this->assertNotNull($payment);
        $this->assertSame('success', $payment->status);
        $this->assertSame('11101001', $payment->akun_tujuan_utama);
        $this->assertSame(2, $payment->accounts()->count());

        $this->bilyet->refresh();
        $this->assertSame('cair', $this->bilyet->status);
        $this->assertSame('2026-08-12', $this->bilyet->cair_date?->format('Y-m-d'));
        $this->assertSame('2026-08-12', $this->bilyet->bilyet_date?->format('Y-m-d'));
        $this->assertEquals(100934000, (float) $this->bilyet->amount);
        $this->assertSame('268811748', $this->bilyet->sap_doc_num);
        $this->assertSame('Operational PC by Payreq & PMT BPJS TK', $this->bilyet->remarks);

        $this->cashAccountA->refresh();
        $this->cashAccountB->refresh();
        $this->advanceAccount->refresh();

        $this->assertEquals(100950500, (float) $this->cashAccountA->app_balance);
        $this->assertEquals(1483500, (float) $this->cashAccountB->app_balance);
        $this->assertEquals(505500, (float) $this->advanceAccount->app_balance);

        $this->assertSame(2, Transaksi::query()->where('document_type', 'incoming')->where('document_id', $payment->id)->count());

        $this->assertDatabaseHas('sap_submission_logs', [
            'document_type' => SapSubmissionLog::DOCUMENT_TYPE_GENERAL_OUTGOING_PAYMENT,
            'status' => 'success',
            'sap_doc_num' => '268811748',
        ]);
    }

    public function test_submit_success_retry_does_not_double_balances(): void
    {
        $this->mockSapSuccess('268811748', 10271);
        $this->actingAs($this->user);

        $service = $this->service();
        $payload = [
            $this->giro->id,
            $this->bilyet->id,
            100934000,
            '2026-08-12',
            '2026-08-12',
            '000H',
            'Operational PC by Payreq & PMT BPJS TK',
            $this->destinationPayload(),
            $this->user,
        ];

        $first = $service->submit(...$payload);
        $second = $service->submit(...$payload);

        $this->assertTrue($first['success']);
        $this->assertTrue($second['success']);
        $this->assertSame(1, GeneralOutgoingPayment::query()->count());
        $this->assertSame(2, Transaksi::query()->where('document_type', 'incoming')->count());

        $this->cashAccountA->refresh();
        $this->advanceAccount->refresh();
        $this->assertEquals(100950500, (float) $this->cashAccountA->app_balance);
        $this->assertEquals(505500, (float) $this->advanceAccount->app_balance);
    }

    public function test_submit_sap_failure_keeps_bilyet_onhand_and_balances_unchanged(): void
    {
        $mock = Mockery::mock(SapService::class);
        $mock->shouldReceive('createGeneralOutgoingPayment')
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'SAP rejected payload',
            ]);
        $this->app->instance(SapService::class, $mock);

        $this->actingAs($this->user);

        $result = $this->service()->submit(
            $this->giro->id,
            $this->bilyet->id,
            100934000,
            '2026-08-12',
            '2026-08-12',
            '000H',
            'Operational PC by Payreq & PMT BPJS TK',
            $this->destinationPayload(),
            $this->user,
            profitCenter: '30',
        );

        $this->assertFalse($result['success']);

        $this->bilyet->refresh();
        $this->assertSame('onhand', $this->bilyet->status);
        $this->assertNull($this->bilyet->sap_doc_num);

        $this->cashAccountA->refresh();
        $this->advanceAccount->refresh();
        $this->assertEquals(1000000, (float) $this->cashAccountA->app_balance);
        $this->assertEquals(101439500, (float) $this->advanceAccount->app_balance);

        $this->assertSame(0, GeneralOutgoingPayment::query()->count());
        $this->assertSame(0, Transaksi::query()->count());

        $this->assertDatabaseHas('sap_submission_logs', [
            'document_type' => SapSubmissionLog::DOCUMENT_TYPE_GENERAL_OUTGOING_PAYMENT,
            'status' => 'failed',
        ]);
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

    /**
     * @return list<array{account_id: int, amount: float|int, description?: string|null}>
     */
    private function destinationPayload(): array
    {
        return [
            ['account_id' => $this->cashAccountA->id, 'amount' => 99950500, 'description' => 'Petty Cash line'],
            ['account_id' => $this->cashAccountB->id, 'amount' => 983500, 'description' => 'Intransit line'],
        ];
    }

    private function service(): SapGeneralOutgoingPaymentService
    {
        return app(SapGeneralOutgoingPaymentService::class);
    }
}
