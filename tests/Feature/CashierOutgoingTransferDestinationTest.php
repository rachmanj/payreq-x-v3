<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Outgoing;
use App\Models\Payreq;
use App\Models\TransferAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CashierOutgoingTransferDestinationTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    private TransferAccount $transferAccount;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::query()->firstOrCreate(['name' => 'akses_transaksi_cashier'], ['guard_name' => 'web']);
        Role::query()->firstOrCreate(['name' => 'superadmin'], ['guard_name' => 'web']);

        $this->cashier = User::factory()->create(['project' => '000H']);
        $this->cashier->assignRole('superadmin');
        $this->cashier->givePermissionTo('akses_transaksi_cashier');

        $requestor = User::factory()->create(['project' => '000H']);

        $bankId = DB::table('banks')->insertGetId([
            'name' => 'BCA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->transferAccount = TransferAccount::create([
            'user_id' => $requestor->id,
            'bank_id' => $bankId,
            'account_number' => '1234567890',
            'account_name' => 'John Doe',
            'label' => 'Rekening Utama',
        ]);

        $bankAccount = Account::create([
            'type' => 'bank',
            'account_number' => '11020101',
            'account_name' => 'Bank Account',
            'project' => '000H',
            'app_balance' => 100000000,
            'is_active' => true,
        ]);

        $transferPayreq = Payreq::create([
            'user_id' => $requestor->id,
            'nomor' => 'PR-TRANSFER-DEST',
            'type' => 'advance',
            'amount' => 500000,
            'remarks' => 'Transfer outgoing destination test',
            'project' => '000H',
            'status' => 'paid',
            'payment_method' => 'transfer',
            'transfer_account_id' => $this->transferAccount->id,
            'editable' => 0,
            'deletable' => 0,
        ]);

        $cashPayreq = Payreq::create([
            'user_id' => $requestor->id,
            'nomor' => 'PR-CASH-DEST',
            'type' => 'advance',
            'amount' => 300000,
            'remarks' => 'Cash outgoing destination test',
            'project' => '000H',
            'status' => 'paid',
            'payment_method' => 'cash',
            'editable' => 0,
            'deletable' => 0,
        ]);

        Outgoing::create([
            'payreq_id' => $transferPayreq->id,
            'cashier_id' => $this->cashier->id,
            'account_id' => $bankAccount->id,
            'amount' => 500000,
            'project' => '000H',
            'outgoing_date' => now()->toDateString(),
            'payment_method' => 'transfer',
            'transfer_account_id' => $this->transferAccount->id,
        ]);

        Outgoing::create([
            'payreq_id' => $cashPayreq->id,
            'cashier_id' => $this->cashier->id,
            'account_id' => $bankAccount->id,
            'amount' => 300000,
            'project' => '000H',
            'outgoing_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'transfer_account_id' => null,
        ]);
    }

    public function test_outgoings_data_returns_transfer_destination_labels(): void
    {
        $response = $this->actingAs($this->cashier)
            ->get(route('cashier.outgoings.data'))
            ->assertOk();

        $rows = collect($response->json('data'));

        $this->assertGreaterThanOrEqual(2, $rows->count());

        $transferRow = $rows->first(fn (array $row) => str_contains($row['transfer_destination'], 'Rekening Utama'));
        $cashRow = $rows->first(fn (array $row) => str_contains($row['transfer_destination'], 'text-muted'));

        $this->assertNotNull($transferRow, 'Baris outgoing transfer dengan rekening tujuan tidak ditemukan.');
        $this->assertSame($this->transferAccount->displayLabel, strip_tags($transferRow['transfer_destination']));

        $this->assertNotNull($cashRow, 'Baris outgoing cash tanpa rekening tujuan tidak ditemukan.');
        $this->assertSame('-', trim(strip_tags($cashRow['transfer_destination'])));
    }
}
