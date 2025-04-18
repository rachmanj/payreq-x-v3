<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CashStatementExport implements FromView, WithTitle, WithStyles, ShouldAutoSize
{
    protected $statementData;

    public function __construct(array $statementData)
    {
        $this->statementData = $statementData;
    }

    public function view(): View
    {
        return view('exports.cash-statement', $this->statementData);
    }

    public function title(): string
    {
        return 'Cash Statement';
    }

    public function styles(Worksheet $sheet)
    {
        // Apply styling to the header row
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'E0E0E0',
                ],
            ],
        ]);

        // Apply currency formatting to amount columns
        $lastRow = count($this->statementData['statementLines']) + 1;
        $sheet->getStyle('G2:I' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00_-');
        
        // Add borders to the entire table
        $sheet->getStyle('A1:I' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);
        
        return $sheet;
    }
} 