<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\GeneralLedger;
use Illuminate\Http\Request;

class GeneralLedgerController extends Controller
{
    public function store($account, $data)
    {
        $general_ledger = new GeneralLedger();

        if ($account->account_type->type_name == 'advance') {
            $debit_amount = $data->amount;
            $credit_amount = 0;
            $account->balance = $account->balance + $data->amount;
        } elseif ($account->account_type->type_name == 'cash') {
            $debit_amount = 0;
            $credit_amount = $data->amount;
            $account->balance = $account->balance - $data->amount;
        }

        $account->save();

        $general_ledger->account_id = $account->id;
        $general_ledger->posting_date = $data->sap_posting_date;
        $general_ledger->document_type = $data->type;
        $general_ledger->journal_no = $data->journal_no;
        $general_ledger->remarks = $data->description;
        $general_ledger->debit = $debit_amount; // $account->account_type->type_name == 'advance' ? $data->amount : '0';
        $general_ledger->credit = $credit_amount; // $account->account_type->type_name == 'cash' ? $data->amount : '0';
        $general_ledger->save();
    }

    public function delete($gl)
    {
        $account = Account::find($gl->account_id);

        if ($account->account_type->type_name == 'advance') {
            $account->balance = $account->balance - $gl->debit;
        } elseif ($account->account_type->type_name == 'cash') {
            $account->balance = $account->balance + $gl->credit;
        }
        $account->save();
        $gl->delete();
    }
}
