<?php

namespace Tests\Feature;

use App\Models\BankReconciliation;
use App\Models\Giro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BankReconciliationExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'akses_koran'], ['guard_name' => 'web']);
        Role::query()->firstOrCreate(['name' => 'cashier'], ['guard_name' => 'web']);
    }

    protected function createPreparer(): User
    {
        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('cashier');
        $user->givePermissionTo('akses_koran');

        return $user;
    }

    protected function createReconciliation(User $preparer): BankReconciliation
    {
        $bankId = DB::table('banks')->insertGetId([
            'name' => 'BCA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $giro = Giro::query()->create([
            'acc_no' => '1234567890',
            'acc_name' => 'Test Account',
            'bank_id' => $bankId,
            'project' => '000H',
        ]);

        return BankReconciliation::query()->create([
            'giro_id' => $giro->id,
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

    public function test_export_downloads_excel_file(): void
    {
        Excel::fake();

        $preparer = $this->createPreparer();
        $reconciliation = $this->createReconciliation($preparer);

        $this->actingAs($preparer)
            ->get(route('cashier.bank-reconciliation.export', $reconciliation))
            ->assertOk();

        Excel::assertDownloaded('bank-reconciliation-'.$reconciliation->id.'-2026-02.xlsx');
    }

    public function test_show_page_includes_confirm_dialogs_for_reparse_and_fetch(): void
    {
        $preparer = $this->createPreparer();
        $reconciliation = $this->createReconciliation($preparer);
        $reconciliation->update([
            'source_mode' => BankReconciliation::SOURCE_AI,
            'dokumen_id' => null,
        ]);

        // Attach a dokumen so re-parse button is shown
        $dokumenId = DB::table('dokumens')->insertGetId([
            'filename1' => 'koran.pdf',
            'giro_id' => $reconciliation->giro_id,
            'type' => 'koran',
            'project' => '000H',
            'periode' => '2026-02-01',
            'created_by' => $preparer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $reconciliation->update(['dokumen_id' => $dokumenId]);

        $this->actingAs($preparer)
            ->get(route('cashier.bank-reconciliation.show', $reconciliation))
            ->assertOk()
            ->assertSee('Re-parse will replace all bank statement lines', false)
            ->assertSee('Fetch SAP will replace all SAP lines', false);
    }

    public function test_report_page_includes_export_excel_link(): void
    {
        $preparer = $this->createPreparer();
        $reconciliation = $this->createReconciliation($preparer);

        $this->actingAs($preparer)
            ->get(route('cashier.bank-reconciliation.report', $reconciliation))
            ->assertOk()
            ->assertSee(route('cashier.bank-reconciliation.export', $reconciliation), false)
            ->assertSee('Export Excel', false);
    }
}
