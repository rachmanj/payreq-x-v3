<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\User;
use App\Services\ExchangeRateScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ExchangeRatesUpdateCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{kmk: array<string, mixed>, rates: list<array<string, mixed>>, scraped_at: \Illuminate\Support\Carbon}
     */
    private function cannedScraperResponse(): array
    {
        return [
            'kmk' => [
                'kmk_number' => 'TEST/1/KMK',
                'effective_from' => '2030-01-01',
                'effective_to' => '2030-01-07',
            ],
            'rates' => [
                [
                    'currency_code' => 'USD',
                    'rate_to_idr' => 15000.0,
                    'raw_value' => '15.000,00',
                    'is_jpy_per_100' => false,
                ],
            ],
            'scraped_at' => now(),
        ];
    }

    public function test_command_creates_missing_currencies_and_rates_using_any_existing_user(): void
    {
        $user = User::factory()->create();

        $payload = $this->cannedScraperResponse();
        $this->mock(ExchangeRateScraperService::class, function ($mock) use ($payload) {
            $mock->shouldReceive('fetch')
                ->once()
                ->with(null)
                ->andReturn($payload);
        });

        $exitCode = Artisan::call('exchange-rates:update', [
            '--no-expand' => true,
            '--force' => true,
        ]);

        $this->assertSame(0, $exitCode);

        $this->assertDatabaseHas('currencies', [
            'currency_code' => 'USD',
            'created_by' => $user->id,
        ]);
        $this->assertDatabaseHas('currencies', [
            'currency_code' => 'IDR',
            'created_by' => $user->id,
        ]);

        $this->assertTrue(
            ExchangeRate::query()
                ->where('currency_from', 'USD')
                ->where('currency_to', 'IDR')
                ->whereDate('effective_date', '2030-01-01')
                ->where('created_by', $user->id)
                ->exists()
        );

        $this->assertSame(15000.0, (float) ExchangeRate::query()->where('currency_from', 'USD')->value('exchange_rate'));
        $this->assertSame(2, Currency::query()->count());
    }

    public function test_command_exits_with_error_when_no_users_exist(): void
    {
        $payload = $this->cannedScraperResponse();
        $this->mock(ExchangeRateScraperService::class, function ($mock) use ($payload) {
            $mock->shouldReceive('fetch')
                ->once()
                ->with(null)
                ->andReturn($payload);
        });

        $exitCode = Artisan::call('exchange-rates:update', ['--no-expand' => true]);

        $this->assertSame(1, $exitCode);
        $this->assertSame(0, Currency::query()->count());
    }
}
