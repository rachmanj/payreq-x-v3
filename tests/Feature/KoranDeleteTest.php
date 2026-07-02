<?php

namespace Tests\Feature;

use App\Models\BankReconciliation;
use App\Models\Dokumen;
use App\Models\Giro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KoranDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'delete_koran'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'akses_koran'], ['guard_name' => 'web']);

        Role::query()->firstOrCreate(['name' => 'cashier'], ['guard_name' => 'web']);
        Role::query()->firstOrCreate(['name' => 'user'], ['guard_name' => 'web']);
    }

    protected function createGiro(array $overrides = []): Giro
    {
        $bankId = \Illuminate\Support\Facades\DB::table('banks')->insertGetId([
            'name' => 'BCA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Giro::query()->create(array_merge([
            'acc_no' => '1234567890',
            'acc_name' => 'Test Account',
            'bank_id' => $bankId,
            'project' => '000H',
        ], $overrides));
    }

    protected function createDokumen(Giro $giro, User $user, string $filename = 'koran_1234567890_999.pdf'): Dokumen
    {
        if (! is_dir(public_path('dokumens'))) {
            mkdir(public_path('dokumens'), 0755, true);
        }

        file_put_contents(public_path('dokumens/'.$filename), '%PDF-1.4 test');

        return Dokumen::query()->create([
            'giro_id' => $giro->id,
            'type' => 'koran',
            'project' => $giro->project,
            'periode' => '2026-05-01',
            'filename1' => $filename,
            'created_by' => $user->id,
        ]);
    }

    protected function createCashierWithDelete(): User
    {
        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('cashier');
        $user->givePermissionTo(['delete_koran', 'akses_koran']);

        return $user;
    }

    public function test_delete_requires_delete_koran_permission(): void
    {
        $giro = $this->createGiro();
        $owner = User::factory()->create(['project' => '000H']);
        $dokumen = $this->createDokumen($giro, $owner);

        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('user');

        $this->actingAs($user)
            ->delete(route('cashier.koran.destroy', $dokumen))
            ->assertForbidden();

        $this->assertDatabaseHas('dokumens', ['id' => $dokumen->id]);
    }

    public function test_non_elevated_user_cannot_delete_other_project_koran(): void
    {
        $giro = $this->createGiro(['project' => '021C']);
        $owner = User::factory()->create(['project' => '021C']);
        $dokumen = $this->createDokumen($giro, $owner);

        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('user');
        $user->givePermissionTo('delete_koran');

        $this->actingAs($user)
            ->delete(route('cashier.koran.destroy', $dokumen))
            ->assertForbidden();

        $this->assertDatabaseHas('dokumens', ['id' => $dokumen->id]);
    }

    public function test_elevated_cashier_can_delete_any_project_koran(): void
    {
        $giro = $this->createGiro(['project' => '021C']);
        $owner = User::factory()->create(['project' => '021C']);
        $dokumen = $this->createDokumen($giro, $owner, 'koran_1234567890_delete_me.pdf');

        $cashier = $this->createCashierWithDelete();

        $this->actingAs($cashier)
            ->delete(route('cashier.koran.destroy', $dokumen))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('dokumens', ['id' => $dokumen->id]);
        $this->assertFileDoesNotExist(public_path('dokumens/koran_1234567890_delete_me.pdf'));
    }

    public function test_delete_blocked_when_reconciliation_is_locked(): void
    {
        $giro = $this->createGiro();
        $owner = User::factory()->create(['project' => '000H']);
        $dokumen = $this->createDokumen($giro, $owner, 'koran_locked.pdf');
        $cashier = $this->createCashierWithDelete();

        BankReconciliation::query()->create([
            'giro_id' => $giro->id,
            'dokumen_id' => $dokumen->id,
            'periode' => '2026-05-01',
            'source_mode' => BankReconciliation::SOURCE_MANUAL,
            'status' => BankReconciliation::STATUS_IN_REVIEW,
            'validation_status' => BankReconciliation::VALIDATION_PENDING,
            'created_by' => $owner->id,
            'submitted_by' => $owner->id,
            'submitted_at' => now(),
        ]);

        $this->actingAs($cashier)
            ->delete(route('cashier.koran.destroy', $dokumen))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('dokumens', ['id' => $dokumen->id]);
        $this->assertFileExists(public_path('dokumens/koran_locked.pdf'));
    }

    public function test_delete_succeeds_and_removes_file_when_reconciliation_not_locked(): void
    {
        $giro = $this->createGiro();
        $owner = User::factory()->create(['project' => '000H']);
        $dokumen = $this->createDokumen($giro, $owner, 'koran_removable.pdf');
        $cashier = $this->createCashierWithDelete();

        BankReconciliation::query()->create([
            'giro_id' => $giro->id,
            'dokumen_id' => $dokumen->id,
            'periode' => '2026-05-01',
            'source_mode' => BankReconciliation::SOURCE_MANUAL,
            'status' => BankReconciliation::STATUS_IN_REVIEW,
            'validation_status' => BankReconciliation::VALIDATION_REJECTED,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($cashier)
            ->delete(route('cashier.koran.destroy', $dokumen))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('dokumens', ['id' => $dokumen->id]);
        $this->assertFileDoesNotExist(public_path('dokumens/koran_removable.pdf'));
    }
}
