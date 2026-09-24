<?php

namespace Tests\Feature;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JournalEntryRequiredDimensionsTest extends TestCase
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

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function balancedLinesWithDimensions(): array
    {
        return [
            [
                'account_code' => '11001',
                'debit_credit' => 'debit',
                'amount' => 1000,
                'project' => '000H',
                'cost_center' => 'FIN',
                'description' => 'Debit line',
            ],
            [
                'account_code' => '21001',
                'debit_credit' => 'credit',
                'amount' => 1000,
                'project' => 'APS',
                'cost_center' => 'OPS',
                'description' => 'Credit line',
            ],
        ];
    }

    public function test_store_rejects_line_without_project(): void
    {
        $user = $this->authorizedUser();
        $lines = $this->balancedLinesWithDimensions();
        unset($lines[0]['project']);

        $this->actingAs($user)
            ->from(route('accounting.journal-entries.create'))
            ->post(route('accounting.journal-entries.store'), [
                'date' => now()->toDateString(),
                'memo' => 'Missing project',
                'lines' => $lines,
            ])
            ->assertSessionHasErrors(['lines.0.project']);

        $this->assertDatabaseCount('journal_entries', 0);
    }

    public function test_store_rejects_line_without_cost_center(): void
    {
        $user = $this->authorizedUser();
        $lines = $this->balancedLinesWithDimensions();
        $lines[0]['cost_center'] = '';

        $this->actingAs($user)
            ->from(route('accounting.journal-entries.create'))
            ->post(route('accounting.journal-entries.store'), [
                'date' => now()->toDateString(),
                'memo' => 'Missing cost center',
                'lines' => $lines,
            ])
            ->assertSessionHasErrors(['lines.0.cost_center']);

        $this->assertDatabaseCount('journal_entries', 0);
    }

    public function test_store_rejects_whitespace_only_project_and_cost_center(): void
    {
        $user = $this->authorizedUser();
        $lines = $this->balancedLinesWithDimensions();
        $lines[0]['project'] = '   ';
        $lines[1]['cost_center'] = '   ';

        $response = $this->actingAs($user)
            ->from(route('accounting.journal-entries.create'))
            ->post(route('accounting.journal-entries.store'), [
                'date' => now()->toDateString(),
                'memo' => 'Whitespace dimensions',
                'lines' => $lines,
            ]);

        $response->assertSessionHasErrors(['lines.0.project', 'lines.1.cost_center']);
        $this->assertDatabaseCount('journal_entries', 0);
    }

    public function test_store_persists_project_and_cost_center_on_all_lines(): void
    {
        $user = $this->authorizedUser();
        $lines = $this->balancedLinesWithDimensions();

        $response = $this->actingAs($user)
            ->post(route('accounting.journal-entries.store'), [
                'date' => now()->toDateString(),
                'memo' => 'With dimensions',
                'lines' => $lines,
            ]);

        $entry = JournalEntry::first();
        $this->assertNotNull($entry);
        $response->assertRedirect(route('accounting.journal-entries.show', $entry->id));

        $storedLines = JournalEntryLine::where('journal_entry_id', $entry->id)->orderBy('line_no')->get();
        $this->assertCount(2, $storedLines);
        $this->assertSame('000H', $storedLines[0]->project);
        $this->assertSame('FIN', $storedLines[0]->cost_center);
        $this->assertSame('APS', $storedLines[1]->project);
        $this->assertSame('OPS', $storedLines[1]->cost_center);
    }

    public function test_update_rejects_lines_without_dimensions(): void
    {
        $user = $this->authorizedUser();
        $entry = JournalEntry::factory()->create(['created_by' => $user->id]);
        JournalEntryLine::factory()->create([
            'journal_entry_id' => $entry->id,
            'line_no' => 1,
            'account_code' => '11001',
            'debit_credit' => 'debit',
            'amount' => 1000,
            'project' => '000H',
            'cost_center' => 'FIN',
        ]);
        JournalEntryLine::factory()->create([
            'journal_entry_id' => $entry->id,
            'line_no' => 2,
            'account_code' => '21001',
            'debit_credit' => 'credit',
            'amount' => 1000,
            'project' => '000H',
            'cost_center' => 'FIN',
        ]);

        $lines = $this->balancedLinesWithDimensions();
        $lines[0]['project'] = '';

        $this->actingAs($user)
            ->from(route('accounting.journal-entries.edit', $entry->id))
            ->put(route('accounting.journal-entries.update', $entry->id), [
                'date' => now()->toDateString(),
                'memo' => 'Update missing project',
                'lines' => $lines,
            ])
            ->assertSessionHasErrors(['lines.0.project']);

        $this->assertSame('000H', $entry->fresh()->lines()->orderBy('line_no')->first()->project);
    }

    public function test_update_accepts_lines_with_dimensions(): void
    {
        $user = $this->authorizedUser();
        $entry = JournalEntry::factory()->create(['created_by' => $user->id]);
        JournalEntryLine::factory()->create([
            'journal_entry_id' => $entry->id,
            'line_no' => 1,
            'debit_credit' => 'debit',
            'amount' => 500,
            'project' => 'OLD',
            'cost_center' => 'OLD',
        ]);
        JournalEntryLine::factory()->create([
            'journal_entry_id' => $entry->id,
            'line_no' => 2,
            'debit_credit' => 'credit',
            'amount' => 500,
            'project' => 'OLD',
            'cost_center' => 'OLD',
        ]);

        $lines = $this->balancedLinesWithDimensions();

        $this->actingAs($user)
            ->put(route('accounting.journal-entries.update', $entry->id), [
                'date' => now()->toDateString(),
                'memo' => 'Updated dimensions',
                'lines' => $lines,
            ])
            ->assertRedirect(route('accounting.journal-entries.show', $entry->id));

        $storedLines = $entry->fresh()->lines()->orderBy('line_no')->get();
        $this->assertSame('000H', $storedLines[0]->project);
        $this->assertSame('FIN', $storedLines[0]->cost_center);
        $this->assertSame('APS', $storedLines[1]->project);
        $this->assertSame('OPS', $storedLines[1]->cost_center);
    }

    public function test_create_and_edit_forms_mark_project_and_cost_center_as_required(): void
    {
        $user = $this->authorizedUser();

        $this->actingAs($user)
            ->get(route('accounting.journal-entries.create'))
            ->assertOk()
            ->assertSee('name="lines[0][project]"', false)
            ->assertSee('class="form-control form-control-sm line-project" required', false)
            ->assertSee('name="lines[0][cost_center]"', false)
            ->assertSee('class="form-control form-control-sm line-cost-center" required', false);

        $entry = JournalEntry::factory()->create(['created_by' => $user->id]);
        JournalEntryLine::factory()->create([
            'journal_entry_id' => $entry->id,
            'line_no' => 1,
            'debit_credit' => 'debit',
            'amount' => 1000,
            'project' => '000H',
            'cost_center' => 'FIN',
        ]);
        JournalEntryLine::factory()->create([
            'journal_entry_id' => $entry->id,
            'line_no' => 2,
            'debit_credit' => 'credit',
            'amount' => 1000,
            'project' => '000H',
            'cost_center' => 'FIN',
        ]);

        $this->actingAs($user)
            ->get(route('accounting.journal-entries.edit', $entry->id))
            ->assertOk()
            ->assertSee('line-project" required', false)
            ->assertSee('line-cost-center" required', false);
    }
}
