<?php

namespace App\Imports;

use App\Models\Faktur;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class FakturImport implements ToModel, WithHeadingRow
{
    /**
     * @param Collection $collection
     */
    public function model(array $row)
    {
        return new Faktur([
            'customer_id' => $row['customer_id'],
            'invoice_no' => $row['invoice_no'],
            'invoice_date' => $row['invoice_date'],
            'faktur_no' => $row['faktur_no'],
            'faktur_date' => $row['faktur_date'],
            'kurs' => $row['kurs'],
            'dpp' => $row['dpp'],
            'ppn' => $row['ppn'],
            'remarks' => $row['remarks'],
            'attachment' => $row['attachment'],
            'created_by' => $row['created_by'],
            'submit_at' => $row['submit_at'],
            'response_by' => $row['response_by'],
            'response_at' => $row['response_at'],
            'status' => $row['status'],
        ]);
    }
}
