<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use App\Models\UtilityBill;
use App\Models\UtilityCustomer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UtilityBillIndexLokasiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('akses_utilities', 'web');
    }

    public function test_data_includes_lokasi_from_customer(): void
    {
        $user = $this->utilitiesUser();
        $periode = '2026-09';
        $this->seedBillsForLokasiTest($periode);

        $response = $this->actingAs($user)
            ->getJson($this->dataTablesUrl(['periode' => $periode]));

        $response->assertOk();
        $rows = collect($response->json('data'));
        $this->assertCount(2, $rows);

        $lokasiValues = $rows->pluck('lokasi')->sort()->values()->all();
        $this->assertSame(['Kantor Pusat', 'Mess GBR'], $lokasiValues);
    }

    public function test_data_filters_by_lokasi_parameter(): void
    {
        $user = $this->utilitiesUser();
        $periode = '2026-09';
        $this->seedBillsForLokasiTest($periode);

        $response = $this->actingAs($user)
            ->getJson($this->dataTablesUrl([
                'periode' => $periode,
                'lokasi' => 'Mess GBR',
            ]));

        $response->assertOk();
        $rows = collect($response->json('data'));
        $this->assertCount(1, $rows);
        $this->assertSame('Mess GBR', $rows->first()['lokasi']);
    }

    public function test_global_search_finds_rows_by_lokasi(): void
    {
        $user = $this->utilitiesUser();
        $periode = '2026-09';
        $this->seedBillsForLokasiTest($periode);

        $response = $this->actingAs($user)
            ->getJson($this->dataTablesUrl([
                'periode' => $periode,
                'search' => [
                    'value' => 'Mess GBR',
                    'regex' => 'false',
                ],
            ]));

        $response->assertOk();
        $payload = $response->json();
        $rows = collect($payload['data']);
        $this->assertSame(1, $payload['recordsFiltered']);
        $this->assertCount(1, $rows);
        $this->assertSame('Mess GBR', $rows->first()['lokasi']);
    }

    public function test_global_search_finds_rows_by_periode(): void
    {
        $user = $this->utilitiesUser();
        $periode = '2026-08';
        $this->seedBillsForLokasiTest($periode);

        $response = $this->actingAs($user)
            ->getJson($this->dataTablesUrl([
                'periode' => $periode,
                'search' => [
                    'value' => '2026-08',
                    'regex' => 'false',
                ],
            ]));

        $response->assertOk();
        $payload = $response->json();
        $this->assertSame(2, $payload['recordsFiltered']);
        $this->assertCount(2, $payload['data']);
        foreach ($payload['data'] as $row) {
            $this->assertSame('2026-08', $row['periode']);
        }
    }

    public function test_global_search_finds_rows_by_customer_name(): void
    {
        $user = $this->utilitiesUser();
        $periode = '2026-09';
        $this->seedBillsForLokasiTest($periode);

        $response = $this->actingAs($user)
            ->getJson($this->dataTablesUrl([
                'periode' => $periode,
                'search' => [
                    'value' => 'Pelanggan Kantor',
                    'regex' => 'false',
                ],
            ]));

        $response->assertOk();
        $payload = $response->json();
        $rows = collect($payload['data']);
        $this->assertSame(1, $payload['recordsFiltered']);
        $this->assertCount(1, $rows);
        $this->assertSame('Pelanggan Kantor', $rows->first()['nama_customer']);
    }

    public function test_data_without_global_search_returns_all_matching_rows(): void
    {
        $user = $this->utilitiesUser();
        $periode = '2026-09';
        $this->seedBillsForLokasiTest($periode);

        $response = $this->actingAs($user)
            ->getJson($this->dataTablesUrl(['periode' => $periode]));

        $response->assertOk();
        $payload = $response->json();
        $this->assertSame(2, $payload['recordsFiltered']);
        $this->assertSame(2, $payload['recordsTotal']);
        $this->assertCount(2, $payload['data']);
    }

    public function test_lokasi_filter_works_combined_with_global_search(): void
    {
        $user = $this->utilitiesUser();
        $periode = '2026-09';
        $this->seedBillsForLokasiTest($periode);

        $response = $this->actingAs($user)
            ->getJson($this->dataTablesUrl([
                'periode' => $periode,
                'lokasi' => 'Mess',
                'search' => [
                    'value' => 'Pelanggan',
                    'regex' => 'false',
                ],
            ]));

        $response->assertOk();
        $payload = $response->json();
        $rows = collect($payload['data']);
        $this->assertSame(1, $payload['recordsFiltered']);
        $this->assertCount(1, $rows);
        $this->assertSame('Mess GBR', $rows->first()['lokasi']);
        $this->assertStringContainsString('Pelanggan', $rows->first()['nama_customer']);
    }

    public function test_index_view_has_lokasi_column_filter_and_ajax_parameter(): void
    {
        $user = $this->utilitiesUser();

        $this->actingAs($user)
            ->get(route('utilities.bills.index'))
            ->assertOk()
            ->assertSee('>Lokasi</th>', false)
            ->assertSee('id="filter_lokasi"', false)
            ->assertSee('Cari lokasi...', false)
            ->assertSee("d.lokasi = $('#filter_lokasi').val();", false)
            ->assertSee('searchPlaceholder', false)
            ->assertSee('nama pelanggan, lokasi, ID pelanggan, periode, nomor token', false);
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
            'order' => [
                ['column' => 6, 'dir' => 'desc'],
            ],
            'search' => [
                'value' => '',
                'regex' => 'false',
            ],
        ], $extra));
    }

    protected function seedBillsForLokasiTest(string $periode): void
    {
        $account = Account::query()->create([
            'account_number' => '61208001',
            'account_name' => 'Beban Utilitas',
            'type' => 'expense',
            'sap_account' => '61208001',
            'is_active' => true,
            'is_hidden' => false,
        ]);

        $customerMess = UtilityCustomer::query()->create([
            'jenis_utilitas' => 'pln',
            'tipe' => 'postpaid',
            'id_pelanggan' => 'PLN-MESS-001',
            'nama' => 'Pelanggan Mess',
            'lokasi' => 'Mess GBR',
            'project' => '000H',
            'department' => '20',
            'account_id' => $account->id,
            'is_active' => true,
        ]);

        $customerKantor = UtilityCustomer::query()->create([
            'jenis_utilitas' => 'pln',
            'tipe' => 'postpaid',
            'id_pelanggan' => 'PLN-KTR-001',
            'nama' => 'Pelanggan Kantor',
            'lokasi' => 'Kantor Pusat',
            'project' => '000H',
            'department' => '20',
            'account_id' => $account->id,
            'is_active' => true,
        ]);

        UtilityBill::query()->create([
            'utility_customer_id' => $customerMess->id,
            'periode' => $periode,
            'jumlah_tagihan' => 100000,
            'tanggal_jatuh_tempo' => $periode.'-20',
        ]);

        UtilityBill::query()->create([
            'utility_customer_id' => $customerKantor->id,
            'periode' => $periode,
            'jumlah_tagihan' => 200000,
            'tanggal_jatuh_tempo' => $periode.'-20',
        ]);
    }

    protected function utilitiesUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('akses_utilities');

        return $user;
    }
}
