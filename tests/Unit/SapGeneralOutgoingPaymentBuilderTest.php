<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\Bilyet;
use App\Models\Giro;
use App\Services\SapGeneralOutgoingPaymentBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SapGeneralOutgoingPaymentBuilderTest extends TestCase
{
    use RefreshDatabase;

    private Giro $giro;

    private Bilyet $bilyet;

    private Account $cashAccountA;

    private Account $cashAccountB;

    protected function setUp(): void
    {
        parent::setUp();

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
            'app_balance' => 0,
            'is_active' => true,
        ]);

        $this->cashAccountB = Account::query()->create([
            'account_number' => '11101020',
            'account_name' => 'Intransit',
            'type' => 'cash',
            'project' => '000H',
            'sap_account' => '11101020',
            'app_balance' => 0,
            'is_active' => true,
        ]);
    }

    public function test_build_payload_matches_verified_sap_shape(): void
    {
        $builder = $this->makeBuilder();

        $this->assertSame([], $builder->validate());

        $payload = $builder->build();

        $this->assertSame('rAccount', $payload['DocType']);
        $this->assertSame('11101001', $payload['CardCode']);
        $this->assertSame('2026-08-12', $payload['DocDate']);
        $this->assertSame('IDR', $payload['DocCurrency']);
        $this->assertSame('000H', $payload['ProjectCode']);
        $this->assertSame('Operational PC by Payreq & PMT BPJS TK', $payload['Remarks']);

        $this->assertCount(2, $payload['PaymentAccounts']);
        $this->assertSame('11101001', $payload['PaymentAccounts'][0]['AccountCode']);
        $this->assertSame(99950500.0, $payload['PaymentAccounts'][0]['SumPaid']);
        $this->assertArrayHasKey('Decription', $payload['PaymentAccounts'][0]);
        $this->assertSame('000H', $payload['PaymentAccounts'][0]['ProjectCode']);

        $this->assertSame('11101020', $payload['PaymentAccounts'][1]['AccountCode']);
        $this->assertSame(983500.0, $payload['PaymentAccounts'][1]['SumPaid']);

        $this->assertCount(1, $payload['PaymentChecks']);
        $this->assertSame('11201001', $payload['PaymentChecks'][0]['CheckAccount']);
        $this->assertSame('130552', $payload['PaymentChecks'][0]['CheckNumber']);
        $this->assertSame(100934000.0, $payload['PaymentChecks'][0]['CheckSum']);
        $this->assertSame('2026-08-12', $payload['PaymentChecks'][0]['DueDate']);
        $this->assertSame('IDR', $payload['PaymentChecks'][0]['Currency']);
        $this->assertSame('tYES', $payload['PaymentChecks'][0]['ManualCheck']);
    }

    public function test_validate_fails_when_bilyet_not_onhand(): void
    {
        $this->bilyet->update(['status' => 'cair']);

        $errors = $this->makeBuilder()->validate();

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('onhand', implode(' ', $errors));
    }

    public function test_validate_fails_when_bilyet_belongs_to_other_giro(): void
    {
        $otherGiro = Giro::query()->create([
            'acc_no' => '99999',
            'acc_name' => 'Other Bank',
            'bank_id' => $this->giro->bank_id,
            'type' => 'giro',
            'project' => '000H',
            'sap_account' => '11209999',
        ]);

        $builder = new SapGeneralOutgoingPaymentBuilder(
            $otherGiro,
            $this->bilyet,
            100934000,
            '2026-08-12',
            '000H',
            'Operational PC by Payreq & PMT BPJS TK',
            $this->destinationLines(),
        );

        $errors = $builder->validate();

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('giro', strtolower(implode(' ', $errors)));
    }

    public function test_validate_fails_when_destination_not_cash_or_missing_sap_account(): void
    {
        $bankAccount = Account::query()->create([
            'account_number' => '11201001',
            'account_name' => 'Bank GL',
            'type' => 'bank',
            'project' => '000H',
            'sap_account' => '11201001',
            'is_active' => true,
        ]);

        $builder = new SapGeneralOutgoingPaymentBuilder(
            $this->giro,
            $this->bilyet,
            100934000,
            '2026-08-12',
            '000H',
            'Operational PC by Payreq & PMT BPJS TK',
            [
                ['account' => $bankAccount, 'amount' => 100934000],
            ],
        );

        $errors = $builder->validate();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('cash', strtolower(implode(' ', $errors)));

        $noSapAccount = Account::query()->create([
            'account_number' => '11101999',
            'account_name' => 'Cash No SAP',
            'type' => 'cash',
            'project' => '000H',
            'sap_account' => null,
            'is_active' => true,
        ]);

        $builder = new SapGeneralOutgoingPaymentBuilder(
            $this->giro,
            $this->bilyet,
            100934000,
            '2026-08-12',
            '000H',
            'Operational PC by Payreq & PMT BPJS TK',
            [
                ['account' => $noSapAccount, 'amount' => 100934000],
            ],
        );

        $errors = $builder->validate();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('sap_account', strtolower(implode(' ', $errors)));
    }

    public function test_validate_fails_when_total_not_equal_amount(): void
    {
        $builder = new SapGeneralOutgoingPaymentBuilder(
            $this->giro,
            $this->bilyet,
            100934000,
            '2026-08-12',
            '000H',
            'Operational PC by Payreq & PMT BPJS TK',
            [
                ['account' => $this->cashAccountA, 'amount' => 50000000],
                ['account' => $this->cashAccountB, 'amount' => 983500],
            ],
        );

        $errors = $builder->validate();

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Total nominal', implode(' ', $errors));
    }

    public function test_validate_fails_when_destination_account_duplicated(): void
    {
        $builder = new SapGeneralOutgoingPaymentBuilder(
            $this->giro,
            $this->bilyet,
            100934000,
            '2026-08-12',
            '000H',
            'Operational PC by Payreq & PMT BPJS TK',
            [
                ['account' => $this->cashAccountA, 'amount' => 50000000],
                ['account' => $this->cashAccountA, 'amount' => 50934000],
            ],
        );

        $errors = $builder->validate();

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('duplikat', strtolower(implode(' ', $errors)));
    }

    /**
     * @return list<array{account: Account, amount: float|int, description?: string|null}>
     */
    private function destinationLines(): array
    {
        return [
            ['account' => $this->cashAccountA, 'amount' => 99950500, 'description' => 'Petty Cash line'],
            ['account' => $this->cashAccountB, 'amount' => 983500, 'description' => 'Intransit line'],
        ];
    }

    private function makeBuilder(): SapGeneralOutgoingPaymentBuilder
    {
        return new SapGeneralOutgoingPaymentBuilder(
            $this->giro,
            $this->bilyet,
            100934000,
            '2026-08-12',
            '000H',
            'Operational PC by Payreq & PMT BPJS TK',
            $this->destinationLines(),
            'Preparer',
            'Approver',
        );
    }
}
