<?php

namespace App\Imports;

use App\Models\InvoiceCreation;
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
            'will_delete' => $this->calculateDuration($row['create_date'], $row['posting_date']) < 0 ? true : false,
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

    private function convert_date($date)
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

    private function calculateDuration($create_date, $posting_date)
    {
        $create_date = new \DateTime($this->convert_date($create_date));
        $posting_date = new \DateTime($this->convert_date($posting_date));

        // Ensure create_date is after or the same as posting_date
        if ($create_date <= $posting_date) {
            return 0;
        }

        $workdays = 0;

        // Iterate through each day between the two dates
        while ($create_date > $posting_date) {
            // Check if the day is a weekday (Monday to Friday)
            if ($create_date->format('N') < 6) {
                $workdays++;
            }
            // Move to the next day
            $create_date->modify('-1 day');
        }

        return $workdays;
    }
}
