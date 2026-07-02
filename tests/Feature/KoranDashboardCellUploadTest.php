<?php

namespace Tests\Feature;

use App\Models\Dokumen;
use App\Models\Giro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KoranDashboardCellUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'upload_koran'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'akses_koran'], ['guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'delete_koran'], ['guard_name' => 'web']);

        Role::query()->firstOrCreate(['name' => 'cashier'], ['guard_name' => 'web']);
    }

    protected function createGiro(array $overrides = []): Giro
    {
        $bankId = \Illuminate\Support\Facades\DB::table('banks')->insertGetId([
            'name' => 'BCA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Giro::query()->create(array_merge([
            'acc_no' => '703638126',
            'acc_name' => 'Test Operating Account',
            'bank_id' => $bankId,
            'project' => '000H',
        ], $overrides));
    }

    protected function createUploader(): User
    {
        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('cashier');
        $user->givePermissionTo(['upload_koran', 'akses_koran']);

        return $user;
    }

    public function test_dashboard_shows_clickable_cells_for_missing_and_uploaded_months(): void
    {
        $user = $this->createUploader();
        $giro = $this->createGiro();

        Dokumen::query()->create([
            'giro_id' => $giro->id,
            'type' => 'koran',
            'project' => $giro->project,
            'periode' => '2026-06-01',
            'filename1' => 'koran_703638126_12345.pdf',
            'created_by' => $user->id,
        ]);

        if (! is_dir(public_path('dokumens'))) {
            mkdir(public_path('dokumens'), 0755, true);
        }

        $this->actingAs($user)
            ->get(route('cashier.koran.index', ['page' => 'dashboard', 'year' => 2026]))
            ->assertOk()
            ->assertSee('koran-cell-trigger', false)
            ->assertSee('koran-cell-uploaded', false)
            ->assertSee('koran-cell-missing', false)
            ->assertSee('koran-cell-modal', false)
            ->assertSee($giro->acc_no, false);
    }

    public function test_upload_from_dashboard_prefilled_cell_succeeds_with_account_in_filename(): void
    {
        $user = $this->createUploader();
        $giro = $this->createGiro(['acc_no' => '703638126']);

        if (! is_dir(public_path('dokumens'))) {
            mkdir(public_path('dokumens'), 0755, true);
        }

        $response = $this->actingAs($user)
            ->post(route('cashier.koran.upload'), [
                'giro_id' => $giro->id,
                'periode' => '2026-03',
                'file_upload' => UploadedFile::fake()->create('statement.pdf', 100, 'application/pdf'),
                'remarks' => 'From dashboard cell',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $dokumen = Dokumen::query()->where('giro_id', $giro->id)->first();
        $this->assertNotNull($dokumen);
        $this->assertSame('koran', $dokumen->type);
        $this->assertSame('2026-03-01', $dokumen->getRawOriginal('periode'));
        $this->assertStringContainsString('koran_703638126_', $dokumen->getRawOriginal('filename1'));
        $this->assertFileExists(public_path('dokumens/'.$dokumen->getRawOriginal('filename1')));
    }

    public function test_duplicate_upload_for_same_giro_and_month_is_rejected(): void
    {
        $user = $this->createUploader();
        $giro = $this->createGiro();

        Dokumen::query()->create([
            'giro_id' => $giro->id,
            'type' => 'koran',
            'project' => $giro->project,
            'periode' => '2026-03-01',
            'filename1' => 'koran_existing.pdf',
            'created_by' => $user->id,
        ]);

        if (! is_dir(public_path('dokumens'))) {
            mkdir(public_path('dokumens'), 0755, true);
        }

        $response = $this->actingAs($user)
            ->post(route('cashier.koran.upload'), [
                'giro_id' => $giro->id,
                'periode' => '2026-03',
                'file_upload' => UploadedFile::fake()->create('statement.pdf', 100, 'application/pdf'),
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('periode');
        $this->assertSame(1, Dokumen::query()->where('giro_id', $giro->id)->count());
    }

    public function test_dashboard_disables_delete_hint_when_reconciliation_is_completed(): void
    {
        $user = $this->createUploader();
        $user->givePermissionTo('delete_koran');
        $giro = $this->createGiro();

        $dokumen = Dokumen::query()->create([
            'giro_id' => $giro->id,
            'type' => 'koran',
            'project' => $giro->project,
            'periode' => '2026-06-01',
            'filename1' => 'koran_703638126_12345.pdf',
            'created_by' => $user->id,
        ]);

        \App\Models\BankReconciliation::query()->create([
            'giro_id' => $giro->id,
            'dokumen_id' => $dokumen->id,
            'periode' => '2026-06-01',
            'source_mode' => \App\Models\BankReconciliation::SOURCE_MANUAL,
            'status' => \App\Models\BankReconciliation::STATUS_COMPLETED,
            'validation_status' => \App\Models\BankReconciliation::VALIDATION_VALIDATED,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('cashier.koran.index', ['page' => 'dashboard', 'year' => 2026]))
            ->assertOk()
            ->assertSee('data-reconciliation-locked="1"', false)
            ->assertSee('koran-modal-delete-locked-notice', false);
    }
}
