<?php

namespace App\Imports;

use App\Models\Account;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AccountImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Account([
            'account_number' => $row['account_number'],
            'account_name' => $row['account_name'],
            'description' => $row['description'],
        ]);
    }
}
