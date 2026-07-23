<?php

namespace Tests\Feature;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JournalEntryTest extends TestCase
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

    protected function balancedLines(): array
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
                'project' => '000H',
                'cost_center' => 'FIN',
                'description' => 'Credit line',
            ],
        ];
    }

    public function test_unauthorized_user_cannot_access_journal_entries(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('accounting.journal-entries.index'))
            ->assertRedirect()
            ->assertSessionHas('alert_type', 'error');
    }

    public function test_authorized_user_can_view_index(): void
    {
        $user = $this->authorizedUser();

        $this->actingAs($user)
            ->get(route('accounting.journal-entries.index'))
            ->assertOk();
    }

    public function test_store_creates_balanced_journal_entry(): void
    {
        $user = $this->authorizedUser();

        $response = $this->actingAs($user)
            ->post(route('accounting.journal-entries.store'), [
                'date' => now()->toDateString(),
                'memo' => 'Test memo',
                'reference' => 'REF-001',
                'lines' => $this->balancedLines(),
            ]);

        $entry = JournalEntry::first();
        $this->assertNotNull($entry);
        $this->assertEquals('Test memo', $entry->memo);
        $this->assertEquals(2, $entry->lines()->count());
        $this->assertEquals(1000.0, $entry->totalDebit());
        $this->assertEquals(1000.0, $entry->totalCredit());

        $response->assertRedirect(route('accounting.journal-entries.show', $entry->id));
    }

    public function test_store_rejects_unbalanced_journal_entry(): void
    {
        $user = $this->authorizedUser();
        $lines = $this->balancedLines();
        $lines[1]['amount'] = 500;

        $this->actingAs($user)
            ->from(route('accounting.journal-entries.create'))
            ->post(route('accounting.journal-entries.store'), [
                'date' => now()->toDateString(),
                'memo' => 'Unbalanced',
                'lines' => $lines,
            ])
            ->assertSessionHasErrors('lines');

        $this->assertDatabaseCount('journal_entries', 0);
    }

    public function test_posted_journal_cannot_be_edited(): void
    {
        $user = $this->authorizedUser();
        $entry = JournalEntry::factory()->posted()->create(['created_by' => $user->id]);
        JournalEntryLine::factory()->create([
            'journal_entry_id' => $entry->id,
            'line_no' => 1,
            'debit_credit' => 'debit',
            'amount' => 500,
        ]);
        JournalEntryLine::factory()->create([
            'journal_entry_id' => $entry->id,
            'line_no' => 2,
            'debit_credit' => 'credit',
            'amount' => 500,
        ]);

        $this->actingAs($user)
            ->get(route('accounting.journal-entries.edit', $entry->id))
            ->assertRedirect(route('accounting.journal-entries.show', $entry->id));
    }

    public function test_draft_journal_can_be_deleted(): void
    {
        $user = $this->authorizedUser();
        $entry = JournalEntry::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->delete(route('accounting.journal-entries.destroy', $entry->id))
            ->assertRedirect(route('accounting.journal-entries.index'));

        $this->assertDatabaseMissing('journal_entries', ['id' => $entry->id]);
    }

    public function test_posted_journal_cannot_be_deleted(): void
    {
        $user = $this->authorizedUser();
        $entry = JournalEntry::factory()->posted()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->from(route('accounting.journal-entries.index'))
            ->delete(route('accounting.journal-entries.destroy', $entry->id))
            ->assertRedirect(route('accounting.journal-entries.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('journal_entries', ['id' => $entry->id]);
    }
}
