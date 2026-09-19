<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use App\Services\JournalEntryBuilder;
use App\Services\JournalEntryExchangeRateService;
use App\Services\JournalEntryMulticurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JournalEntryMulticurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected JournalEntryMulticurrencyService $multicurrencyService;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'create_manual_journal_entry'], ['guard_name' => 'web']);
        Role::query()->firstOrCreate(['name' => 'admin'], ['guard_name' => 'web']);

        $this->multicurrencyService = app(JournalEntryMulticurrencyService::class);
    }

    protected function authorizedUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $user->givePermissionTo('create_manual_journal_entry');

        return $user;
    }

    public function test_idr_amount_is_fc_times_exchange_rate_rounded_to_two_decimals(): void
    {
        $this->assertSame(1582615.03, $this->multicurrencyService->calculateIdrAmount(100.01, 15824.5678));
        $this->assertSame(1582456.78, $this->multicurrencyService->calculateIdrAmount(100.0, 15824.5678));
    }

    public function test_usd_line_without_fc_amount_or_exchange_rate_is_rejected(): void
    {
        $user = $this->authorizedUser();

        $this->actingAs($user)
            ->from(route('accounting.journal-entries.create'))
            ->post(route('accounting.journal-entries.store'), [
                'date' => now()->toDateString(),
                'memo' => 'USD tanpa FC',
                'lines' => [
                    [
                        'account_code' => '11201026',
                        'debit_credit' => 'debit',
                        'currency' => 'USD',
                        'amount' => 1000,
                        'fc_amount' => null,
                        'exchange_rate' => null,
                    ],
                    [
                        'account_code' => '11201027',
                        'debit_credit' => 'credit',
                        'currency' => 'USD',
                        'amount' => 1000,
                        'fc_amount' => 10,
                        'exchange_rate' => 15800,
                    ],
                ],
            ])
            ->assertSessionHasErrors('lines');

        $errors = session('errors')->get('lines');
        $this->assertTrue(
            collect($errors)->contains(fn (string $msg) => str_contains($msg, 'nominal valas'))
        );
        $this->assertTrue(
            collect($errors)->contains(fn (string $msg) => str_contains($msg, 'kurs'))
                || collect($errors)->contains(fn (string $msg) => str_contains($msg, 'USD'))
        );
    }

    public function test_unbalanced_usd_group_is_rejected_with_currency_in_message(): void
    {
        $rate = 15800;
        $user = $this->authorizedUser();

        $response = $this->actingAs($user)
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
            ]);

        $response->assertSessionHasErrors('lines');
        $errors = session('errors')->get('lines');
        $this->assertTrue(
            collect($errors)->contains(
                fn (string $msg) => str_contains($msg, 'USD') && str_contains($msg, 'selisih')
            )
        );
    }

    public function test_builder_includes_fc_fields_for_usd_and_not_for_idr(): void
    {
        $entry = JournalEntry::factory()->create([
            'date' => '2026-09-18',
            'number' => 'JE-000001',
        ]);

        JournalEntryLine::factory()->create([
            'journal_entry_id' => $entry->id,
            'line_no' => 1,
            'account_code' => '11301006',
            'debit_credit' => 'debit',
            'amount' => 1580000,
            'currency' => 'USD',
            'fc_amount' => 100,
            'exchange_rate' => 15800,
        ]);

        JournalEntryLine::factory()->create([
            'journal_entry_id' => $entry->id,
            'line_no' => 2,
            'account_code' => '11201026',
            'debit_credit' => 'credit',
            'amount' => 1580000,
            'currency' => 'USD',
            'fc_amount' => 100,
            'exchange_rate' => 15800,
        ]);

        $builder = new JournalEntryBuilder($entry->fresh(['lines']));
        $this->assertEmpty($builder->validate());

        $payload = $builder->build();
        $usdDebitLine = $payload['JournalEntryLines'][0];
        $usdCreditLine = $payload['JournalEntryLines'][1];

        $this->assertSame('USD', $usdDebitLine['FCCurrency']);
        $this->assertSame(100.0, $usdDebitLine['FCDebit']);
        $this->assertSame(0.0, $usdDebitLine['FCCredit']);
        $this->assertSame(1580000.0, $usdDebitLine['Debit']);

        $this->assertSame('USD', $usdCreditLine['FCCurrency']);
        $this->assertSame(100.0, $usdCreditLine['FCCredit']);
        $this->assertSame(0.0, $usdCreditLine['FCDebit']);
        $this->assertSame(1580000.0, $usdCreditLine['Credit']);
    }

    public function test_default_usd_rate_comes_from_exchange_rates_table(): void
    {
        $user = User::factory()->create();

        Currency::query()->create([
            'currency_code' => 'USD',
            'currency_name' => 'US Dollar',
            'created_by' => $user->id,
        ]);
        Currency::query()->create([
            'currency_code' => 'IDR',
            'currency_name' => 'Indonesian Rupiah',
            'created_by' => $user->id,
        ]);

        ExchangeRate::query()->create([
            'currency_from' => 'USD',
            'currency_to' => 'IDR',
            'exchange_rate' => 15500.123456,
            'effective_date' => '2026-09-10',
            'created_by' => $user->id,
            'source' => 'test',
        ]);

        ExchangeRate::query()->create([
            'currency_from' => 'USD',
            'currency_to' => 'IDR',
            'exchange_rate' => 15600.654321,
            'effective_date' => '2026-09-15',
            'created_by' => $user->id,
            'source' => 'test',
        ]);

        ExchangeRate::query()->create([
            'currency_from' => 'USD',
            'currency_to' => 'IDR',
            'exchange_rate' => 99999.999999,
            'effective_date' => '2026-09-20',
            'created_by' => $user->id,
            'source' => 'test',
        ]);

        $service = app(JournalEntryExchangeRateService::class);
        $rate = $service->defaultUsdToIdrRateForDate('2026-09-18');

        $this->assertSame('15600.654321', $rate);
    }

    public function test_mixed_obligasi_scenario_is_rejected_with_usd_and_idr_in_message(): void
    {
        $exchangeRate = 15800.75;
        $user = $this->authorizedUser();

        $lines = [
            [
                'account_code' => '11301007',
                'debit_credit' => 'debit',
                'currency' => 'USD',
                'fc_amount' => 695100,
                'exchange_rate' => $exchangeRate,
            ],
            [
                'account_code' => '11301006',
                'debit_credit' => 'debit',
                'currency' => 'USD',
                'fc_amount' => 11052.03,
                'exchange_rate' => $exchangeRate,
            ],
            [
                'account_code' => '71201015',
                'debit_credit' => 'debit',
                'currency' => 'IDR',
                'amount' => 49950,
            ],
            [
                'account_code' => '11201026',
                'debit_credit' => 'credit',
                'currency' => 'USD',
                'fc_amount' => 706152.03,
                'exchange_rate' => $exchangeRate,
            ],
            [
                'account_code' => '11201020',
                'debit_credit' => 'credit',
                'currency' => 'IDR',
                'amount' => 49950,
            ],
        ];

        $normalized = $this->multicurrencyService->normalizeLines($lines);
        $serviceErrors = $this->multicurrencyService->validateLines($normalized);
        $this->assertContains(JournalEntryMulticurrencyService::MIXED_IDR_AND_FOREIGN_CURRENCY_ERROR, $serviceErrors);

        $this->actingAs($user)
            ->from(route('accounting.journal-entries.create'))
            ->post(route('accounting.journal-entries.store'), [
                'date' => '2026-09-18',
                'memo' => 'Settlement obligasi campuran',
                'lines' => $lines,
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

    public function test_pure_usd_three_line_journal_passes_validation_and_store(): void
    {
        $exchangeRate = 15800.75;
        $user = $this->authorizedUser();

        $lines = [
            [
                'account_code' => '11301007',
                'debit_credit' => 'debit',
                'currency' => 'USD',
                'fc_amount' => 695100,
                'exchange_rate' => $exchangeRate,
            ],
            [
                'account_code' => '11301006',
                'debit_credit' => 'debit',
                'currency' => 'USD',
                'fc_amount' => 11052.03,
                'exchange_rate' => $exchangeRate,
            ],
            [
                'account_code' => '11201026',
                'debit_credit' => 'credit',
                'currency' => 'USD',
                'fc_amount' => 706152.03,
                'exchange_rate' => $exchangeRate,
            ],
        ];

        $normalized = $this->multicurrencyService->normalizeLines($lines);
        $this->assertEmpty($this->multicurrencyService->validateLines($normalized));

        $this->actingAs($user)
            ->post(route('accounting.journal-entries.store'), [
                'date' => '2026-09-18',
                'memo' => 'Settlement obligasi USD',
                'lines' => $lines,
            ])
            ->assertRedirect();

        $entry = JournalEntry::first();
        $this->assertTrue($entry->has_foreign_currency);
        $this->assertCount(3, $entry->lines);
        $this->assertEmpty((new JournalEntryBuilder($entry->fresh(['lines'])))->validate());
    }

    public function test_pure_idr_two_line_journal_passes_validation_via_service(): void
    {
        $lines = [
            [
                'account_code' => '71201015',
                'debit_credit' => 'debit',
                'currency' => 'IDR',
                'amount' => 49950,
            ],
            [
                'account_code' => '11201020',
                'debit_credit' => 'credit',
                'currency' => 'IDR',
                'amount' => 49950,
            ],
        ];

        $this->assertEmpty($this->multicurrencyService->validateLines($lines));
    }

    public function test_pure_idr_journal_entry_payload_unchanged(): void
    {
        $entry = JournalEntry::factory()->create([
            'date' => '2026-09-18',
            'number' => 'JE-000099',
            'reference' => 'REF-ID',
        ]);

        JournalEntryLine::factory()->create([
            'journal_entry_id' => $entry->id,
            'line_no' => 1,
            'account_code' => '11001',
            'debit_credit' => 'debit',
            'amount' => 1000,
            'currency' => 'IDR',
        ]);

        JournalEntryLine::factory()->create([
            'journal_entry_id' => $entry->id,
            'line_no' => 2,
            'account_code' => '21001',
            'debit_credit' => 'credit',
            'amount' => 1000,
            'currency' => 'IDR',
        ]);

        $builder = new JournalEntryBuilder($entry->fresh(['lines']));
        $this->assertEmpty($builder->validate());

        $payload = $builder->build();
        $this->assertSame('2026-09-18', $payload['ReferenceDate']);
        $this->assertCount(2, $payload['JournalEntryLines']);

        foreach ($payload['JournalEntryLines'] as $line) {
            $this->assertArrayNotHasKey('FCCurrency', $line);
            $this->assertArrayNotHasKey('FCDebit', $line);
            $this->assertArrayNotHasKey('FCCredit', $line);
        }

        $this->assertSame(1000.0, $payload['JournalEntryLines'][0]['Debit']);
        $this->assertSame(1000.0, $payload['JournalEntryLines'][1]['Credit']);
        $this->assertFalse((bool) $entry->fresh()->has_foreign_currency);
    }

    public function test_unsupported_foreign_currency_is_rejected(): void
    {
        $user = $this->authorizedUser();

        $this->actingAs($user)
            ->from(route('accounting.journal-entries.create'))
            ->post(route('accounting.journal-entries.store'), [
                'date' => now()->toDateString(),
                'memo' => 'EUR ditolak',
                'lines' => [
                    [
                        'account_code' => '11001',
                        'debit_credit' => 'debit',
                        'currency' => 'EUR',
                        'amount' => 100,
                    ],
                    [
                        'account_code' => '21001',
                        'debit_credit' => 'credit',
                        'amount' => 100,
                    ],
                ],
            ])
            ->assertSessionHasErrors('lines.*.currency');

        $this->assertSame(
            'Baris valas hanya mendukung USD untuk saat ini',
            session('errors')->first('lines.0.currency')
        );
    }

    public function test_usd_line_manual_idr_amount_is_overridden_by_system_calculation(): void
    {
        $user = $this->authorizedUser();
        $rate = 15800;

        $this->actingAs($user)
            ->post(route('accounting.journal-entries.store'), [
                'date' => now()->toDateString(),
                'memo' => 'Override IDR',
                'lines' => [
                    [
                        'account_code' => '11301006',
                        'debit_credit' => 'debit',
                        'currency' => 'USD',
                        'fc_amount' => 10,
                        'exchange_rate' => $rate,
                        'amount' => 1,
                    ],
                    [
                        'account_code' => '11201026',
                        'debit_credit' => 'credit',
                        'currency' => 'USD',
                        'fc_amount' => 10,
                        'exchange_rate' => $rate,
                        'amount' => 1,
                    ],
                ],
            ])
            ->assertRedirect();

        $line = JournalEntryLine::where('debit_credit', 'debit')->first();
        $this->assertSame('158000.00', (string) $line->amount);
    }
}
