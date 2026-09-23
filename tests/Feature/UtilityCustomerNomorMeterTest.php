<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use App\Models\UtilityBill;
use App\Models\UtilityCustomer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UtilityCustomerNomorMeterTest extends TestCase
{
    use RefreshDatabase;

    public const SAMPLE_ID_PELANGGAN = '232011161215';

    public const SAMPLE_NOMOR_METER = '45085313257';

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('akses_utilities', 'web');
    }

    public function test_store_persists_nomor_meter_and_data_endpoint_returns_it(): void
    {
        $user = $this->utilitiesUser();
        $account = $this->createAccount();

        $this->actingAs($user)
            ->post(route('utilities.customers.store'), $this->customerPayload($account->id))
            ->assertRedirect(route('utilities.customers.index'));

        $customer = UtilityCustomer::query()->where('id_pelanggan', self::SAMPLE_ID_PELANGGAN)->first();
        $this->assertNotNull($customer);
        $this->assertSame(self::SAMPLE_NOMOR_METER, $customer->nomor_meter);

        $response = $this->actingAs($user)
            ->getJson($this->customerDataTablesUrl());

        $response->assertOk();
        $row = collect($response->json('data'))->firstWhere('id_pelanggan', self::SAMPLE_ID_PELANGGAN);
        $this->assertNotNull($row);
        $this->assertSame(self::SAMPLE_NOMOR_METER, strip_tags($row['nomor_meter']));
    }

    public function test_customer_index_shows_nomor_meter_column(): void
    {
        $user = $this->utilitiesUser();
        $account = $this->createAccount();
        UtilityCustomer::query()->create($this->customerAttributes($account->id));

        $this->actingAs($user)
            ->get(route('utilities.customers.index'))
            ->assertOk()
            ->assertSee('>No. Meter</th>', false)
            ->assertSee("data: 'nomor_meter'", false);
    }

    public function test_bill_create_page_includes_nomor_meter_on_option_label_and_data_attribute(): void
    {
        $user = $this->utilitiesUser();
        $account = $this->createAccount();
        UtilityCustomer::query()->create($this->customerAttributes($account->id));

        $this->actingAs($user)
            ->get(route('utilities.bills.create'))
            ->assertOk()
            ->assertSee(' · Meter '.self::SAMPLE_NOMOR_METER, false)
            ->assertSee('data-nomor-meter="'.self::SAMPLE_NOMOR_METER.'"', false)
            ->assertSee('No. Meter:', false);
    }

    public function test_bill_data_global_search_finds_rows_by_nomor_meter_and_id_pelanggan(): void
    {
        $user = $this->utilitiesUser();
        $account = $this->createAccount();
        $customer = UtilityCustomer::query()->create($this->customerAttributes($account->id));
        $periode = '2026-09';

        UtilityBill::query()->create([
            'utility_customer_id' => $customer->id,
            'periode' => $periode,
            'jumlah_tagihan' => 150000,
            'tanggal_jatuh_tempo' => $periode.'-20',
        ]);

        $otherCustomer = UtilityCustomer::query()->create(array_merge($this->customerAttributes($account->id), [
            'id_pelanggan' => '999999999999',
            'nomor_meter' => '11111111111',
            'nama' => 'Pelanggan Lain',
        ]));
        UtilityBill::query()->create([
            'utility_customer_id' => $otherCustomer->id,
            'periode' => $periode,
            'jumlah_tagihan' => 200000,
            'tanggal_jatuh_tempo' => $periode.'-20',
        ]);

        $byMeter = $this->actingAs($user)
            ->getJson($this->billDataTablesUrl($periode, [
                'search' => ['value' => self::SAMPLE_NOMOR_METER, 'regex' => 'false'],
            ]));
        $byMeter->assertOk();
        $this->assertSame(1, $byMeter->json('recordsFiltered'));
        $this->assertStringContainsString(self::SAMPLE_ID_PELANGGAN, strip_tags($byMeter->json('data.0.id_pelanggan')));

        $byId = $this->actingAs($user)
            ->getJson($this->billDataTablesUrl($periode, [
                'search' => ['value' => self::SAMPLE_ID_PELANGGAN, 'regex' => 'false'],
            ]));
        $byId->assertOk();
        $this->assertSame(1, $byId->json('recordsFiltered'));
        $this->assertStringContainsString(self::SAMPLE_NOMOR_METER, $byId->json('data.0.id_pelanggan'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function customerPayload(int $accountId): array
    {
        return array_merge($this->customerAttributes($accountId), [
            'is_active' => '1',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function customerAttributes(int $accountId): array
    {
        return [
            'jenis_utilitas' => 'pln',
            'tipe' => 'postpaid',
            'id_pelanggan' => self::SAMPLE_ID_PELANGGAN,
            'nomor_meter' => self::SAMPLE_NOMOR_METER,
            'nama' => 'ALOYSIA TUTUT RATNAWATI',
            'lokasi' => 'Beverly',
            'project' => '000H',
            'department' => '20',
            'account_id' => $accountId,
            'is_active' => true,
        ];
    }

    protected function createAccount(): Account
    {
        return Account::query()->create([
            'account_number' => '61208001',
            'account_name' => 'Beban Utilitas',
            'type' => 'expense',
            'sap_account' => '61208001',
            'is_active' => true,
            'is_hidden' => false,
        ]);
    }

    protected function utilitiesUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('akses_utilities');

        return $user;
    }

    protected function customerDataTablesUrl(): string
    {
        return route('utilities.customers.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 50,
            'columns' => [
                ['data' => 'id_pelanggan', 'name' => 'id_pelanggan', 'searchable' => 'true', 'orderable' => 'true'],
                ['data' => 'nomor_meter', 'name' => 'nomor_meter', 'searchable' => 'true', 'orderable' => 'true'],
                ['data' => 'nama', 'name' => 'nama', 'searchable' => 'true', 'orderable' => 'true'],
                ['data' => 'jenis_label', 'name' => 'jenis_utilitas', 'searchable' => 'true', 'orderable' => 'true'],
                ['data' => 'tipe_badge', 'name' => '', 'searchable' => 'false', 'orderable' => 'false'],
                ['data' => 'project', 'name' => 'project', 'searchable' => 'true', 'orderable' => 'true'],
                ['data' => 'account_info', 'name' => '', 'searchable' => 'false', 'orderable' => 'false'],
                ['data' => 'is_active_badge', 'name' => '', 'searchable' => 'false', 'orderable' => 'false'],
                ['data' => 'action', 'name' => '', 'searchable' => 'false', 'orderable' => 'false'],
            ],
            'order' => [['column' => 1, 'dir' => 'asc']],
            'search' => ['value' => '', 'regex' => 'false'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    protected function billDataTablesUrl(string $periode, array $extra = []): string
    {
        return route('utilities.bills.data', array_merge([
            'draw' => 1,
            'start' => 0,
            'length' => 50,
            'periode' => $periode,
            'columns' => [
                ['data' => 'checkbox', 'name' => '', 'searchable' => 'false', 'orderable' => 'false'],
                ['data' => 'id_pelanggan', 'name' => 'utility_customers.id_pelanggan', 'searchable' => 'true', 'orderable' => 'true'],
                ['data' => 'nama_customer', 'name' => 'utility_customers.nama', 'searchable' => 'true', 'orderable' => 'true'],
                ['data' => 'lokasi', 'name' => 'utility_customers.lokasi', 'searchable' => 'true', 'orderable' => 'true'],
                ['data' => 'jenis_utilitas', 'name' => 'utility_customers.jenis_utilitas', 'searchable' => 'true', 'orderable' => 'true'],
                ['data' => 'tipe_badge', 'name' => '', 'searchable' => 'false', 'orderable' => 'false'],
                ['data' => 'periode', 'name' => 'utility_bills.periode', 'searchable' => 'true', 'orderable' => 'true'],
                ['data' => 'jumlah_tagihan', 'name' => 'utility_bills.jumlah_tagihan', 'searchable' => 'true', 'orderable' => 'true'],
                ['data' => 'tanggal_jatuh_tempo', 'name' => 'utility_bills.tanggal_jatuh_tempo', 'searchable' => 'true', 'orderable' => 'true'],
                ['data' => 'nomor_token_display', 'name' => '', 'searchable' => 'false', 'orderable' => 'false'],
                ['data' => 'status_badge', 'name' => '', 'searchable' => 'false', 'orderable' => 'false'],
                ['data' => 'payreq_badge', 'name' => '', 'searchable' => 'false', 'orderable' => 'false'],
                ['data' => 'sap_badge', 'name' => '', 'searchable' => 'false', 'orderable' => 'false'],
                ['data' => 'action', 'name' => '', 'searchable' => 'false', 'orderable' => 'false'],
            ],
            'order' => [['column' => 6, 'dir' => 'desc']],
            'search' => ['value' => '', 'regex' => 'false'],
        ], $extra));
    }
}
