<?php

namespace App\Exports;

use App\Models\Pcbc;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PcbcExport implements FromView, WithTitle, WithStyles, ShouldAutoSize
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query;
    }

    public function view(): View
    {
        if ($this->query) {
            $pcbcs = $this->query->with(['createdBy', 'project'])->get();
        } else {
            $pcbcs = Pcbc::with(['createdBy', 'project'])->orderBy('pcbc_date', 'desc')->get();
        }

        return view('exports.pcbc', compact('pcbcs'));
    }

    public function title(): string
    {
        return 'PCBC Records';
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        
        // Header styling
        $sheet->getStyle('A1:Q1')->applyFromArray([
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
            ],
        ]);

        // Amount columns formatting
        $sheet->getStyle('M2:O' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
        
        // Borders
        $sheet->getStyle('A1:Q' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);

        return $sheet;
    }
}
