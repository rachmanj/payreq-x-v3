<?php

namespace App\Imports;

use App\Models\DailyTx;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DailyTxImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new DailyTx([
            'create_date' => $this->convertDate($row['create_date']),
            'posting_date' => $this->convertDate($row['posting_date']),
            'duration' => $this->calculateDuration($row['create_date'], $row['posting_date']),
            'doc_num' => $row['doc_num'],
            'doc_type' => $row['doc_type'],
            'project' => $row['project_code'],
            'account' => $row['account'],
            'debit' => $row['debit'],
            'credit' => $row['credit'],
            'remarks' => $row['remarks'],
            'user_code' => $row['user_code'],
            'will_delete' => $this->calculateDuration($row['create_date'], $row['posting_date']) < 0 ? true : false,
            'uploaded_by' => auth()->user()->id,
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

    private function calculateDuration($create_date, $posting_date)
    {
        $create_date = new \DateTime($this->convertDate($create_date));
        $posting_date = new \DateTime($this->convertDate($posting_date));

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
