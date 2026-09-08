<?php

namespace App\Services\InstallmentImport;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BcaStyleExcelParser
{
    private const BLOCK_WIDTH = 7;

    private const HEADER_ROW = 8;

    private const DATA_START_ROW = 9;

    private const MAX_INSTALLMENT_ROWS = 36;

    /**
     * @return array{units: list<array{kontrak: string, unit: string|null, hutang_pokok: float, hutang_bunga: float, rows: list<array{no: int, due_date: string, principal: float, interest: float, total: float}>}>}
     */
    public function parse(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $blockStarts = $this->detectBlockStarts($sheet);
        $units = [];

        foreach ($blockStarts as $startCol) {
            $unit = $this->parseBlock($sheet, $startCol);
            if ($unit !== null) {
                $units[] = $unit;
            }
        }

        return ['units' => $units];
    }

    /**
     * @return list<int>
     */
    protected function detectBlockStarts(Worksheet $sheet): array
    {
        $starts = [];
        $highestColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());

        for ($col = 1; $col <= $highestColumn; $col++) {
            $value = trim((string) $sheet->getCellByColumnAndRow($col, self::HEADER_ROW)->getValue());
            if (strcasecmp($value, 'Ke') === 0) {
                $starts[] = $col;
            }
        }

        return $starts;
    }

    /**
     * @return array{kontrak: string, unit: string|null, hutang_pokok: float, hutang_bunga: float, rows: list<array{no: int, due_date: string, principal: float, interest: float, total: float}>}|null
     */
    protected function parseBlock(Worksheet $sheet, int $startCol): ?array
    {
        $headerText = (string) $sheet->getCellByColumnAndRow($startCol, 1)->getValue();
        if (trim($headerText) === '') {
            return null;
        }

        $kontrak = $this->extractKontrak($headerText);
        $unit = $this->extractUnit($headerText);
        $hutangPokok = $this->extractAmount($headerText, 'Hutang Pokok');
        $hutangBunga = $this->extractAmount($headerText, 'Hutang Bunga');

        $rawRows = [];
        for ($row = self::DATA_START_ROW; $row < self::DATA_START_ROW + self::MAX_INSTALLMENT_ROWS; $row++) {
            $no = $sheet->getCellByColumnAndRow($startCol, $row)->getCalculatedValue();
            if ($no === null || $no === '' || ! is_numeric($no)) {
                break;
            }

            $tanggal = $sheet->getCellByColumnAndRow($startCol + 1, $row)->getCalculatedValue();
            $rental = (float) $sheet->getCellByColumnAndRow($startCol + 2, $row)->getCalculatedValue();
            $outPokok = (float) $sheet->getCellByColumnAndRow($startCol + 3, $row)->getCalculatedValue();
            $outsBunga = (float) $sheet->getCellByColumnAndRow($startCol + 4, $row)->getCalculatedValue();

            $rawRows[] = [
                'no' => (int) $no,
                'tanggal' => $tanggal,
                'rental' => $rental,
                'out_pokok' => $outPokok,
                'outs_bunga' => $outsBunga,
            ];
        }

        if ($rawRows === []) {
            return null;
        }

        $rows = [];
        foreach ($rawRows as $index => $raw) {
            $prevOutPokok = $index === 0
                ? $hutangPokok
                : $rawRows[$index - 1]['out_pokok'];

            $principal = round($prevOutPokok - $raw['out_pokok'], 2);
            $interest = round($raw['rental'] - $principal, 2);

            $rows[] = [
                'no' => $raw['no'],
                'due_date' => $this->parseExcelDate($raw['tanggal']),
                'principal' => $principal,
                'interest' => $interest,
                'total' => round($raw['rental'], 2),
            ];
        }

        return [
            'kontrak' => $kontrak,
            'unit' => $unit,
            'hutang_pokok' => $hutangPokok,
            'hutang_bunga' => $hutangBunga,
            'rows' => $rows,
        ];
    }

    protected function extractKontrak(string $text): string
    {
        if (preg_match('/No\.\s*Kontrak\s*:\s*([^\s]+)/i', $text, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    protected function extractUnit(string $text): ?string
    {
        if (preg_match('/\b(T\s*\d+)\s*$/i', $text, $matches)) {
            return preg_replace('/\s+/', ' ', trim($matches[1]));
        }

        return null;
    }

    protected function extractAmount(string $text, string $label): float
    {
        $pattern = '/'.preg_quote($label, '/').'\s*:\s*([\d,\.]+)/i';
        if (preg_match($pattern, $text, $matches)) {
            return (float) str_replace(',', '', $matches[1]);
        }

        return 0.0;
    }

    protected function parseExcelDate(mixed $value): string
    {
        if (is_numeric($value)) {
            return Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        if (is_string($value) && $value !== '') {
            return date('Y-m-d', strtotime($value));
        }

        return '';
    }
}
