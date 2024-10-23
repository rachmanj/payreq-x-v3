<?php

namespace App\Imports;

use App\Models\InvoiceCreation;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class InvoiceCreationImport implements ToModel, WithHeadingRow
{
    private $batchNumber;

    public function __construct()
    {
        $this->batchNumber = $this->generateBatchNumber();
    }

    public function model(array $row)
    {
        return new InvoiceCreation([
            'create_date' => $this->convert_date($row['create_date']),
            'posting_date' => $this->convert_date($row['posting_date']),
            'duration' => $this->calculateDuration($row['create_date'], $row['posting_date']),
            'document_number' => $row['doc_num'],
            'user_code' => $row['user_code'],
            'batch_number' => $this->batchNumber(),
            'uploaded_by' => auth()->id(),
        ]);
    }

    private function generateBatchNumber()
    {
        $lastBatchNumber = InvoiceCreation::max('batch_number');
        return $lastBatchNumber + 1;
    }

    private function batchNumber()
    {
        return $this->batchNumber;
    }

    public function convert_date($date)
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

    public function calculateDuration($create_date, $posting_date)
    {
        $create_date = strtotime($this->convert_date($create_date));
        $posting_date = strtotime($this->convert_date($posting_date));
        $duration = ($create_date - $posting_date) / (60 * 60 * 24); // Convert seconds to days
        return $duration;
    }
}
