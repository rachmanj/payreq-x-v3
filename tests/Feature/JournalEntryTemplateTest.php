<?php

namespace Tests\Feature;

use App\Models\JournalEntryTemplate;
use App\Models\JournalEntryTemplateLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JournalEntryTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'create_manual_journal_entry'], ['guard_name' => 'web']);
        Role::query()->firstOrCreate(['name' => 'admin'], ['guard_name' => 'web']);
    }

    protected function authorizedUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $user->givePermissionTo('create_manual_journal_entry');

        return $user;
    }

    protected function templateLines(): array
    {
        return [
            [
                'account_code' => '11001',
                'debit_credit' => 'debit',
                'default_amount' => null,
                'project' => '000H',
                'cost_center' => 'FIN',
                'description' => 'Accrual debit',
            ],
            [
                'account_code' => '21001',
                'debit_credit' => 'credit',
                'default_amount' => null,
                'project' => '000H',
                'cost_center' => 'FIN',
                'description' => 'Accrual credit',
            ],
        ];
    }

    public function test_store_creates_template_with_lines(): void
    {
        $user = $this->authorizedUser();

        $this->actingAs($user)
            ->post(route('accounting.journal-entries.templates.store'), [
                'name' => 'Monthly Accrual',
                'description' => 'Standard accrual entry',
                'lines' => $this->templateLines(),
            ])
            ->assertRedirect(route('accounting.journal-entries.templates.index'))
            ->assertSessionHas('success');

        $template = JournalEntryTemplate::where('name', 'Monthly Accrual')->first();
        $this->assertNotNull($template);
        $this->assertEquals(2, $template->lines()->count());
    }

    public function test_lines_endpoint_returns_template_lines_json(): void
    {
        $user = $this->authorizedUser();
        $template = JournalEntryTemplate::factory()->create(['created_by' => $user->id]);

        JournalEntryTemplateLine::factory()->create([
            'journal_entry_template_id' => $template->id,
            'line_no' => 1,
            'account_code' => '11001',
            'debit_credit' => 'debit',
            'project' => '000H',
            'cost_center' => 'FIN',
            'description' => 'Line 1',
        ]);

        JournalEntryTemplateLine::factory()->create([
            'journal_entry_template_id' => $template->id,
            'line_no' => 2,
            'account_code' => '21001',
            'debit_credit' => 'credit',
            'project' => '000H',
            'cost_center' => 'FIN',
            'description' => 'Line 2',
        ]);

        $this->actingAs($user)
            ->getJson(route('accounting.journal-entries.templates.lines', $template->id))
            ->assertOk()
            ->assertJsonPath('name', $template->name)
            ->assertJsonCount(2, 'lines')
            ->assertJsonPath('lines.0.account_code', '11001');
    }

    public function test_update_template_replaces_lines(): void
    {
        $user = $this->authorizedUser();
        $template = JournalEntryTemplate::factory()->create(['created_by' => $user->id, 'name' => 'Old Name']);
        JournalEntryTemplateLine::factory()->create([
            'journal_entry_template_id' => $template->id,
            'line_no' => 1,
        ]);

        $this->actingAs($user)
            ->put(route('accounting.journal-entries.templates.update', $template->id), [
                'name' => 'Updated Name',
                'description' => 'Updated desc',
                'lines' => $this->templateLines(),
            ])
            ->assertRedirect(route('accounting.journal-entries.templates.index'));

        $template->refresh();
        $this->assertEquals('Updated Name', $template->name);
        $this->assertEquals(2, $template->lines()->count());
    }

    public function test_destroy_deletes_template(): void
    {
        $user = $this->authorizedUser();
        $template = JournalEntryTemplate::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->delete(route('accounting.journal-entries.templates.destroy', $template->id))
            ->assertRedirect(route('accounting.journal-entries.templates.index'));

        $this->assertDatabaseMissing('journal_entry_templates', ['id' => $template->id]);
    }
}
