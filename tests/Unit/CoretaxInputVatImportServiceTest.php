<?php

namespace Tests\Unit;

use App\Models\CoretaxInputVat;
use App\Models\Customer;
use App\Models\Faktur;
use App\Models\User;
use App\Services\CoretaxInputVatImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class CoretaxInputVatImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private CoretaxInputVatImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CoretaxInputVatImportService;
    }

    public function test_masa_pajak_from_september_and_year(): void
    {
        $this->assertSame('2026-09', $this->service->masaPajakFromMonthYear('September', '2026'));
        $this->assertSame('2026-09', $this->service->masaPajakFromMonthYear('september', '2026'));
        $this->assertSame('2026-01', $this->service->masaPajakFromMonthYear('Januari', '2026'));
    }

    public function test_map_row_dpp_and_ppn_match_eleven_twelfths_formula(): void
    {
        $row = $this->service->mapRow([
            'Nomor Faktur Pajak' => '04002600397729426',
            'NPWP Penjual' => '0013315965046000',
            'Nama Penjual' => 'INDOTRUCK UTAMA',
            'Tanggal Faktur Pajak' => '2026-09-28T00:00:00',
            'Masa Pajak' => 'September',
            'Tahun' => '2026',
            'Masa Pajak Pengkreditkan' => '',
            'Tahun Pajak Pengkreditan' => '',
            'Status Faktur' => 'APPROVED',
            'Harga Jual/Penggantian/DPP' => '25000000',
            'DPP Nilai Lain/DPP' => '22916667',
            'PPN' => '2750000',
            'PPnBM' => '0',
            'Perekam' => 'HERBERT',
            'Referensi' => 'PSI-2600002178',
            'Valid' => 'TRUE',
            'Dilaporkan' => 'FALSE',
        ], '2026-09');

        $this->assertNotNull($row);
        $this->assertSame(25_000_000.0, $row['nilai_bruto']);
        $this->assertSame(22_916_667.0, $row['dpp']);
        $this->assertSame(2_750_000.0, $row['ppn']);
        $expectedDpp = round(25_000_000 * 11 / 12, 2);
        $expectedPpn = round($expectedDpp * 0.12, 2);
        $this->assertEqualsWithDelta($expectedDpp, $row['dpp'], 1.0);
        $this->assertEqualsWithDelta($expectedPpn, $row['ppn'], 1.0);
    }

    public function test_rejects_invalid_header(): void
    {
        $path = $this->writeFixtureSpreadsheet(['Kolom Salah'], []);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->parseSpreadsheet($path);
    }

    public function test_preview_and_idempotent_commit(): void
    {
        $user = User::factory()->create();
        $path = $this->writeSampleCoretaxSpreadsheet();
        $file = new UploadedFile($path, 'coretax.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $preview = $this->service->preview($file, '2026-09');
        $this->assertTrue($preview['success']);
        $this->assertSame(3, $preview['summary']['row_count']);
        $this->assertSame(2_750_000.0 + 88_000.0 + 1_693_670.0, $preview['summary']['total_ppn']);

        $first = $this->service->commit($preview['preview_token'], $user);
        $this->assertTrue($first['success']);
        $this->assertSame(3, $first['imported']);
        $this->assertSame(3, CoretaxInputVat::query()->where('masa_pajak', '2026-09')->count());

        $file2 = new UploadedFile($path, 'coretax.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        $preview2 = $this->service->preview($file2, '2026-09');
        $second = $this->service->commit($preview2['preview_token'], $user);
        $this->assertTrue($second['success']);
        $this->assertSame(3, CoretaxInputVat::query()->where('masa_pajak', '2026-09')->count());
    }

    public function test_sync_updates_faktur_coretax_status_without_creating_faktur(): void
    {
        $user = User::factory()->create();
        $customer = Customer::query()->create([
            'code' => 'V001',
            'name' => 'INDOTRUCK UTAMA',
            'type' => 'vendor',
        ]);

        Faktur::query()->create([
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-X',
            'invoice_date' => '2026-09-28',
            'type' => 'purchase',
            'masa_pajak' => '2026-09',
            'faktur_no' => '04002600397729426',
            'dpp' => 22_916_667,
            'ppn' => 2_750_000,
            'coretax_status' => 'belum_diketahui',
            'created_by' => $user->id,
        ]);

        $beforeCount = Faktur::query()->count();
        $path = $this->writeSampleCoretaxSpreadsheet();
        $file = new UploadedFile($path, 'coretax.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        $preview = $this->service->preview($file, '2026-09');
        $this->service->commit($preview['preview_token'], $user);

        $this->assertSame($beforeCount, Faktur::query()->count());
        $faktur = Faktur::query()->where('faktur_no', '04002600397729426')->first();
        $this->assertSame('approved', $faktur->coretax_status);
    }

    public function test_preview_token_expires(): void
    {
        $result = $this->service->commit('invalid-token', User::factory()->create());
        $this->assertFalse($result['success']);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $dataRows
     */
    private function writeFixtureSpreadsheet(array $headers, array $dataRows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('data');

        foreach ($headers as $col => $label) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $label);
        }

        foreach ($dataRows as $rowIndex => $row) {
            foreach ($row as $col => $value) {
                $sheet->setCellValueByColumnAndRow($col + 1, $rowIndex + 2, $value);
            }
        }

        $path = sys_get_temp_dir().'/coretax-fixture-'.uniqid('', true).'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    private function writeSampleCoretaxSpreadsheet(): string
    {
        $headers = array_merge([''], CoretaxInputVatImportService::EXPECTED_HEADERS);

        $rows = [
            ['', '0013315965046000', 'INDOTRUCK UTAMA', '04002600397729426', '2026-09-28T00:00:00', 'September', '2026', '', '', 'APPROVED', '25000000', '22916667', '2750000', '0', 'HERBERT ALFRIANDO PARAPAT', 'PSI-2600002178', '', 'TRUE', 'FALSE', 'FALSE', ''],
            ['', '0209038454025000', 'PT SINAR CIPTA TEKNIKA', '04002600375242773', '2026-09-15T00:00:00', 'September', '2026', 'September', '2026', 'CREDITED', '800000', '733333', '88000', '0', 'ERNI SEPTIANINGSIH', 'SI.2026.09.00079', '', 'TRUE', 'FALSE', 'FALSE', ''],
            ['', '0020258737091000', 'TRAKINDO UTAMA', '04002600372947906', '2026-09-14T00:00:00', 'September', '2026', 'September', '2026', 'CREDITED', '15397000', '14113917', '1693670', '0', 'MUHAMMAD YANUAR', '5311652909-260206277', '', 'TRUE', 'FALSE', 'FALSE', ''],
        ];

        return $this->writeFixtureSpreadsheet($headers, $rows);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}
