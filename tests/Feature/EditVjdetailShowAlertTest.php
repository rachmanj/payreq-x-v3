<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VerificationJournal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EditVjdetailShowAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'akses_sap_sync'], ['guard_name' => 'web']);
        Role::query()->firstOrCreate(['name' => 'cashier'], ['guard_name' => 'web']);
    }

    public function test_edit_vjdetail_show_alert_dismisses_with_jquery_not_bootstrap_plugin(): void
    {
        $path = resource_path('views/accounting/sap-sync/edit-vjdetail/index.blade.php');
        $contents = file_get_contents($path);

        $this->assertIsString($contents);
        $this->assertMatchesRegularExpression(
            '/window\.showAlert\s*=\s*function\s*\([^)]*\)\s*\{/',
            $contents
        );

        $start = strpos($contents, 'window.showAlert = function');
        $this->assertNotFalse($start);
        $showAlertBlock = substr($contents, $start, 800);

        $this->assertStringContainsString('fadeOut', $showAlertBlock);
        $this->assertStringContainsString('remove()', $showAlertBlock);
        $this->assertStringContainsString(".find('.close').on('click'", $showAlertBlock);
        $this->assertStringNotContainsString(".alert('close')", $showAlertBlock);
    }

    public function test_edit_vjdetail_page_renders_for_authorized_user(): void
    {
        $user = User::factory()->create(['project' => '022C']);
        $user->assignRole('cashier');
        $user->givePermissionTo('akses_sap_sync');

        $journal = VerificationJournal::query()->create([
            'nomor' => 'VJ'.uniqid(),
            'date' => now()->toDateString(),
            'project' => '022C',
            'amount' => 1000,
            'created_by' => $user->id,
            'validation_status' => VerificationJournal::VALIDATION_REJECTED,
            'rejection_reason' => 'Fix account code',
            'sap_submission_attempts' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('accounting.sap-sync.edit_vjdetail_display', ['vj_id' => $journal->id]))
            ->assertOk()
            ->assertSee('alert-container', false);
    }
}
