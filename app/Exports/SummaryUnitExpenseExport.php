<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SummaryUnitExpenseExport implements FromView, WithTitle, WithStyles, ShouldAutoSize
{
    protected int $year;

    public function __construct(int $year)
    {
        $this->year = $year;
    }

    public function view(): View
    {
        $data = DB::table('realization_details')
            ->join('verification_journals', 'realization_details.verification_journal_id', '=', 'verification_journals.id')
            ->whereNotNull('realization_details.verification_journal_id')
            ->whereNotNull('realization_details.unit_no')
            ->whereRaw('YEAR(verification_journals.sap_posting_date) = ?', [$this->year])
            ->select(
                'realization_details.unit_no',
                DB::raw("SUM(CASE WHEN realization_details.type = 'fuel' THEN realization_details.amount ELSE 0 END) as fuel_amount"),
                DB::raw("SUM(CASE WHEN realization_details.type = 'fuel' AND realization_details.qty > 0 THEN realization_details.qty ELSE 0 END) as fuel_qty"),
                DB::raw("SUM(CASE WHEN realization_details.type = 'service' THEN realization_details.amount ELSE 0 END) as service_amount"),
                DB::raw("SUM(CASE WHEN realization_details.type = 'other' OR realization_details.type IS NULL THEN realization_details.amount ELSE 0 END) as other_amount"),
                DB::raw("SUM(CASE WHEN realization_details.type = 'tax' THEN realization_details.amount ELSE 0 END) as tax_amount"),
                DB::raw('SUM(realization_details.amount) as total_amount')
            )
            ->groupBy('realization_details.unit_no')
            ->orderBy('realization_details.unit_no')
            ->get();

        $year = $this->year;
        return view('exports.summary_unit_expense', compact('data', 'year'));
    }

    public function title(): string
    {
        return 'Summary Unit Expense ' . $this->year;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $lastCol = 'G';

        if ($lastRow < 1) {
            return $sheet;
        }

        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2196F3'],
            ],
        ]);

        $sheet->getStyle('C2:' . $lastCol . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
        // Est. FCPL column H - commented for later use
        // $sheet->getStyle('H2:H' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');

        $sheet->getStyle('A1:' . $lastCol . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);

        return $sheet;
    }
}
