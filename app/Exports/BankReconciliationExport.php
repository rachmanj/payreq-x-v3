<?php

namespace App\Exports;

use App\Models\BankReconciliation;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BankReconciliationExport implements FromView, ShouldAutoSize, WithStyles, WithTitle
{
    /**
     * @param  array<string, mixed>  $statement
     */
    public function __construct(
        protected BankReconciliation $reconciliation,
        protected array $statement,
    ) {}

    public function view(): View
    {
        return view('exports.bank-reconciliation', [
            'bankReconciliation' => $this->reconciliation,
            'statement' => $this->statement,
        ]);
    }

    public function title(): string
    {
        $period = $this->reconciliation->periode?->format('Y-m') ?? 'period';

        return 'BR '.$period;
    }

    public function styles(Worksheet $sheet): Worksheet
    {
        $lastRow = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();

        $sheet->getStyle('A1:'.$lastColumn.'1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '11998E'],
            ],
        ]);

        $sheet->getStyle('A1:'.$lastColumn.$lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);

        return $sheet;
    }
}
