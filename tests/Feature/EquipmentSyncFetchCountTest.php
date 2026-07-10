<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ArkFleetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EquipmentSyncFetchCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_fetch_count_returns_configuration_error_when_url_missing(): void
    {
        config(['services.ark_fleet.url_equipments' => null]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('equipments.sync.fetch_count'))
            ->assertOk()
            ->assertJson([
                'success' => false,
                'count' => 0,
            ])
            ->assertJsonPath('debug.configured_base_url', null)
            ->assertJsonPath('debug.request_url', null);
    }

    public function test_fetch_count_returns_equipment_count_from_ark_fleet_api(): void
    {
        config(['services.ark_fleet.url_equipments' => 'http://ark-fleet.test/api/equipments']);

        Http::fake([
            'http://ark-fleet.test/api/equipments?status=ACTIVE' => Http::response([
                'count' => 2,
                'data' => [
                    ['unit_no' => 'EQ-001'],
                    ['unit_no' => 'EQ-002'],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('equipments.sync.fetch_count'))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'count' => 2,
            ])
            ->assertJsonPath('debug.http_status', 200)
            ->assertJsonPath('debug.data_array_length', 2);
    }

    public function test_sync_index_shows_fetch_button_and_diagnostics(): void
    {
        config(['services.ark_fleet.url_equipments' => 'http://ark-fleet.test/api/equipments']);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('equipments.sync.index'))
            ->assertOk()
            ->assertSee('id="fetch-btn"', false)
            ->assertSee('ARK-Fleet API debug info')
            ->assertSee('http://ark-fleet.test/api/equipments');
    }

    public function test_ark_fleet_service_reports_connection_failure_details(): void
    {
        config(['services.ark_fleet.url_equipments' => 'http://unreachable-ark-fleet.test/api/equipments']);

        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
        });

        $result = app(ArkFleetService::class)->fetchActiveEquipmentCount();

        $this->assertFalse($result['success']);
        $this->assertSame(0, $result['count']);
        $this->assertArrayHasKey('error_message', $result['debug']);
        $this->assertSame('http://unreachable-ark-fleet.test/api/equipments?status=ACTIVE', $result['debug']['request_url']);
    }
}
