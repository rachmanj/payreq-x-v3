<?php

namespace Tests\Feature;

use App\Models\Bapsb;
use App\Models\Bilyet;
use App\Models\Dokumen;
use App\Models\Giro;
use App\Models\User;
use App\Services\BapsbComplianceService;
use App\Services\BapsbService;
use Carbon\Carbon;
use Database\Seeders\BapsbPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BapsbFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected Giro $giroGiro;

    protected Giro $giroTabungan;

    protected User $cashier;

    protected User $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BapsbPermissionsSeeder::class);

        Role::query()->firstOrCreate(['name' => 'cashier'], ['guard_name' => 'web']);

        $bankId = DB::table('banks')->insertGetId([
            'name' => 'Mandiri',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->giroGiro = Giro::query()->create([
            'acc_no' => '149-001',
            'acc_name' => 'Giro Account',
            'bank_id' => $bankId,
            'type' => 'giro',
            'project' => '021C',
            'sap_account' => '11201005',
            'needs_bilyet' => true,
        ]);

        $this->giroTabungan = Giro::query()->create([
            'acc_no' => '149-002',
            'acc_name' => 'Tabungan',
            'bank_id' => $bankId,
            'type' => 'tabungan',
            'project' => '021C',
            'sap_account' => '11201006',
            'needs_bilyet' => false,
        ]);

        $this->cashier = User::factory()->create(['project' => '021C']);
        $this->cashier->givePermissionTo('akses_bapsb');

        $this->validator = User::factory()->create(['project' => '000H']);
        $this->validator->givePermissionTo('validate_bapsb_report');
    }

    #[Test]
    public function period_query_includes_onhand_release_and_excludes_tabungan_and_debit(): void
    {
        $period = '2024-06';
        $service = app(BapsbService::class);

        $included = Bilyet::query()->create([
            'giro_id' => $this->giroGiro->id,
            'prefix' => 'A',
            'nomor' => '1',
            'type' => 'cek',
            'bilyet_date' => '2024-06-15',
            'amount' => 1_000_000,
            'status' => 'onhand',
            'project' => '021C',
        ]);

        Bilyet::query()->create([
            'giro_id' => $this->giroGiro->id,
            'prefix' => 'A',
            'nomor' => '2',
            'type' => 'bg',
            'bilyet_date' => '2024-06-20',
            'amount' => 2_000_000,
            'status' => 'release',
            'project' => '021C',
        ]);

        Bilyet::query()->create([
            'giro_id' => $this->giroTabungan->id,
            'prefix' => 'T',
            'nomor' => '1',
            'type' => 'cek',
            'bilyet_date' => '2024-06-10',
            'amount' => 500_000,
            'status' => 'onhand',
            'project' => '021C',
        ]);

        Bilyet::query()->create([
            'giro_id' => $this->giroGiro->id,
            'prefix' => 'D',
            'nomor' => '1',
            'type' => 'debit',
            'bilyet_date' => '2024-06-10',
            'amount' => 100_000,
            'status' => 'onhand',
            'project' => '021C',
        ]);

        Bilyet::query()->create([
            'giro_id' => $this->giroGiro->id,
            'prefix' => 'C',
            'nomor' => '1',
            'type' => 'cek',
            'bilyet_date' => '2024-06-01',
            'cair_date' => '2024-06-25',
            'amount' => 3_000_000,
            'status' => 'cair',
            'project' => '021C',
        ]);

        $list = $service->bilyetsForPeriod('021C', $period);
        $this->assertCount(2, $list);
        $this->assertTrue($list->contains('id', $included->id));

        $mutations = $service->mutationCountsForPeriod('021C', $period);
        $this->assertSame(1, $mutations['count_cair']);
        $this->assertSame(0, $mutations['count_void']);
    }

    #[Test]
    public function nomor_is_unique_per_project_and_period(): void
    {
        $service = app(BapsbService::class);
        $first = $service->generateNomor('021C', '2024-07');
        $this->assertSame('BAPSB-0001/021C/07-2024', $first);

        Bapsb::query()->create([
            'nomor' => $first,
            'period' => '2024-07',
            'project' => '021C',
            'bapsb_date' => '2024-07-31',
            'prepared_by' => $this->cashier->id,
            'checker1' => 'A',
            'checker2' => 'B',
        ]);

        $second = $service->generateNomor('021C', '2024-07');
        $this->assertSame('BAPSB-0002/021C/07-2024', $second);
    }

    #[Test]
    public function cashier_can_save_draft_edit_and_upload_pdf(): void
    {
        $bilyet = $this->seedOnhandBilyet('2024-08-10');
        $payload = $this->linePayload($bilyet);

        $response = $this->actingAs($this->cashier)->post(route('cashier.bapsb.store'), [
            'period' => '2024-08',
            'project' => '021C',
            'bapsb_date' => '2024-08-31',
            'checker1' => 'Inspector 1',
            'checker2' => 'Inspector 2',
            'lines' => [$payload],
        ]);

        $response->assertRedirect();
        $bapsb = Bapsb::query()->first();
        $this->assertNotNull($bapsb);
        $this->assertNull($bapsb->submitted_at);

        $this->actingAs($this->cashier)->put(route('cashier.bapsb.update', $bapsb), [
            'bapsb_date' => '2024-08-31',
            'checker1' => 'Inspector 1',
            'checker2' => 'Inspector 2 updated',
            'lines' => [[
                'bilyet_id' => $bilyet->id,
                'physical_present' => 0,
                'location' => 'Brankas Site',
                'location_note' => 'Vault A',
                'remarks' => 'OK',
            ]],
        ])->assertRedirect();

        $bapsb->refresh();
        $this->assertSame('Inspector 2 updated', $bapsb->checker2);
        $this->assertFalse($bapsb->lines->first()->physical_present);

        $file = UploadedFile::fake()->create('signed.pdf', 100, 'application/pdf');
        $this->actingAs($this->cashier)->post(route('cashier.bapsb.upload', $bapsb), [
            'attachment' => $file,
        ])->assertRedirect();

        $bapsb->refresh();
        $this->assertNotNull($bapsb->dokumen_id);
        $this->assertSame('bapsb', Dokumen::query()->find($bapsb->dokumen_id)->type);
    }

    #[Test]
    public function submit_and_validate_flow_respects_permissions(): void
    {
        $bilyet = $this->seedOnhandBilyet('2024-09-05');
        $bapsb = $this->createDraftBapsb('2024-09', $bilyet);

        $this->attachFakePdf($bapsb);

        $this->actingAs($this->cashier)->post(route('cashier.bapsb.submit', $bapsb))->assertRedirect();
        $bapsb->refresh();
        $this->assertNotNull($bapsb->submitted_at);

        $this->actingAs($this->cashier)
            ->from(route('cashier.bapsb.show', $bapsb))
            ->put(route('cashier.bapsb.validate', $bapsb))
            ->assertRedirect()
            ->assertSessionHas('alert_message');

        $this->actingAs($this->validator)->put(route('cashier.bapsb.validate', $bapsb))->assertRedirect();
        $bapsb->refresh();
        $this->assertSame(Bapsb::VALIDATION_VALIDATED, $bapsb->validation_status);
    }

    #[Test]
    public function print_page_renders_signature_blocks(): void
    {
        $bilyet = $this->seedOnhandBilyet('2024-10-01');
        $bapsb = $this->createDraftBapsb('2024-10', $bilyet);

        $this->actingAs($this->cashier)
            ->get(route('cashier.bapsb.print', $bapsb))
            ->assertOk()
            ->assertSee('Prepared by', false)
            ->assertSee('Checked by 1', false)
            ->assertSee('Checked by 2', false)
            ->assertSee('Approved by', false);
    }

    #[Test]
    public function outstanding_lists_late_units(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-11-10'));

        $service = app(BapsbComplianceService::class);
        $late = $service->outstandingSubmissions();
        $this->assertNotEmpty($late);
        $this->assertTrue(collect($late)->contains(fn ($row) => $row['project'] === '021C' && $row['period'] === '2024-09'));

        $this->actingAs($this->validator)
            ->get(route('cashier.bapsb.outstanding'))
            ->assertOk()
            ->assertSee('021C');
    }

    #[Test]
    public function access_without_permission_is_denied(): void
    {
        $user = User::factory()->create(['project' => '021C']);

        $this->actingAs($user)
            ->from('/dashboard')
            ->get(route('cashier.bapsb.index'))
            ->assertRedirect()
            ->assertSessionHas('alert_message');
    }

    protected function seedOnhandBilyet(string $date): Bilyet
    {
        return Bilyet::query()->create([
            'giro_id' => $this->giroGiro->id,
            'prefix' => 'X',
            'nomor' => (string) random_int(1000, 9999),
            'type' => 'cek',
            'bilyet_date' => $date,
            'amount' => 1_500_000,
            'status' => 'onhand',
            'project' => '021C',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function linePayload(Bilyet $bilyet): array
    {
        return [
            'bilyet_id' => $bilyet->id,
            'physical_present' => 1,
            'location' => 'Brankas Site',
            'location_note' => null,
            'remarks' => null,
        ];
    }

    protected function createDraftBapsb(string $period, Bilyet $bilyet): Bapsb
    {
        $service = app(BapsbService::class);
        $summary = $service->summarizeBilyets(collect([$bilyet]));
        $mutations = $service->mutationCountsForPeriod('021C', $period);

        $bapsb = Bapsb::query()->create([
            'nomor' => $service->generateNomor('021C', $period),
            'period' => $period,
            'project' => '021C',
            'bapsb_date' => $period.'-28',
            'prepared_by' => $this->cashier->id,
            'checker1' => 'C1',
            'checker2' => 'C2',
            ...$summary,
            ...$mutations,
        ]);

        $service = app(BapsbService::class);
        \App\Models\BapsbLine::query()->create([
            'bapsb_id' => $bapsb->id,
            'bilyet_id' => $bilyet->id,
            'type' => $bilyet->type,
            'nomor' => $bilyet->prefix.$bilyet->nomor,
            'bank_account' => $service->bankAccountLabel($bilyet),
            'bilyet_date' => $bilyet->bilyet_date,
            'amount' => (int) $bilyet->amount,
            'status' => $bilyet->status,
            'physical_present' => true,
            'location' => 'Brankas Site',
        ]);

        return $bapsb->fresh('lines');
    }

    protected function attachFakePdf(Bapsb $bapsb): void
    {
        $dokumen = Dokumen::query()->create([
            'filename1' => 'test.pdf',
            'type' => 'bapsb',
            'project' => $bapsb->project,
            'dokumen_date' => $bapsb->bapsb_date,
            'created_by' => $this->cashier->id,
            'validation_status' => Dokumen::VALIDATION_PENDING,
        ]);
        $bapsb->update(['dokumen_id' => $dokumen->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
