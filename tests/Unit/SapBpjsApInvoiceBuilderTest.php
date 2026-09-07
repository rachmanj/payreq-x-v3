<?php

namespace Tests\Unit;

use App\Models\BpjsApInvoice;
use App\Models\SapBusinessPartner;
use App\Services\SapBpjsApInvoiceBuilder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SapBpjsApInvoiceBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected SapBusinessPartner $kesehatanPartner;

    protected SapBusinessPartner $tkPartner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kesehatanPartner = SapBusinessPartner::query()->create([
            'code' => 'VBPKEIDR01',
            'name' => 'BPJS KESEHATAN',
            'type' => SapBusinessPartner::TYPE_SUPPLIER,
            'active' => true,
        ]);

        $this->tkPartner = SapBusinessPartner::query()->create([
            'code' => 'VBPTKIDR01',
            'name' => 'BPJS KETENAGAKERJAAN',
            'type' => SapBusinessPartner::TYPE_SUPPLIER,
            'active' => true,
        ]);
    }

    public function test_build_payload_maps_kesehatan_ho_correctly(): void
    {
        $invoice = $this->makeInvoice([
            'jenis' => BpjsApInvoice::JENIS_KESEHATAN,
            'unit' => '000H',
            'periode' => '2026-10',
            'amount' => 15000000,
            'doc_date' => '2026-09-07',
            'due_date' => '2026-10-07',
            'num_at_card' => '10/26',
            'label' => 'BPJS Kesehatan HO per Oktober 2026',
        ]);

        $builder = new SapBpjsApInvoiceBuilder($invoice);
        $this->assertSame([], $builder->validate());

        $payload = $builder->build();

        $this->assertSame('VBPKEIDR01', $payload['CardCode']);
        $this->assertSame('dDocument_Service', $payload['DocType']);
        $this->assertSame('2026-09-07', $payload['DocDate']);
        $this->assertSame('2026-10-07', $payload['DocDueDate']);
        $this->assertSame('2026-09-07', $payload['TaxDate']);
        $this->assertSame('IDR', $payload['DocCurrency']);
        $this->assertSame('10/26', $payload['NumAtCard']);
        $this->assertSame('BPJS Kesehatan HO per Oktober 2026', $payload['Comments']);
        $this->assertSame('20', $payload['U_MIS_CCDepartement']);
        $this->assertSame('2026-09-07', $payload['U_MIS_FPDate']);
        $this->assertCount(1, $payload['DocumentLines']);
        $this->assertSame('61201004', $payload['DocumentLines'][0]['AccountCode']);
        $this->assertSame('BPJS Kesehatan HO per Oktober 2026', $payload['DocumentLines'][0]['ItemDescription']);
        $this->assertSame(1, $payload['DocumentLines'][0]['Quantity']);
        $this->assertSame(15000000.0, $payload['DocumentLines'][0]['UnitPrice']);
        $this->assertSame(15000000.0, $payload['DocumentLines'][0]['LineTotal']);
        $this->assertSame('B100', $payload['DocumentLines'][0]['VatGroup']);
        $this->assertSame('tNO', $payload['DocumentLines'][0]['WTLiable']);
        $this->assertSame('20', $payload['DocumentLines'][0]['CostingCode']);
        $this->assertSame('000H', $payload['DocumentLines'][0]['ProjectCode']);
    }

    public function test_build_payload_maps_ketenagakerjaan_ns_correctly(): void
    {
        $invoice = $this->makeInvoice([
            'jenis' => BpjsApInvoice::JENIS_KETENAGAKERJAAN,
            'unit' => '022C',
            'periode' => '2026-09',
            'amount' => 8000000,
            'doc_date' => '2026-09-01',
            'due_date' => '2026-10-01',
            'num_at_card' => '9/26',
            'label' => 'BPJS Ketenagakerjaan NS 022C per September 2026',
        ]);

        $builder = new SapBpjsApInvoiceBuilder($invoice);
        $payload = $builder->build();

        $this->assertSame('VBPTKIDR01', $payload['CardCode']);
        $this->assertSame('61201003', $payload['DocumentLines'][0]['AccountCode']);
        $this->assertSame('022C', $payload['DocumentLines'][0]['ProjectCode']);
        $this->assertSame('BPJS Ketenagakerjaan NS 022C per September 2026', $payload['Comments']);
    }

    public function test_num_at_card_appends_suffix_when_already_used(): void
    {
        BpjsApInvoice::factory()->create([
            'num_at_card' => '10/26',
            'periode' => '2026-10',
        ]);

        $this->assertSame('10/26-2', SapBpjsApInvoiceBuilder::buildNumAtCard('2026-10'));
    }

    public function test_validate_rejects_empty_amount_and_duplicate_num_at_card(): void
    {
        BpjsApInvoice::factory()->create(['num_at_card' => '10/26']);

        $invoice = $this->makeInvoice([
            'amount' => 0,
            'num_at_card' => '10/26',
        ]);

        $errors = (new SapBpjsApInvoiceBuilder($invoice))->validate();
        $this->assertTrue(collect($errors)->contains(fn (string $e) => str_contains($e, 'lebih dari nol')));
        $this->assertTrue(collect($errors)->contains(fn (string $e) => str_contains($e, 'sudah dipakai')));
    }

    public function test_build_label_uses_indonesian_month_names(): void
    {
        $label = SapBpjsApInvoiceBuilder::buildLabel(
            BpjsApInvoice::JENIS_KESEHATAN,
            '000H',
            '2026-01'
        );

        $this->assertSame('BPJS Kesehatan HO per Januari 2026', $label);
    }

    public function test_last_amount_previous_month_from_january(): void
    {
        BpjsApInvoice::factory()->create([
            'jenis' => BpjsApInvoice::JENIS_KESEHATAN,
            'unit' => '000H',
            'periode' => '2025-12',
            'amount' => 1234567,
        ]);

        $previousPeriode = Carbon::createFromFormat('Y-m', '2026-01')
            ->startOfMonth()
            ->subMonth()
            ->format('Y-m');

        $this->assertSame('2025-12', $previousPeriode);

        $previous = BpjsApInvoice::query()
            ->where('jenis', BpjsApInvoice::JENIS_KESEHATAN)
            ->where('unit', '000H')
            ->where('periode', $previousPeriode)
            ->first();

        $this->assertNotNull($previous);
        $this->assertSame(1234567.0, (float) $previous->amount);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeInvoice(array $overrides = []): BpjsApInvoice
    {
        return BpjsApInvoice::factory()->make(array_merge([
            'jenis' => BpjsApInvoice::JENIS_KESEHATAN,
            'unit' => '000H',
            'periode' => '2026-10',
            'amount' => 1000000,
            'doc_date' => '2026-09-07',
            'due_date' => '2026-10-07',
            'num_at_card' => '10/26',
            'label' => 'BPJS Kesehatan HO per Oktober 2026',
            'status' => BpjsApInvoice::STATUS_PENDING,
        ], $overrides));
    }
}
