<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ExchangeRateTemplateExport implements FromView, WithTitle, WithStyles, ShouldAutoSize
{
    public function view(): View
    {
        return view('exports.exchange-rate-template');
    }

    public function title(): string
    {
        return 'Exchange Rate Template';
    }

    public function styles(Worksheet $sheet)
    {
        // Apply styling to the header row
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => '2196F3',
                ],
            ],
            'font' => [
                'color' => [
                    'rgb' => 'FFFFFF',
                ],
                'bold' => true,
            ],
        ]);

        // Add example data styling
        $sheet->getStyle('A2:D3')->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'E3F2FD',
                ],
            ],
            'font' => [
                'italic' => true,
                'color' => [
                    'rgb' => '666666',
                ],
            ],
        ]);

        // Add borders to the header and example rows
        $sheet->getStyle('A1:D3')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);

        // Center align headers
        $sheet->getStyle('A1:D1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(18);  // Currency From
        $sheet->getColumnDimension('B')->setWidth(18);  // Currency To
        $sheet->getColumnDimension('C')->setWidth(20);  // Exchange Rate
        $sheet->getColumnDimension('D')->setWidth(18);  // Effective Date

        // Add instruction comment to exchange rate column
        $sheet->getComment('C1')->getText()->createTextRun('Enter the exchange rate with up to 6 decimal places. Example: 15750.123456');

        // Add instruction comment to date column
        $sheet->getComment('D1')->getText()->createTextRun('Use format: YYYY-MM-DD. Example: 2024-01-15');

        return $sheet;
    }
}
