<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use App\Models\UtilityBill;
use App\Models\UtilityCustomer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UtilityBillIndexOrderAndTipeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('akses_utilities', 'web');
    }

    public function test_data_default_order_is_newest_created_first(): void
    {
        $user = $this->utilitiesUser();
        $periode = '2026-09';
        $ids = $this->seedThreeBillsWithCreatedAt($periode);

        $response = $this->actingAs($user)
            ->getJson($this->dataTablesUrl([
                'periode' => $periode,
                'order' => [],
            ]));

        $response->assertOk();
        $rows = collect($response->json('data'));
        $this->assertCount(3, $rows);
        $this->assertSame((string) $ids['newest'], (string) $rows->first()['id']);
    }

    public function test_data_filters_by_tipe_prepaid(): void
    {
        $user = $this->utilitiesUser();
        $periode = '2026-09';
        $this->seedPrepaidAndPostpaidBills($periode);

        $response = $this->actingAs($user)
            ->getJson($this->dataTablesUrl([
                'periode' => $periode,
                'tipe' => 'prepaid',
            ]));

        $response->assertOk();
        $rows = collect($response->json('data'));
        $this->assertCount(1, $rows);
        $this->assertStringContainsString('PLN-PRE-001', $rows->first()['id_pelanggan']);
        $this->assertStringContainsString('vj-chip-info', $rows->first()['tipe_badge']);
    }

    public function test_data_filters_by_tipe_postpaid(): void
    {
        $user = $this->utilitiesUser();
        $periode = '2026-09';
        $this->seedPrepaidAndPostpaidBills($periode);

        $response = $this->actingAs($user)
            ->getJson($this->dataTablesUrl([
                'periode' => $periode,
                'tipe' => 'postpaid',
            ]));

        $response->assertOk();
        $rows = collect($response->json('data'));
        $this->assertCount(1, $rows);
        $this->assertStringContainsString('PLN-POS-001', $rows->first()['id_pelanggan']);
    }

    public function test_data_without_tipe_filter_returns_all_types(): void
    {
        $user = $this->utilitiesUser();
        $periode = '2026-09';
        $this->seedPrepaidAndPostpaidBills($periode);

        $response = $this->actingAs($user)
            ->getJson($this->dataTablesUrl(['periode' => $periode]));

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_index_view_has_tipe_filter_and_ajax_parameter(): void
    {
        $user = $this->utilitiesUser();

        $this->actingAs($user)
            ->get(route('utilities.bills.index'))
            ->assertOk()
            ->assertSee('id="filter_tipe"', false)
            ->assertSee('>Tipe</label>', false)
            ->assertSee('value="prepaid"', false)
            ->assertSee('value="postpaid"', false)
            ->assertSee("d.tipe = $('#filter_tipe').val();", false);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    protected function dataTablesUrl(array $extra = []): string
    {
        return route('utilities.bills.data', array_merge([
            'draw' => 1,
            'start' => 0,
            'length' => 50,
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
            'order' => [],
            'search' => [
                'value' => '',
                'regex' => 'false',
            ],
        ], $extra));
    }

    /**
     * @return array{oldest: int, middle: int, newest: int}
     */
    protected function seedThreeBillsWithCreatedAt(string $periode): array
    {
        $account = $this->createUtilityAccount();

        $customers = [];
        foreach (['A', 'B', 'C'] as $suffix) {
            $customers[$suffix] = UtilityCustomer::query()->create([
                'jenis_utilitas' => 'pln',
                'tipe' => 'postpaid',
                'id_pelanggan' => 'PLN-ORD-'.$suffix,
                'nama' => 'Pelanggan '.$suffix,
                'project' => '000H',
                'department' => '20',
                'account_id' => $account->id,
                'is_active' => true,
            ]);
        }

        $oldest = UtilityBill::query()->create([
            'utility_customer_id' => $customers['A']->id,
            'periode' => $periode,
            'jumlah_tagihan' => 100000,
            'tanggal_jatuh_tempo' => $periode.'-20',
        ]);
        $middle = UtilityBill::query()->create([
            'utility_customer_id' => $customers['B']->id,
            'periode' => $periode,
            'jumlah_tagihan' => 110000,
            'tanggal_jatuh_tempo' => $periode.'-20',
        ]);
        $newest = UtilityBill::query()->create([
            'utility_customer_id' => $customers['C']->id,
            'periode' => $periode,
            'jumlah_tagihan' => 120000,
            'tanggal_jatuh_tempo' => $periode.'-20',
        ]);

        UtilityBill::query()->whereKey($oldest->id)->update(['created_at' => Carbon::parse('2026-09-01 10:00:00')]);
        UtilityBill::query()->whereKey($middle->id)->update(['created_at' => Carbon::parse('2026-09-02 10:00:00')]);
        UtilityBill::query()->whereKey($newest->id)->update(['created_at' => Carbon::parse('2026-09-03 10:00:00')]);

        return [
            'oldest' => $oldest->id,
            'middle' => $middle->id,
            'newest' => $newest->id,
        ];
    }

    protected function seedPrepaidAndPostpaidBills(string $periode): void
    {
        $account = $this->createUtilityAccount();

        $prepaidCustomer = UtilityCustomer::query()->create([
            'jenis_utilitas' => 'pln',
            'tipe' => 'prepaid',
            'id_pelanggan' => 'PLN-PRE-001',
            'nama' => 'Pelanggan Prepaid',
            'project' => '000H',
            'department' => '20',
            'account_id' => $account->id,
            'is_active' => true,
        ]);

        $postpaidCustomer = UtilityCustomer::query()->create([
            'jenis_utilitas' => 'pln',
            'tipe' => 'postpaid',
            'id_pelanggan' => 'PLN-POS-001',
            'nama' => 'Pelanggan Postpaid',
            'project' => '000H',
            'department' => '20',
            'account_id' => $account->id,
            'is_active' => true,
        ]);

        UtilityBill::query()->create([
            'utility_customer_id' => $prepaidCustomer->id,
            'periode' => $periode,
            'jumlah_tagihan' => 50000,
            'tanggal_bayar' => $periode.'-05',
            'nomor_token' => 'TOKEN-1',
        ]);

        UtilityBill::query()->create([
            'utility_customer_id' => $postpaidCustomer->id,
            'periode' => $periode,
            'jumlah_tagihan' => 150000,
            'tanggal_jatuh_tempo' => $periode.'-20',
        ]);
    }

    protected function createUtilityAccount(): Account
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
}
