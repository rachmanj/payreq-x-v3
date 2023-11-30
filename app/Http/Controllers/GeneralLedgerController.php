<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\GeneralLedger;
use Illuminate\Http\Request;

class GeneralLedgerController extends Controller
{
    public function index()
    {
        $accounts = Account::where('project', auth()->user()->project)
            // ->where('account_type_id', '!=', 1)
            ->orderBy('account_number')
            ->get();

        return view('general-ledgers.index', compact('accounts'));
    }

    public function search(Request $request)
    {
        $account_id = $request->account_id;

        return redirect()->route('general-ledgers.show', $account_id);
    }

    public function show($id)
    {
        $accounts = Account::where('project', auth()->user()->project)
            // ->where('account_type_id', '!=', 1)
            ->orderBy('account_number')
            ->get();

        $selected_account = Account::find($id);

        return view('general-ledgers.show', compact('accounts', 'selected_account'));
    }

    public function store($account, $data)
    {
        $general_ledger = new GeneralLedger();

        if ($data->type == 'cash-in') {
            // update account balance and set debit/credit amount
            if ($account->type == 'cash') {
                $debit_amount = $data->amount;
                $credit_amount = 0;
                $account->sap_balance = $account->sap_balance + $data->amount;
            } elseif ($account->type == 'advance') {
                $debit_amount = 0;
                $credit_amount = $data->amount;
                $account->sap_balance = $account->sap_balance - $data->amount;
            }
            $account->save();
        } elseif ($data->type == 'cash-out') {
            // update account balance and set debit/credit amount
            if ($account->type == 'advance') {
                $debit_amount = $data->amount;
                $credit_amount = 0;
                $account->sap_balance = $account->sap_balance + $data->amount;
            } elseif ($account->type == 'cash') {
                $debit_amount = 0;
                $credit_amount = $data->amount;
                $account->sap_balance = $account->sap_balance - $data->amount;
            }
            $account->save();
        }

        $general_ledger->account_id = $account->id;
        $general_ledger->posting_date = $data->sap_posting_date;
        $general_ledger->document_type = $data->type;
        $general_ledger->journal_no = $data->journal_no;
        $general_ledger->remarks = $data->description;
        $general_ledger->project = auth()->user()->project;
        $general_ledger->cost_center_id = auth()->user()->department_id;
        $general_ledger->debit = $debit_amount; // $account->account_type->type_name == 'advance' ? $data->amount : '0';
        $general_ledger->credit = $credit_amount; // $account->account_type->type_name == 'cash' ? $data->amount : '0';
        $general_ledger->created_by = auth()->user()->id;
        $general_ledger->save();

        return true;
    }

    public function delete($gl)
    {
        $account = Account::find($gl->account_id);

        if ($account->type == 'advance') {
            $account->balance = $account->balance - $gl->debit;
        } elseif ($account->type == 'cash') {
            $account->balance = $account->balance + $gl->credit;
        }
        $account->save();
        $gl->delete();
    }

    public function data($id)
    {
        // get user's roles
        $userRoles = app(UserController::class)->getUserRoles();

        if (in_array('superadmin', $userRoles) || in_array('admin', $userRoles)) {
            $gls = GeneralLedger::orderBy('posting_date', 'desc')
                ->get();
        } else {
            $gls = GeneralLedger::where('project', auth()->user()->project)
                ->orderBy('posting_date', 'desc')
                ->get();
        }

        $journals = GeneralLedger::where('account_id', $id)
            ->orderBy('posting_date', 'asc')
            ->get();

        return datatables()->of($journals)
            ->editColumn('posting_date', function ($journal) {
                return date('d-M-Y', strtotime($journal->posting_date));
            })
            ->editColumn('debit', function ($gl) {
                return number_format($gl->debit, 2, ',', '.');
            })
            ->editColumn('credit', function ($gl) {
                return number_format($gl->credit, 2, ',', '.');
            })
            ->editColumn('balance', function ($gl) {
                $row_before_balance = GeneralLedger::where('account_id', $gl->account_id)
                    ->where('posting_date', '<', $gl->posting_date)
                    ->sum('debit') - GeneralLedger::where('account_id', $gl->account_id)
                    ->where('posting_date', '<', $gl->posting_date)
                    ->sum('credit');
                $balance = $row_before_balance + $gl->debit - $gl->credit;
                return number_format($balance, 2, ',', '.');
            })
            // ->addColumn('action', 'user-payreqs.action')
            // ->rawColumns(['action', 'nomor', 'status'])
            ->addIndexColumn()
            ->toJson();
    }
}
