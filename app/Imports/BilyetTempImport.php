<?php

namespace App\Imports;

use App\Models\BilyetTemp;
use App\Models\Giro;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BilyetTempImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new BilyetTemp([
            'giro_id' => $this->cek_account($row['acc_no']),
            'acc_no' => $row['acc_no'],
            'prefix' => $row['prefix'],
            'nomor' => $row['nomor'],
            'type' => $row['type'],
            'bilyet_date' => $this->convert_date($row['bilyet_date']),
            'cair_date' => $this->convert_date($row['cair_date']),
            'amount' => $row['amount'],
            'remarks' => $row['remarks'],
            'loan_id' => $row['loan_id'],
            'created_by' => auth()->id(),
            'project' => auth()->user()->project,
        ]);
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

    public function cek_account($acc_no)
    {
        $cek = Giro::where('acc_no', $acc_no)->first();
        if ($cek) {
            return $cek->id;
        } else {
            return null;
        }
    }
}
