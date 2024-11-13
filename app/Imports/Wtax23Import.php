<?php

namespace App\Imports;

use App\Models\Wtax23;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class Wtax23Import implements ToModel, WithHeadingRow
{
    private $batchNumber;

    public function __construct()
    {
        $this->batchNumber = $this->generateBatchNumber();
    }

    public function model(array $row)
    {
        return new Wtax23([
            'create_date' => $this->convertDate($row['create_date']),
            'posting_date' => $this->convertDate($row['posting_date']),
            'doc_num' => $row['invoice_no'],
            'doc_type' => 'invoice',
            'account' => '21701005',
            'amount' => $row['wtax_amount'],
            'remarks' => $row['vendor_name'],
            'user_code' => $row['user_code'],
            'uploaded_by' => Auth::id(),
            'batch_no' => $this->batchNumber,
        ]);
    }

    private function convertDate($date)
    {
        if ($date) {
            $year = substr($date, 6, 4);
            $month = substr($date, 3, 2);
            $day = substr($date, 0, 2);
            $new_date = $year . '-' . $month . '-' . $day;
            return $new_date;
        } else {
            return null;
        }
    }

    private function generateBatchNumber()
    {
        $lastBatchNumber = Wtax23::max('batch_no');
        return $lastBatchNumber ? $lastBatchNumber + 1 : 1;
    }
}
