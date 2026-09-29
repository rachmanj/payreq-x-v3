<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Faktur;
use App\Models\TaxPeriod;
use App\Models\User;
use App\Services\SapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PpnMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['view_tax_monitoring', 'manage_tax_monitoring', 'approve_tax_period'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    public function test_guest_cannot_access_ppn_dashboard(): void
    {
        $this->get(route('accounting.tax.ppn.index'))->assertRedirect('/login');
    }

    public function test_viewer_without_manage_cannot_reconcile(): void
    {
        $user = $this->userWithPermissions(['view_tax_monitoring']);

        $this->actingAs($user)
            ->post(route('accounting.tax.ppn.reconcile'), ['masa_pajak' => '2026-08'])
            ->assertForbidden();
    }

    public function test_reconcile_saves_snapshot_with_mocked_sap(): void
    {
        $manager = $this->userWithPermissions(['view_tax_monitoring', 'manage_tax_monitoring']);

        $sap = Mockery::mock(SapService::class);
        $sap->shouldReceive('fetchPurchaseInvoicesForDocDateRange')->andReturn([]);
        $sap->shouldReceive('fetchArInvoicesForDocDateRange')->andReturn([]);
        $this->app->instance(SapService::class, $sap);

        $this->actingAs($manager)
            ->post(route('accounting.tax.ppn.reconcile'), ['masa_pajak' => '2026-08'])
            ->assertRedirect(route('accounting.tax.ppn.index', ['masa_pajak' => '2026-08']));

        $period = TaxPeriod::query()->where('masa_pajak', '2026-08')->first();
        $this->assertNotNull($period);
        $this->assertNotNull($period->snapshot_json);
        $this->assertArrayHasKey('reconciled_at', $period->snapshot_json);
    }

    public function test_approve_and_close_require_approve_permission(): void
    {
        $manager = $this->userWithPermissions(['view_tax_monitoring', 'manage_tax_monitoring']);
        $approver = $this->userWithPermissions(['view_tax_monitoring', 'manage_tax_monitoring', 'approve_tax_period']);

        $period = TaxPeriod::query()->create([
            'masa_pajak' => '2026-08',
            'tax_type' => 'ppn',
            'status' => 'prepared',
            'prepared_by' => $manager->id,
            'prepared_at' => now(),
        ]);

        $this->actingAs($manager)
            ->post(route('accounting.tax.ppn.periods.approve', $period))
            ->assertForbidden();

        $this->actingAs($approver)
            ->post(route('accounting.tax.ppn.periods.approve', $period))
            ->assertRedirect();

        $period->refresh();
        $this->assertSame('approved', $period->status);
    }

    public function test_reopen_without_reason_fails_validation(): void
    {
        $approver = $this->userWithPermissions(['view_tax_monitoring', 'approve_tax_period']);
        $period = TaxPeriod::query()->create([
            'masa_pajak' => '2026-08',
            'tax_type' => 'ppn',
            'status' => 'locked',
        ]);

        $this->actingAs($approver)
            ->post(route('accounting.tax.ppn.periods.reopen', $period), ['reason' => 'abc'])
            ->assertSessionHasErrors('reason');
    }

    public function test_validate_masukan_updates_faktur(): void
    {
        $manager = $this->userWithPermissions(['view_tax_monitoring', 'manage_tax_monitoring']);
        $customer = Customer::query()->create(['code' => 'V1', 'name' => 'Vendor', 'type' => 'vendor']);
        $faktur = Faktur::query()->create([
            'customer_id' => $customer->id,
            'invoice_no' => 'AP-1',
            'invoice_date' => '2026-08-01',
            'type' => 'purchase',
            'masa_pajak' => '2026-08',
            'dpp' => 100,
            'ppn' => 11,
            'created_by' => $manager->id,
            'validation_status' => 'belum_diperiksa',
        ]);

        $this->actingAs($manager)
            ->post(route('accounting.tax.ppn.masukan.validate', $faktur), ['validation_status' => 'valid'])
            ->assertRedirect();

        $this->assertSame('valid', $faktur->fresh()->validation_status);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'ppn-test-'.uniqid(), 'guard_name' => 'web']);
        $role->syncPermissions($permissions);
        $user->assignRole($role);

        return $user;
    }
}
