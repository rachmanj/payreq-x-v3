<?php

namespace App\Services;

use App\Models\CoretaxInputVat;
use App\Models\Faktur;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class CoretaxInputVatImportService
{
    /**
     * @var list<string>
     */
    public const EXPECTED_HEADERS = [
        'NPWP Penjual',
        'Nama Penjual',
        'Nomor Faktur Pajak',
        'Tanggal Faktur Pajak',
        'Masa Pajak',
        'Tahun',
        'Masa Pajak Pengkreditkan',
        'Tahun Pajak Pengkreditan',
        'Status Faktur',
        'Harga Jual/Penggantian/DPP',
        'DPP Nilai Lain/DPP',
        'PPN',
        'PPnBM',
        'Perekam',
        'Referensi',
        'Nomor SP2D',
        'Valid',
        'Dilaporkan',
        'Dilaporkan oleh Penjual',
        'IsShowClearName',
    ];

    /**
     * Header ekspor Coretax berbahasa Inggris → label kanonik (Indonesia).
     *
     * @var array<string, string>
     */
    public const HEADER_ALIASES_EN = [
        'SellerTIN' => 'NPWP Penjual',
        'SellerTaxpayerName' => 'Nama Penjual',
        'TaxInvoiceNumber' => 'Nomor Faktur Pajak',
        'TaxInvoiceDate' => 'Tanggal Faktur Pajak',
        'TaxInvoicePeriod' => 'Masa Pajak',
        'TaxInvoiceYear' => 'Tahun',
        'PeriodCredit' => 'Masa Pajak Pengkreditkan',
        'YearCredit' => 'Tahun Pajak Pengkreditan',
        'TaxInvoiceStatus' => 'Status Faktur',
        'SellingPrice' => 'Harga Jual/Penggantian/DPP',
        'OtherTaxBase' => 'DPP Nilai Lain/DPP',
        'VAT' => 'PPN',
        'STLG' => 'PPnBM',
        'Signer' => 'Perekam',
        'Reference' => 'Referensi',
        'SP2DNumber' => 'Nomor SP2D',
        'Valid' => 'Valid',
        'ReportedByBuyer' => 'Dilaporkan',
        'ReportedBySeller' => 'Dilaporkan oleh Penjual',
    ];

    /**
     * Kolom wajib (salah satu bahasa) untuk mengenali ekspor Coretax.
     *
     * @var list<string>
     */
    public const REQUIRED_CANONICAL_HEADERS = [
        'Nomor Faktur Pajak',
        'Tanggal Faktur Pajak',
        'Masa Pajak',
        'Tahun',
        'Harga Jual/Penggantian/DPP',
        'PPN',
        'Status Faktur',
    ];

    /**
     * @return array{success: bool, message?: string, preview_token?: string, summary?: array<string, mixed>, rows?: list<array<string, mixed>>}
     */
    public function preview(UploadedFile $file, string $masaPajak): array
    {
        if (preg_match('/^\d{4}-\d{2}$/', $masaPajak) !== 1) {
            return ['success' => false, 'message' => 'Masa pajak harus format YYYY-MM.'];
        }

        try {
            $parsed = $this->parseFile($file);
        } catch (\InvalidArgumentException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $rows = [];
        $skippedByMasa = [];
        foreach ($parsed['rows'] as $line) {
            $fakturNo = preg_replace('/\s+/', '', $line['Nomor Faktur Pajak'] ?? '') ?? '';
            if ($fakturNo === '') {
                continue;
            }

            $rowMasa = $this->masaPajakFromMonthYear(
                $line['Masa Pajak'] ?? '',
                $line['Tahun'] ?? '',
            );

            if ($rowMasa !== null && $rowMasa !== $masaPajak) {
                $skippedByMasa[$rowMasa] = ($skippedByMasa[$rowMasa] ?? 0) + 1;

                continue;
            }

            $mapped = $this->mapRow($line, $masaPajak);
            if ($mapped === null) {
                continue;
            }
            $rows[] = $mapped;
        }

        if ($rows === []) {
            $skipHint = $this->formatSkippedSummary($skippedByMasa);
            $message = 'Tidak ada baris data yang valid untuk masa impor '.$masaPajak.'.';
            if ($skipHint !== []) {
                $message .= ' '.implode(' ', $skipHint);
            }

            return ['success' => false, 'message' => $message];
        }

        $totalPpn = array_sum(array_column($rows, 'ppn'));
        $previewToken = Str::uuid()->toString();
        $skippedMessages = $this->formatSkippedSummary($skippedByMasa);

        Cache::put($this->cacheKey($previewToken), [
            'masa_pajak' => $masaPajak,
            'rows' => $rows,
            'filename' => $file->getClientOriginalName(),
        ], now()->addHours(2));

        return [
            'success' => true,
            'preview_token' => $previewToken,
            'summary' => [
                'row_count' => count($rows),
                'total_ppn' => round($totalPpn, 2),
                'masa_pajak' => $masaPajak,
                'skipped_messages' => $skippedMessages,
            ],
            'rows' => array_slice($rows, 0, 50),
        ];
    }

    /**
     * @return array{success: bool, message?: string, imported?: int, batch?: string}
     */
    public function commit(string $previewToken, User $user): array
    {
        $cached = Cache::get($this->cacheKey($previewToken));
        if (! is_array($cached) || empty($cached['rows'])) {
            return [
                'success' => false,
                'message' => 'Pratinjau tidak valid atau sudah kedaluwarsa. Unggah ulang berkas.',
            ];
        }

        $masaPajak = (string) $cached['masa_pajak'];
        $rows = $cached['rows'];
        $batch = 'CTX-'.now()->format('YmdHis');

        $imported = DB::transaction(function () use ($rows, $masaPajak, $batch, $user): int {
            $count = 0;
            foreach ($rows as $row) {
                $this->upsertRow($row, $masaPajak, $batch, $user);
                $count++;
            }

            return $count;
        });

        Cache::forget($this->cacheKey($previewToken));

        return [
            'success' => true,
            'imported' => $imported,
            'batch' => $batch,
            'masa_pajak' => $masaPajak,
            'message' => sprintf('%d baris prepopulasi Coretax disimpan untuk masa %s.', $imported, $masaPajak),
        ];
    }

    /**
     * @return array{headers: list<string>, rows: list<array<string, string>>}
     */
    public function parseFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'csv') {
            return $this->parseCsv($file->getRealPath());
        }

        if (in_array($extension, ['xlsx', 'xls'], true)) {
            return $this->parseSpreadsheet($file->getRealPath());
        }

        throw new \InvalidArgumentException('Format berkas tidak didukung. Gunakan .xlsx atau .csv.');
    }

    /**
     * @return array{headers: list<string>, rows: list<array<string, string>>}
     */
    public function parseSpreadsheet(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName('data') ?? $spreadsheet->getActiveSheet();

        $headerRow = [];
        $highestColumn = $sheet->getHighestDataColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $headerRow[] = trim((string) $sheet->getCellByColumnAndRow($col, 1)->getValue());
        }

        $this->assertHeaders($headerRow);

        $headerMap = $this->headerIndexMap($headerRow);
        $rows = [];
        $highestRow = (int) $sheet->getHighestDataRow();

        for ($rowNum = 2; $rowNum <= $highestRow; $rowNum++) {
            $line = [];
            foreach ($headerMap as $name => $index) {
                $cell = $sheet->getCellByColumnAndRow($index + 1, $rowNum);
                $value = $cell->getValue();
                if (Date::isDateTime($cell) && is_numeric($value)) {
                    $value = Date::excelToDateTimeObject((float) $value)->format('Y-m-d\TH:i:s');
                }
                $line[$name] = trim((string) $value);
            }
            if ($this->rowIsEmpty($line)) {
                continue;
            }
            $rows[] = $line;
        }

        return ['headers' => $headerRow, 'rows' => $rows];
    }

    /**
     * @return array{headers: list<string>, rows: list<array<string, string>>}
     */
    public function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \InvalidArgumentException('Berkas CSV tidak dapat dibaca.');
        }

        $headerRow = fgetcsv($handle);
        if ($headerRow === false) {
            fclose($handle);
            throw new \InvalidArgumentException('Berkas CSV kosong.');
        }

        $headerRow = array_map(fn ($h) => trim((string) $h), $headerRow);
        $this->assertHeaders($headerRow);
        $headerMap = $this->headerIndexMap($headerRow);

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            $line = [];
            foreach ($headerMap as $name => $index) {
                $line[$name] = trim((string) ($data[$index] ?? ''));
            }
            if ($this->rowIsEmpty($line)) {
                continue;
            }
            $rows[] = $line;
        }

        fclose($handle);

        return ['headers' => $headerRow, 'rows' => $rows];
    }

    public function masaPajakFromMonthYear(?string $monthName, ?string $year): ?string
    {
        $year = trim((string) $year);
        if ($year === '' || ! ctype_digit($year)) {
            return null;
        }

        $month = $this->monthNumberFromName(trim((string) $monthName));
        if ($month === null) {
            return null;
        }

        return sprintf('%s-%02d', $year, $month);
    }

    public function monthNumberFromName(string $name): ?int
    {
        $name = strtolower(trim($name));
        if ($name === '') {
            return null;
        }

        if (ctype_digit($name) && (int) $name >= 1 && (int) $name <= 12) {
            return (int) $name;
        }

        $map = [
            'januari' => 1, 'january' => 1,
            'februari' => 2, 'february' => 2,
            'maret' => 3, 'march' => 3,
            'april' => 4,
            'mei' => 5, 'may' => 5,
            'juni' => 6, 'june' => 6,
            'juli' => 7, 'july' => 7,
            'agustus' => 8, 'august' => 8,
            'september' => 9,
            'oktober' => 10, 'october' => 10,
            'november' => 11,
            'desember' => 12, 'december' => 12,
        ];

        return $map[$name] ?? null;
    }

    /**
     * @param  array<string, string>  $line
     * @return array<string, mixed>|null
     */
    public function mapRow(array $line, string $targetMasaPajak): ?array
    {
        $fakturNo = preg_replace('/\s+/', '', $line['Nomor Faktur Pajak'] ?? '') ?? '';
        if ($fakturNo === '') {
            return null;
        }

        $rowMasa = $this->masaPajakFromMonthYear(
            $line['Masa Pajak'] ?? '',
            $line['Tahun'] ?? '',
        );

        $masaPengkreditan = $this->masaPajakFromMonthYear(
            $line['Masa Pajak Pengkreditkan'] ?? '',
            $line['Tahun Pajak Pengkreditan'] ?? '',
        );

        $bruto = $this->parseMoney($line['Harga Jual/Penggantian/DPP'] ?? '0');
        $dpp = $this->parseMoney($line['DPP Nilai Lain/DPP'] ?? '0');
        $ppn = $this->parseMoney($line['PPN'] ?? '0');

        return [
            'faktur_no' => $fakturNo,
            'npwp' => $line['NPWP Penjual'] ?? '',
            'supplier_name' => $line['Nama Penjual'] ?? '',
            'faktur_date' => $this->parseFakturDate($line['Tanggal Faktur Pajak'] ?? ''),
            'masa_pajak' => $targetMasaPajak,
            'nilai_bruto' => $bruto,
            'dpp' => $dpp,
            'ppn' => $ppn,
            'status_faktur' => strtoupper(trim($line['Status Faktur'] ?? '')),
            'masa_pengkreditan' => $masaPengkreditan,
            'perekam' => $line['Perekam'] ?? null,
            'referensi' => $line['Referensi'] ?? null,
            'valid_coretax' => $this->parseBool($line['Valid'] ?? ''),
            'dilaporkan' => $this->parseBool($line['Dilaporkan'] ?? ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function upsertRow(array $row, string $masaPajak, string $batch, User $user): CoretaxInputVat
    {
        $record = CoretaxInputVat::query()->updateOrCreate(
            [
                'masa_pajak' => $masaPajak,
                'faktur_no' => $row['faktur_no'],
            ],
            [
                'import_batch' => $batch,
                'npwp' => $row['npwp'],
                'supplier_name' => $row['supplier_name'],
                'faktur_date' => $row['faktur_date'],
                'nilai_bruto' => $row['nilai_bruto'],
                'dpp' => $row['dpp'],
                'ppn' => $row['ppn'],
                'status_faktur' => $row['status_faktur'],
                'masa_pengkreditan' => $row['masa_pengkreditan'],
                'perekam' => $row['perekam'],
                'referensi' => $row['referensi'],
                'valid_coretax' => $row['valid_coretax'],
                'dilaporkan' => $row['dilaporkan'],
                'imported_by' => $user->id,
            ],
        );

        $this->syncFakturFromCoretaxRow($record);

        return $record;
    }

    public function syncFakturFromCoretaxRow(CoretaxInputVat $record): void
    {
        $coretaxStatus = $this->mapCoretaxStatusToFaktur($record->status_faktur);

        $fakturs = Faktur::query()
            ->where('type', 'purchase')
            ->where('faktur_no', $record->faktur_no)
            ->get();

        foreach ($fakturs as $faktur) {
            $faktur->update([
                'coretax_status' => $coretaxStatus,
                'coretax_masa_pengkreditan' => $record->masa_pengkreditan,
            ]);
        }

        if ($fakturs->count() === 1) {
            $record->update([
                'match_status' => 'matched',
                'matched_faktur_id' => $fakturs->first()->id,
            ]);
        }
    }

    public function mapCoretaxStatusToFaktur(?string $status): string
    {
        $status = strtoupper(trim((string) $status));

        return match ($status) {
            'APPROVED', 'CREDITED' => 'approved',
            'REJECT', 'REJECTED', 'BATAL' => 'reject',
            'DIGANTI', 'REPLACED' => 'diganti',
            default => 'belum_diketahui',
        };
    }

    /**
     * @param  list<string>  $headerRow
     */
    public function assertHeaders(array $headerRow): void
    {
        $normalized = array_values(array_filter(
            array_map(fn ($h) => trim((string) $h), $headerRow),
            fn ($h) => $h !== '',
        ));

        $presentCanonical = [];
        foreach ($normalized as $label) {
            $canonical = $this->canonicalHeaderLabel($label);
            if ($canonical !== null) {
                $presentCanonical[$canonical] = true;
            }
        }

        $missingCore = [];
        foreach (self::REQUIRED_CANONICAL_HEADERS as $required) {
            if (! isset($presentCanonical[$required])) {
                $missingCore[] = $required;
            }
        }

        if ($missingCore !== []) {
            $read = $normalized === [] ? '(kosong)' : implode(', ', $normalized);
            throw new \InvalidArgumentException(
                'Header berkas Coretax tidak dikenali. Header terbaca: '.$read
                .'. Kolom inti belum ada: '.implode(', ', $missingCore)
                .'. Didukung ekspor Coretax berheader Indonesia maupun Inggris (SellerTIN, TaxInvoiceNumber, dll.).'
            );
        }
    }

    public function canonicalHeaderLabel(string $label): ?string
    {
        $label = trim($label);
        if ($label === '') {
            return null;
        }

        if (in_array($label, self::EXPECTED_HEADERS, true)) {
            return $label;
        }

        return self::HEADER_ALIASES_EN[$label] ?? null;
    }

    /**
     * @param  array<string, int>  $skippedByMasa
     * @return list<string>
     */
    public function formatSkippedSummary(array $skippedByMasa): array
    {
        $messages = [];
        ksort($skippedByMasa);
        foreach ($skippedByMasa as $masa => $count) {
            $label = $this->masaPajakDisplayLabel((string) $masa);
            $messages[] = sprintf(
                '%d baris dilewati karena masa pajaknya %s — unggah terpisah untuk masa itu.',
                $count,
                $label,
            );
        }

        return $messages;
    }

    public function masaPajakDisplayLabel(string $masaPajak): string
    {
        if (preg_match('/^(\d{4})-(\d{2})$/', $masaPajak, $m) !== 1) {
            return $masaPajak;
        }

        $month = (int) $m[2];
        $names = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return ($names[$month] ?? $m[2]).' '.$m[1];
    }

    /**
     * @param  list<string>  $headerRow
     * @return array<string, int>
     */
    private function headerIndexMap(array $headerRow): array
    {
        $map = [];
        foreach ($headerRow as $index => $label) {
            $label = trim((string) $label);
            if ($label === '') {
                continue;
            }
            $canonical = $this->canonicalHeaderLabel($label);
            if ($canonical !== null) {
                $map[$canonical] = $index;
            }
        }

        return $map;
    }

    /**
     * @param  array<string, string>  $line
     */
    private function rowIsEmpty(array $line): bool
    {
        foreach ($line as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function parseMoney(string $value): float
    {
        $value = str_replace([' ', '.'], '', trim($value));
        $value = str_replace(',', '.', $value);

        return round((float) $value, 2);
    }

    private function parseFakturDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $value, $m) === 1) {
            return $m[1];
        }

        return null;
    }

    private function parseBool(string $value): ?bool
    {
        $value = strtoupper(trim($value));
        if ($value === '') {
            return null;
        }

        return in_array($value, ['TRUE', '1', 'YA', 'YES'], true);
    }

    private function cacheKey(string $token): string
    {
        return 'coretax_input_vat_preview:'.$token;
    }
}
