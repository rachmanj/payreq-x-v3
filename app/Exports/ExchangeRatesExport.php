<?php

namespace App\Exports;

use App\Models\ExchangeRate;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ExchangeRatesExport implements FromView, WithTitle, WithStyles, ShouldAutoSize
{
    protected $query;
    protected $exchangeRates;

    public function __construct($query = null)
    {
        if ($query) {
            $this->exchangeRates = $query->with(['currencyFromRelation', 'currencyToRelation', 'creator'])
                ->orderBy('effective_date', 'desc')
                ->get();
        } else {
            $this->exchangeRates = ExchangeRate::with(['currencyFromRelation', 'currencyToRelation', 'creator'])
                ->orderBy('effective_date', 'desc')
                ->get();
        }
    }

    public function view(): View
    {
        return view('exports.exchange-rates', [
            'exchangeRates' => $this->exchangeRates
        ]);
    }

    public function title(): string
    {
        return 'Exchange Rates';
    }

    public function styles(Worksheet $sheet)
    {
        // Apply styling to the header row
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => '4CAF50',
                ],
            ],
            'font' => [
                'color' => [
                    'rgb' => 'FFFFFF',
                ],
                'bold' => true,
            ],
        ]);

        // Apply number formatting to exchange rate column (column D)
        $lastRow = $this->exchangeRates->count() + 1;
        if ($lastRow > 1) {
            $sheet->getStyle('D2:D' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.000000_-');
        }

        // Add borders to the entire table
        $sheet->getStyle('A1:H' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);

        // Center align headers
        $sheet->getStyle('A1:H1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(15);  // Currency From
        $sheet->getColumnDimension('B')->setWidth(15);  // Currency To
        $sheet->getColumnDimension('C')->setWidth(25);  // Currency Pair
        $sheet->getColumnDimension('D')->setWidth(18);  // Exchange Rate
        $sheet->getColumnDimension('E')->setWidth(15);  // Effective Date
        $sheet->getColumnDimension('F')->setWidth(20);  // Created By
        $sheet->getColumnDimension('G')->setWidth(20);  // Created At
        $sheet->getColumnDimension('H')->setWidth(20);  // Updated At

        return $sheet;
    }
}
