<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SummaryUnitExpenseMonthlyExport implements FromView, WithTitle, WithStyles, ShouldAutoSize
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
                DB::raw("SUM(CASE WHEN MONTH(verification_journals.sap_posting_date) = 1 THEN realization_details.amount ELSE 0 END) as jan"),
                DB::raw("SUM(CASE WHEN MONTH(verification_journals.sap_posting_date) = 2 THEN realization_details.amount ELSE 0 END) as feb"),
                DB::raw("SUM(CASE WHEN MONTH(verification_journals.sap_posting_date) = 3 THEN realization_details.amount ELSE 0 END) as mar"),
                DB::raw("SUM(CASE WHEN MONTH(verification_journals.sap_posting_date) = 4 THEN realization_details.amount ELSE 0 END) as apr"),
                DB::raw("SUM(CASE WHEN MONTH(verification_journals.sap_posting_date) = 5 THEN realization_details.amount ELSE 0 END) as may"),
                DB::raw("SUM(CASE WHEN MONTH(verification_journals.sap_posting_date) = 6 THEN realization_details.amount ELSE 0 END) as jun"),
                DB::raw("SUM(CASE WHEN MONTH(verification_journals.sap_posting_date) = 7 THEN realization_details.amount ELSE 0 END) as jul"),
                DB::raw("SUM(CASE WHEN MONTH(verification_journals.sap_posting_date) = 8 THEN realization_details.amount ELSE 0 END) as aug"),
                DB::raw("SUM(CASE WHEN MONTH(verification_journals.sap_posting_date) = 9 THEN realization_details.amount ELSE 0 END) as sep"),
                DB::raw("SUM(CASE WHEN MONTH(verification_journals.sap_posting_date) = 10 THEN realization_details.amount ELSE 0 END) as oct"),
                DB::raw("SUM(CASE WHEN MONTH(verification_journals.sap_posting_date) = 11 THEN realization_details.amount ELSE 0 END) as nov"),
                DB::raw("SUM(CASE WHEN MONTH(verification_journals.sap_posting_date) = 12 THEN realization_details.amount ELSE 0 END) as `dec`"),
                DB::raw('SUM(realization_details.amount) as total_amount')
            )
            ->groupBy('realization_details.unit_no')
            ->orderBy('realization_details.unit_no')
            ->get();

        $year = $this->year;
        return view('exports.summary_unit_expense_monthly', compact('data', 'year'));
    }

    public function title(): string
    {
        return 'Monthly Breakdown ' . $this->year;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $lastCol = 'O';

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
