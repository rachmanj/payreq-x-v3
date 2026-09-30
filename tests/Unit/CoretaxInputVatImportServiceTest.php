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

        try {
            $this->service->parseSpreadsheet($path);
            $this->fail('Expected InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Header berkas Coretax tidak dikenali', $e->getMessage());
            $this->assertStringContainsString('Header terbaca:', $e->getMessage());
            $this->assertStringContainsString('Kolom inti belum ada:', $e->getMessage());
            $this->assertStringContainsString('Indonesia maupun Inggris', $e->getMessage());
        }
    }

    public function test_preview_accepts_english_csv_headers(): void
    {
        $path = $this->writeEnglishCoretaxCsv();
        $file = new UploadedFile($path, 'coretax-en.csv', 'text/csv', null, true);

        $preview = $this->service->preview($file, '2026-09');
        $this->assertTrue($preview['success']);
        $this->assertSame(1, $preview['summary']['row_count']);
        $this->assertGreaterThan(0, $preview['summary']['total_ppn']);
        $this->assertSame(2_750_000.0, $preview['summary']['total_ppn']);
        $firstRow = $preview['rows'][0];
        $this->assertSame('04002600397729426', $firstRow['faktur_no']);
        $this->assertMatchesRegularExpression('/^\d{17}$/', $firstRow['faktur_no']);
        $this->assertSame(22_916_667.0, $firstRow['dpp']);
        $this->assertSame(2_750_000.0, $firstRow['ppn']);
        $this->assertSame('0013315965046000', $firstRow['npwp']);
        $this->assertSame('INDOTRUCK UTAMA', $firstRow['supplier_name']);
    }

    public function test_english_csv_with_one_leading_empty_in_data_row_still_maps_by_header_name(): void
    {
        $path = $this->writeEnglishCoretaxCsvMisalignedDataRow();
        $parsed = $this->service->parseCsv($path);
        $this->assertCount(1, $parsed['rows']);
        $line = $parsed['rows'][0];

        $this->assertSame('0013315965046000', $line['NPWP Penjual']);
        $this->assertSame('PT INDO PERKASA MANDIRI', $line['Nama Penjual']);
        $this->assertSame('04002600319206612', $line['Nomor Faktur Pajak']);
        $this->assertSame('1888333', $line['DPP Nilai Lain/DPP']);
        $this->assertSame('226600', $line['PPN']);
        $this->assertSame('2060000', $line['Harga Jual/Penggantian/DPP']);
        $this->assertSame('APPROVED', $line['Status Faktur']);

        $mapped = $this->service->mapRow($line, '2026-08');
        $this->assertNotNull($mapped);
        $this->assertSame('04002600319206612', $mapped['faktur_no']);
        $this->assertSame(2_060_000.0, $mapped['nilai_bruto']);
        $this->assertSame(1_888_333.0, $mapped['dpp']);
        $this->assertSame(226_600.0, $mapped['ppn']);
    }

    public function test_preview_english_csv_skips_other_masa(): void
    {
        $path = $this->writeEnglishCoretaxCsvMixedMasa();
        $file = new UploadedFile($path, 'coretax-en-mixed.csv', 'text/csv', null, true);

        $preview = $this->service->preview($file, '2026-09');
        $this->assertTrue($preview['success']);
        $this->assertSame(2, $preview['summary']['row_count']);
        $this->assertGreaterThan(0, $preview['summary']['total_ppn']);
        $skipped = $preview['summary']['skipped_messages'] ?? [];
        $this->assertCount(1, $skipped);
        $this->assertStringContainsString('1 baris dilewati', $skipped[0]);
        $this->assertStringContainsString('Agustus 2026', $skipped[0]);
    }

    public function test_preview_skips_rows_from_other_masa_and_reports_summary(): void
    {
        $path = $this->writeMixedMasaSpreadsheet();
        $file = new UploadedFile($path, 'coretax-mixed.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $preview = $this->service->preview($file, '2026-09');
        $this->assertTrue($preview['success']);
        $this->assertSame(2, $preview['summary']['row_count']);
        $skipped = $preview['summary']['skipped_messages'] ?? [];
        $this->assertCount(1, $skipped);
        $this->assertStringContainsString('12 baris dilewati', $skipped[0]);
        $this->assertStringContainsString('Agustus 2026', $skipped[0]);
    }

    public function test_preview_and_idempotent_commit(): void
    {
        $user = User::factory()->create();
        $path = $this->writeSampleCoretaxSpreadsheet();
        $file = new UploadedFile($path, 'coretax.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $preview = $this->service->preview($file, '2026-09');
        $this->assertTrue($preview['success']);
        $this->assertSame(3, $preview['summary']['row_count']);
        $this->assertGreaterThan(0, $preview['summary']['total_ppn']);
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

    private function writeEnglishCoretaxCsv(): string
    {
        $headers = $this->englishCoretaxHeaderRow();
        $row = $this->englishCoretaxSampleRow();

        $path = sys_get_temp_dir().'/coretax-en-'.uniqid('', true).'.csv';
        $handle = fopen($path, 'w');
        fputcsv($handle, $headers);
        fputcsv($handle, $row);
        fclose($handle);

        return $path;
    }

    private function writeEnglishCoretaxCsvMisalignedDataRow(): string
    {
        $headers = $this->englishCoretaxHeaderRow();
        $row = $this->englishCoretaxOwnerSampleRow();
        array_shift($row);

        $path = sys_get_temp_dir().'/coretax-en-mis-'.uniqid('', true).'.csv';
        $handle = fopen($path, 'w');
        fputcsv($handle, $headers);
        fputcsv($handle, $row);
        fclose($handle);

        return $path;
    }

    private function writeEnglishCoretaxCsvMixedMasa(): string
    {
        $headers = $this->englishCoretaxHeaderRow();
        $sept = $this->englishCoretaxSampleRow();
        $aug = $this->englishCoretaxOwnerSampleRow();
        $aug[6] = 'Agustus';
        $aug[4] = '04002600375240001';

        $path = sys_get_temp_dir().'/coretax-en-mixed-'.uniqid('', true).'.csv';
        $handle = fopen($path, 'w');
        fputcsv($handle, $headers);
        fputcsv($handle, $sept);
        fputcsv($handle, $sept);
        fputcsv($handle, $aug);
        fclose($handle);

        return $path;
    }

    /**
     * Header ekspor CSV Coretax (EN): dua kolom kosong di depan, 21 kolom total.
     *
     * @return list<string>
     */
    private function englishCoretaxHeaderRow(): array
    {
        return [
            '',
            '',
            'SellerTIN',
            'SellerTaxpayerName',
            'TaxInvoiceNumber',
            'TaxInvoiceDate',
            'TaxInvoicePeriod',
            'TaxInvoiceYear',
            'PeriodCredit',
            'YearCredit',
            'TaxInvoiceStatus',
            'SellingPrice',
            'OtherTaxBase',
            'VAT',
            'STLG',
            'Signer',
            'Reference',
            'SP2DNumber',
            'Valid',
            'ReportedByBuyer',
            'ReportedBySeller',
        ];
    }

    /**
     * @return list<string>
     */
    private function englishCoretaxSampleRow(): array
    {
        return [
            '',
            '',
            '0013315965046000',
            'INDOTRUCK UTAMA',
            '04002600397729426',
            '2026-09-28T00:00:00',
            'September',
            '2026',
            '',
            '',
            'APPROVED',
            '25000000',
            '22916667',
            '2750000',
            '0',
            'HERBERT',
            'PSI-2600002178',
            '',
            'TRUE',
            'FALSE',
            'FALSE',
        ];
    }

    /**
     * Nilai contoh dari berkas pemilik (Agustus 2026).
     *
     * @return list<string>
     */
    private function englishCoretaxOwnerSampleRow(): array
    {
        return [
            '',
            '',
            '0013315965046000',
            'PT INDO PERKASA MANDIRI',
            '04002600319206612',
            '2026-08-12T00:00:00',
            'Agustus',
            '2026',
            '',
            '',
            'APPROVED',
            '2060000',
            '1888333',
            '226600',
            '0',
            'X',
            'REF',
            '',
            'TRUE',
            'FALSE',
            'FALSE',
        ];
    }

    private function writeMixedMasaSpreadsheet(): string
    {
        $headers = array_merge([''], CoretaxInputVatImportService::EXPECTED_HEADERS);
        $septRow = ['', '0013315965046000', 'INDOTRUCK UTAMA', '04002600397729426', '2026-09-28T00:00:00', 'September', '2026', '', '', 'APPROVED', '25000000', '22916667', '2750000', '0', 'HERBERT', 'PSI-1', '', 'TRUE', 'FALSE', 'FALSE', ''];
        $augRow = ['', '0209038454025000', 'PT AUG', '04002600375240001', '2026-08-15T00:00:00', 'Agustus', '2026', '', '', 'APPROVED', '1000', '917', '110', '0', 'X', 'REF', '', 'TRUE', 'FALSE', 'FALSE', ''];
        $dataRows = [$septRow, $septRow, $augRow];
        for ($i = 0; $i < 11; $i++) {
            $clone = $augRow;
            $clone[3] = '0400260037524000'.str_pad((string) (2 + $i), 2, '0', STR_PAD_LEFT);
            $dataRows[] = $clone;
        }

        return $this->writeFixtureSpreadsheet($headers, $dataRows);
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
