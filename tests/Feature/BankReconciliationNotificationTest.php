<?php

namespace Tests\Feature;

use App\Models\BankReconciliation;
use App\Models\Giro;
use App\Models\User;
use App\Notifications\BankReconciliationRejectedNotification;
use App\Notifications\BankReconciliationSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BankReconciliationNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'akses_koran'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'validate_bank_reconciliation'], ['guard_name' => 'web']);
        Role::query()->firstOrCreate(['name' => 'cashier'], ['guard_name' => 'web']);
        Role::query()->firstOrCreate(['name' => 'admin'], ['guard_name' => 'web']);
    }

    protected function createGiro(): Giro
    {
        $bankId = DB::table('banks')->insertGetId([
            'name' => 'BCA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Giro::query()->create([
            'acc_no' => '1234567890',
            'acc_name' => 'Test Account',
            'bank_id' => $bankId,
            'project' => '000H',
        ]);
    }

    protected function createPreparer(): User
    {
        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('cashier');
        $user->givePermissionTo('akses_koran');

        return $user;
    }

    protected function createValidator(): User
    {
        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('admin');
        $user->givePermissionTo(['validate_bank_reconciliation', 'akses_koran']);

        return $user;
    }

    protected function createBalancedReconciliation(User $preparer): BankReconciliation
    {
        return BankReconciliation::query()->create([
            'giro_id' => $this->createGiro()->id,
            'periode' => '2026-02-01',
            'source_mode' => BankReconciliation::SOURCE_MANUAL,
            'status' => BankReconciliation::STATUS_IN_REVIEW,
            'created_by' => $preparer->id,
            'opening_balance_bank' => '1000.00',
            'closing_balance_bank' => '1000.00',
            'opening_balance_book' => '1000.00',
            'closing_balance_book' => '1000.00',
        ]);
    }

    public function test_submit_notifies_validators(): void
    {
        Notification::fake();

        $preparer = $this->createPreparer();
        $validator = $this->createValidator();
        $reconciliation = $this->createBalancedReconciliation($preparer);

        $this->actingAs($preparer)
            ->post(route('cashier.bank-reconciliation.submit', $reconciliation))
            ->assertRedirect()
            ->assertSessionHas('success');

        Notification::assertSentTo(
            $validator,
            BankReconciliationSubmittedNotification::class
        );
        Notification::assertNotSentTo(
            $preparer,
            BankReconciliationSubmittedNotification::class
        );
    }

    public function test_reject_notifies_preparer(): void
    {
        Notification::fake();

        $preparer = $this->createPreparer();
        $validator = $this->createValidator();
        $reconciliation = $this->createBalancedReconciliation($preparer);
        $reconciliation->update([
            'validation_status' => BankReconciliation::VALIDATION_PENDING,
            'submitted_by' => $preparer->id,
            'submitted_at' => now(),
        ]);

        $this->actingAs($validator)
            ->post(route('cashier.bank-reconciliation.reject', $reconciliation), [
                'rejection_reason' => 'Please explain outstanding items',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Notification::assertSentTo(
            $preparer,
            BankReconciliationRejectedNotification::class,
            function (BankReconciliationRejectedNotification $notification) {
                return $notification->rejectionReason === 'Please explain outstanding items';
            }
        );
        Notification::assertNotSentTo(
            $validator,
            BankReconciliationRejectedNotification::class
        );
    }
}
