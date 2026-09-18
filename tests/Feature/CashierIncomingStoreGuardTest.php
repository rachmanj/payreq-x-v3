<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Incoming;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CashierIncomingStoreGuardTest extends TestCase
{
    use RefreshDatabase;

    private const PROJECT = '026C';

    private const AMOUNT = 5000000;

    protected function setUp(): void
    {
        parent::setUp();

        Role::query()->firstOrCreate(['name' => 'cashier'], ['guard_name' => 'web']);
    }

    private function createCashier(): User
    {
        $user = User::factory()->create(['project' => self::PROJECT]);
        $user->assignRole('cashier');

        return $user;
    }

    private function createCashAccount(): Account
    {
        return Account::query()->create([
            'type' => 'cash',
            'account_number' => '11010101',
            'account_name' => 'Cash Account',
            'project' => self::PROJECT,
            'app_balance' => 10000000,
            'is_active' => true,
        ]);
    }

    private function createAutomaticIncoming(array $overrides = []): Incoming
    {
        return Incoming::query()->create(array_merge([
            'nomor' => '26062500092',
            'cashier_id' => null,
            'description' => 'Bank Transaction: 26062500092 - Penarikan kas',
            'amount' => self::AMOUNT,
            'project' => self::PROJECT,
            'will_post' => true,
        ], $overrides));
    }

    private function storeManualIncoming(User $user): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($user)
            ->from(route('cashier.incomings.create'))
            ->post(route('cashier.incomings.store'), [
                'description' => 'Penarikan manual',
                'amount' => self::AMOUNT,
            ]);
    }

    public function test_store_rejected_when_unreceived_automatic_incoming_with_same_amount_exists(): void
    {
        Carbon::setTestNow('2026-09-18 10:00:00');

        $user = $this->createCashier();
        $this->createAutomaticIncoming(['created_at' => '2026-09-10 08:00:00']);

        $countBefore = Incoming::query()->count();

        $this->storeManualIncoming($user)
            ->assertRedirect(route('cashier.incomings.create'))
            ->assertSessionHas('error', function (string $message): bool {
                return str_contains($message, '26062500092')
                    && str_contains($message, '10 Sep 2026')
                    && str_contains($message, 'belum di-receive');
            });

        $this->assertSame($countBefore, Incoming::query()->count());

        Carbon::setTestNow();
    }

    public function test_store_succeeds_when_no_similar_automatic_incoming_exists(): void
    {
        $user = $this->createCashier();
        $countBefore = Incoming::query()->count();

        $this->storeManualIncoming($user)
            ->assertRedirect(route('cashier.incomings.index'))
            ->assertSessionHas('success');

        $this->assertSame($countBefore + 1, Incoming::query()->count());
        $this->assertDatabaseHas('incomings', [
            'description' => 'Penarikan manual',
            'amount' => self::AMOUNT,
            'project' => self::PROJECT,
            'nomor' => null,
        ]);
    }

    public function test_store_succeeds_when_automatic_incoming_with_same_amount_already_received(): void
    {
        Carbon::setTestNow('2026-09-18 10:00:00');

        $user = $this->createCashier();
        $account = $this->createCashAccount();
        $automaticIncoming = $this->createAutomaticIncoming(['created_at' => '2026-09-10 08:00:00']);

        $transaksi = new Transaksi;
        $transaksi->account_id = $account->id;
        $transaksi->document_id = $automaticIncoming->id;
        $transaksi->document_type = 'incoming';
        $transaksi->posting_date = '2026-09-10';
        $transaksi->description = $automaticIncoming->description;
        $transaksi->debit = self::AMOUNT;
        $transaksi->balance = self::AMOUNT;
        $transaksi->save();

        $countBefore = Incoming::query()->count();

        $this->storeManualIncoming($user)
            ->assertRedirect(route('cashier.incomings.index'))
            ->assertSessionHas('success');

        $this->assertSame($countBefore + 1, Incoming::query()->count());

        Carbon::setTestNow();
    }

    public function test_store_succeeds_when_similar_automatic_incoming_is_older_than_14_days(): void
    {
        Carbon::setTestNow('2026-09-18 10:00:00');

        $user = $this->createCashier();
        $this->createAutomaticIncoming(['created_at' => '2026-09-03 08:00:00']);

        $countBefore = Incoming::query()->count();

        $this->storeManualIncoming($user)
            ->assertRedirect(route('cashier.incomings.index'))
            ->assertSessionHas('success');

        $this->assertSame($countBefore + 1, Incoming::query()->count());

        Carbon::setTestNow();
    }
}
