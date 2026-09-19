<?php

namespace Tests\Feature;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use App\Services\JournalEntryMulticurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JournalEntryMulticurrencyUiTest extends TestCase
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

    public function test_create_page_includes_multicurrency_form_fields(): void
    {
        $user = $this->authorizedUser();

        $this->actingAs($user)
            ->get(route('accounting.journal-entries.create'))
            ->assertOk()
            ->assertSee('Mata Uang', false)
            ->assertSee('Nominal Valas', false)
            ->assertSee('name="lines[0][currency]"', false)
            ->assertSee('name="lines[0][fc_amount]"', false)
            ->assertSee('name="lines[0][exchange_rate]"', false)
            ->assertSee('id="je-currency-summary"', false)
            ->assertSee('dua jurnal terpisah', false);
    }

    public function test_store_mixed_usd_and_idr_lines_is_rejected(): void
    {
        $user = $this->authorizedUser();
        $exchangeRate = 15800;

        $this->actingAs($user)
            ->from(route('accounting.journal-entries.create'))
            ->post(route('accounting.journal-entries.store'), [
                'date' => now()->toDateString(),
                'memo' => 'Campuran USD IDR',
                'lines' => [
                    [
                        'account_code' => '11301006',
                        'debit_credit' => 'debit',
                        'currency' => 'USD',
                        'fc_amount' => 10,
                        'exchange_rate' => $exchangeRate,
                        'amount' => 1,
                    ],
                    [
                        'account_code' => '11201026',
                        'debit_credit' => 'credit',
                        'currency' => 'USD',
                        'fc_amount' => 10,
                        'exchange_rate' => $exchangeRate,
                        'amount' => 1,
                    ],
                    [
                        'account_code' => '61001001',
                        'debit_credit' => 'debit',
                        'currency' => 'IDR',
                        'amount' => 50000,
                    ],
                    [
                        'account_code' => '11201020',
                        'debit_credit' => 'credit',
                        'currency' => 'IDR',
                        'amount' => 50000,
                    ],
                ],
            ])
            ->assertSessionHasErrors('lines');

        $errors = session('errors')->get('lines');
        $this->assertTrue(
            collect($errors)->contains(
                fn (string $msg) => str_contains($msg, 'USD') && str_contains($msg, 'IDR')
            )
        );

        $this->assertNull(JournalEntry::first());
    }

    public function test_store_pure_usd_lines_persists_calculated_idr_amounts(): void
    {
        $user = $this->authorizedUser();
        $exchangeRate = 15800;
        $fcDebit = 10;
        $expectedIdr = app(JournalEntryMulticurrencyService::class)->calculateIdrAmount($fcDebit, $exchangeRate);

        $this->actingAs($user)
            ->post(route('accounting.journal-entries.store'), [
                'date' => now()->toDateString(),
                'memo' => 'USD murni',
                'lines' => [
                    [
                        'account_code' => '11301006',
                        'debit_credit' => 'debit',
                        'currency' => 'USD',
                        'fc_amount' => $fcDebit,
                        'exchange_rate' => $exchangeRate,
                        'amount' => 1,
                    ],
                    [
                        'account_code' => '11201026',
                        'debit_credit' => 'credit',
                        'currency' => 'USD',
                        'fc_amount' => $fcDebit,
                        'exchange_rate' => $exchangeRate,
                        'amount' => 1,
                    ],
                ],
            ])
            ->assertRedirect();

        $entry = JournalEntry::first();
        $this->assertTrue($entry->has_foreign_currency);

        $usdDebit = JournalEntryLine::where('debit_credit', 'debit')->where('currency', 'USD')->first();
        $this->assertSame(number_format($expectedIdr, 2, '.', ''), (string) $usdDebit->amount);
        $this->assertSame(number_format($fcDebit, 2, '.', ''), (string) $usdDebit->fc_amount);
    }

    public function test_store_unbalanced_usd_group_returns_validation_error_mentioning_usd(): void
    {
        $user = $this->authorizedUser();
        $rate = 15800;

        $this->actingAs($user)
            ->from(route('accounting.journal-entries.create'))
            ->post(route('accounting.journal-entries.store'), [
                'date' => now()->toDateString(),
                'memo' => 'USD tidak seimbang',
                'lines' => [
                    [
                        'account_code' => '11301006',
                        'debit_credit' => 'debit',
                        'currency' => 'USD',
                        'fc_amount' => 100,
                        'exchange_rate' => $rate,
                    ],
                    [
                        'account_code' => '11201026',
                        'debit_credit' => 'credit',
                        'currency' => 'USD',
                        'fc_amount' => 90,
                        'exchange_rate' => $rate,
                    ],
                ],
            ])
            ->assertSessionHasErrors('lines');

        $errors = session('errors')->get('lines');
        $this->assertTrue(
            collect($errors)->contains(fn (string $msg) => str_contains($msg, 'USD'))
        );
    }

    public function test_index_data_shows_usd_badge_for_foreign_currency_journal(): void
    {
        $user = $this->authorizedUser();

        $entry = JournalEntry::factory()->create([
            'created_by' => $user->id,
            'has_foreign_currency' => true,
            'number' => 'JE-000888',
        ]);

        $this->actingAs($user)
            ->getJson(route('accounting.journal-entries.data'))
            ->assertOk()
            ->assertJsonFragment(['number' => $entry->number.' <span class="badge badge-info ml-1">USD</span>']);
    }

    public function test_pure_idr_journal_create_and_store_regression(): void
    {
        $user = $this->authorizedUser();

        $this->actingAs($user)
            ->get(route('accounting.journal-entries.create'))
            ->assertOk()
            ->assertSee('name="lines[0][amount]"', false);

        $this->actingAs($user)
            ->post(route('accounting.journal-entries.store'), [
                'date' => now()->toDateString(),
                'memo' => 'IDR murni',
                'lines' => [
                    [
                        'account_code' => '11001',
                        'debit_credit' => 'debit',
                        'currency' => 'IDR',
                        'amount' => 1000,
                    ],
                    [
                        'account_code' => '21001',
                        'debit_credit' => 'credit',
                        'currency' => 'IDR',
                        'amount' => 1000,
                    ],
                ],
            ])
            ->assertRedirect();

        $entry = JournalEntry::first();
        $this->assertFalse((bool) $entry->has_foreign_currency);
        $this->assertEquals(1000.0, $entry->totalDebit());
        $this->assertEquals(1000.0, $entry->totalCredit());
    }

    public function test_default_usd_rate_endpoint_returns_rate_for_journal_date(): void
    {
        $user = $this->authorizedUser();

        $this->actingAs($user)
            ->getJson(route('accounting.journal-entries.default_usd_rate', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertJsonStructure(['exchange_rate']);
    }
}
