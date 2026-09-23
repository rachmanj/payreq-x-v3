<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use App\Models\UtilityBill;
use App\Models\UtilityCustomer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UtilityBillCreateEditViewTest extends TestCase
{
    use RefreshDatabase;

    public const SAMPLE_LOKASI = 'Gudang Utama Bandung';

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('akses_utilities', 'web');
    }

    public function test_create_page_shows_customer_location_in_option_and_info_panel(): void
    {
        $user = $this->utilitiesUser();
        $customer = $this->createSampleCustomer();

        $this->actingAs($user)
            ->get(route('utilities.bills.create'))
            ->assertOk()
            ->assertSee(' · '.self::SAMPLE_LOKASI.' (', false)
            ->assertSee('utility-customer-info', false)
            ->assertSee('Lokasi:', false)
            ->assertSee($customer->id_pelanggan, false);
    }

    public function test_edit_page_shows_customer_location_in_option_and_info_panel(): void
    {
        $user = $this->utilitiesUser();
        $customer = $this->createSampleCustomer();
        $bill = UtilityBill::query()->create([
            'utility_customer_id' => $customer->id,
            'periode' => '2026-08',
            'jumlah_tagihan' => 500000,
            'tanggal_jatuh_tempo' => '2026-08-20',
        ]);

        $this->actingAs($user)
            ->get(route('utilities.bills.edit', $bill))
            ->assertOk()
            ->assertSee(' · '.self::SAMPLE_LOKASI.' (', false)
            ->assertSee('utility-customer-info', false)
            ->assertSee('Lokasi:', false)
            ->assertSee($customer->id_pelanggan, false);
    }

    public function test_create_page_includes_location_search_placeholder_and_select2_matcher(): void
    {
        $user = $this->utilitiesUser();
        $this->createSampleCustomer();

        $this->actingAs($user)
            ->get(route('utilities.bills.create'))
            ->assertOk()
            ->assertSee('Cari nama, lokasi, ID pelanggan, atau no. meter...', false)
            ->assertSee('customerSelectMatcher', false)
            ->assertSee('data-nama', false)
            ->assertSee('data-lokasi', false)
            ->assertSee('data-nomor-meter', false);
    }

    public function test_create_page_shows_tipe_pembayaran_before_id_pelanggan(): void
    {
        $user = $this->utilitiesUser();
        $this->createSampleCustomer();

        $response = $this->actingAs($user)->get(route('utilities.bills.create'));
        $response->assertOk();

        $html = $response->getContent();
        $tipePos = strpos($html, 'id="tipe"');
        $customerPos = strpos($html, 'id="utility_customer_id"');

        $this->assertNotFalse($tipePos);
        $this->assertNotFalse($customerPos);
        $this->assertLessThan($customerPos, $tipePos);

        $response
            ->assertSee('Tipe Pembayaran', false)
            ->assertSee('ID Pelanggan', false)
            ->assertSee('text-danger">*</span>', false);
    }

    protected function utilitiesUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('akses_utilities');

        return $user;
    }

    protected function createSampleCustomer(): UtilityCustomer
    {
        $account = Account::query()->create([
            'account_number' => '61208001',
            'account_name' => 'Beban Utilitas',
            'type' => 'expense',
            'sap_account' => '61208001',
            'is_active' => true,
            'is_hidden' => false,
        ]);

        return UtilityCustomer::query()->create([
            'jenis_utilitas' => 'pln',
            'tipe' => 'postpaid',
            'id_pelanggan' => 'PLN-LOC-TEST-001',
            'nama' => 'Pelanggan Lokasi Test',
            'lokasi' => self::SAMPLE_LOKASI,
            'project' => '000H',
            'department' => '20',
            'account_id' => $account->id,
            'is_active' => true,
        ]);
    }
}
