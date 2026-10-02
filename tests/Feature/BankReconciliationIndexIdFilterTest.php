<?php

namespace Tests\Feature;

use App\Models\BankReconciliation;
use App\Models\Giro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BankReconciliationIndexIdFilterTest extends TestCase
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

    protected function createElevatedViewer(): User
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
        $user->givePermissionTo(['akses_koran', 'validate_bank_reconciliation']);

        return $user;
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

    protected function createReconciliation(User $preparer, string $periode, ?string $validationStatus = null): BankReconciliation
    {
        $attributes = [
            'giro_id' => $this->createGiro()->id,
            'periode' => $periode,
            'source_mode' => BankReconciliation::SOURCE_MANUAL,
            'status' => BankReconciliation::STATUS_IN_REVIEW,
            'created_by' => $preparer->id,
        ];

        if ($validationStatus !== null) {
            $attributes['validation_status'] = $validationStatus;
            $attributes['submitted_by'] = $preparer->id;
            $attributes['submitted_at'] = now();
        }

        return BankReconciliation::query()->create($attributes);
    }

    public function test_index_lists_all_reconciliations_without_id_filter(): void
    {
        $preparer = $this->createElevatedViewer();
        $first = $this->createReconciliation($preparer, '2026-01-01');
        $second = $this->createReconciliation($preparer, '2026-02-01');

        $response = $this->actingAs($preparer)
            ->get(route('cashier.bank-reconciliation.index'))
            ->assertOk();

        $reconciliations = $response->viewData('reconciliations');
        $this->assertTrue($reconciliations->contains(fn ($row) => (int) $row->id === (int) $first->id));
        $this->assertTrue($reconciliations->contains(fn ($row) => (int) $row->id === (int) $second->id));
    }

    public function test_index_filters_by_exact_id(): void
    {
        $preparer = $this->createElevatedViewer();
        $first = $this->createReconciliation($preparer, '2026-01-01');
        $this->createReconciliation($preparer, '2026-02-01');

        $response = $this->actingAs($preparer)
            ->get(route('cashier.bank-reconciliation.index', ['id' => $first->id]))
            ->assertOk();

        $reconciliations = $response->viewData('reconciliations');
        $this->assertCount(1, $reconciliations);
        $this->assertSame((int) $first->id, (int) $reconciliations->first()->id);
    }

    public function test_pending_validation_view_works_together_with_id_filter(): void
    {
        $preparer = $this->createElevatedViewer();
        $validator = $this->createValidator();

        $pending = $this->createReconciliation(
            $preparer,
            '2026-03-01',
            BankReconciliation::VALIDATION_PENDING
        );
        $inReview = $this->createReconciliation($preparer, '2026-04-01');

        $response = $this->actingAs($validator)
            ->get(route('cashier.bank-reconciliation.index', [
                'view' => 'pending_validation',
                'id' => $pending->id,
            ]))
            ->assertOk();

        $reconciliations = $response->viewData('reconciliations');
        $this->assertCount(1, $reconciliations);
        $this->assertSame((int) $pending->id, (int) $reconciliations->first()->id);

        $emptyResponse = $this->actingAs($validator)
            ->get(route('cashier.bank-reconciliation.index', [
                'view' => 'pending_validation',
                'id' => $inReview->id,
            ]))
            ->assertOk();

        $this->assertCount(0, $emptyResponse->viewData('reconciliations'));
    }
}
