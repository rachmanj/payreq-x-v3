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

    public function test_store_rejects_line_without_project(): void
    {
        $user = $this->authorizedUser();
        $lines = $this->templateLines();
        unset($lines[0]['project']);

        $this->actingAs($user)
            ->from(route('accounting.journal-entries.templates.create'))
            ->post(route('accounting.journal-entries.templates.store'), [
                'name' => 'Invalid Template',
                'description' => null,
                'lines' => $lines,
            ])
            ->assertSessionHasErrors(['lines.0.project']);

        $this->assertDatabaseMissing('journal_entry_templates', ['name' => 'Invalid Template']);
    }

    public function test_store_rejects_line_without_cost_center(): void
    {
        $user = $this->authorizedUser();
        $lines = $this->templateLines();
        $lines[0]['cost_center'] = '';

        $this->actingAs($user)
            ->from(route('accounting.journal-entries.templates.create'))
            ->post(route('accounting.journal-entries.templates.store'), [
                'name' => 'Missing CC Template',
                'lines' => $lines,
            ])
            ->assertSessionHasErrors(['lines.0.cost_center']);

        $this->assertDatabaseMissing('journal_entry_templates', ['name' => 'Missing CC Template']);
    }

    public function test_store_rejects_whitespace_only_project_and_cost_center(): void
    {
        $user = $this->authorizedUser();
        $lines = $this->templateLines();
        $lines[0]['project'] = '   ';
        $lines[1]['cost_center'] = '   ';

        $this->actingAs($user)
            ->from(route('accounting.journal-entries.templates.create'))
            ->post(route('accounting.journal-entries.templates.store'), [
                'name' => 'Whitespace Template',
                'lines' => $lines,
            ])
            ->assertSessionHasErrors(['lines.0.project', 'lines.1.cost_center']);

        $this->assertDatabaseMissing('journal_entry_templates', ['name' => 'Whitespace Template']);
    }

    public function test_store_persists_project_and_cost_center_on_all_lines(): void
    {
        $user = $this->authorizedUser();
        $lines = $this->templateLines();
        $lines[0]['project'] = 'APS';
        $lines[0]['cost_center'] = 'OPS';
        $lines[1]['project'] = '000H';
        $lines[1]['cost_center'] = 'FIN';

        $this->actingAs($user)
            ->post(route('accounting.journal-entries.templates.store'), [
                'name' => 'Dimension Template',
                'lines' => $lines,
            ])
            ->assertRedirect(route('accounting.journal-entries.templates.index'));

        $template = JournalEntryTemplate::where('name', 'Dimension Template')->first();
        $this->assertNotNull($template);

        $storedLines = $template->lines()->orderBy('line_no')->get();
        $this->assertSame('APS', $storedLines[0]->project);
        $this->assertSame('OPS', $storedLines[0]->cost_center);
        $this->assertSame('000H', $storedLines[1]->project);
        $this->assertSame('FIN', $storedLines[1]->cost_center);
    }

    public function test_update_rejects_lines_without_dimensions(): void
    {
        $user = $this->authorizedUser();
        $template = JournalEntryTemplate::factory()->create(['created_by' => $user->id, 'name' => 'Existing']);
        JournalEntryTemplateLine::factory()->create([
            'journal_entry_template_id' => $template->id,
            'line_no' => 1,
            'project' => '000H',
            'cost_center' => 'FIN',
        ]);
        JournalEntryTemplateLine::factory()->create([
            'journal_entry_template_id' => $template->id,
            'line_no' => 2,
            'debit_credit' => 'credit',
            'project' => '000H',
            'cost_center' => 'FIN',
        ]);

        $lines = $this->templateLines();
        $lines[0]['project'] = '';

        $this->actingAs($user)
            ->from(route('accounting.journal-entries.templates.edit', $template->id))
            ->put(route('accounting.journal-entries.templates.update', $template->id), [
                'name' => 'Existing',
                'lines' => $lines,
            ])
            ->assertSessionHasErrors(['lines.0.project']);

        $this->assertSame('000H', $template->fresh()->lines()->orderBy('line_no')->first()->project);
    }

    public function test_create_and_edit_forms_mark_project_and_cost_center_as_required(): void
    {
        $user = $this->authorizedUser();

        $this->actingAs($user)
            ->get(route('accounting.journal-entries.templates.create'))
            ->assertOk()
            ->assertSee('class="form-control form-control-sm line-project" required', false)
            ->assertSee('class="form-control form-control-sm line-cost-center" required', false);

        $template = JournalEntryTemplate::factory()->create(['created_by' => $user->id]);
        JournalEntryTemplateLine::factory()->create([
            'journal_entry_template_id' => $template->id,
            'line_no' => 1,
            'project' => '000H',
            'cost_center' => 'FIN',
        ]);
        JournalEntryTemplateLine::factory()->create([
            'journal_entry_template_id' => $template->id,
            'line_no' => 2,
            'debit_credit' => 'credit',
            'project' => '000H',
            'cost_center' => 'FIN',
        ]);

        $this->actingAs($user)
            ->get(route('accounting.journal-entries.templates.edit', $template->id))
            ->assertOk()
            ->assertSee('line-project" required', false)
            ->assertSee('line-cost-center" required', false);
    }
}
