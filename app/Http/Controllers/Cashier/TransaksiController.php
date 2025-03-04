<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransaksiController extends Controller
{
    public function index()
    {
        $account = Account::where('type', 'cash')->where('project', auth()->user()->project)->first();

        return view('cashier.transaksis.index', compact('account'));
    }

    public function store($type, $data)
    {
        $transaksi = new Transaksi();

        $latest_transaksi_of_account = Transaksi::where('account_id', $data->account_id)->latest()->first();
        $last_balance = $latest_transaksi_of_account ? $latest_transaksi_of_account->balance : 0;

        if ($type == 'outgoing') {
            $transaksi->account_id = $data->account_id;
            $transaksi->document_id = $data->id;
            $transaksi->document_type = 'outgoing';
            $transaksi->posting_date = $data->outgoing_date;
            $transaksi->description = 'Payreq no.' . $data->payreq->nomor . ', ' . $data->payreq->requestor->name;
            $transaksi->credit = $data->amount;
            $transaksi->balance = $last_balance - $data->amount;
            $transaksi->save();

            return true;
        } else {
            $transaksi->account_id = Account::where('type', 'cash')->where('project', auth()->user()->project)->first()->id;
            $transaksi->document_id = $data->id;
            $transaksi->document_type = 'incoming';
            $transaksi->posting_date = $data->receive_date;
            $transaksi->description = $data->description;
            $transaksi->debit = $data->amount;
            $transaksi->balance = $last_balance + $data->amount;
            $transaksi->save();

            return true;
        }
    }

    public function data()
    {
        $account_id = request()->query('account_id');
        
        // Get the first transaction to use as a starting point for balance calculation
        $first_transaction = Transaksi::where('account_id', $account_id)
            ->orderBy('id', 'asc')
            ->first();
            
        $initial_balance = $first_transaction ? $first_transaction->balance : 0;
        
        // Use query builder for better performance with DataTables
        $query = Transaksi::query()
            ->where('account_id', $account_id)
            ->select([
                'id',
                'created_at',
                'posting_date',
                'document_type',
                'description',
                'debit',
                'credit',
                'balance',
                DB::raw('(SELECT SUM(t2.debit) FROM transaksis as t2 WHERE t2.account_id = transaksis.account_id AND t2.id <= transaksis.id) as cumulative_debit'),
                DB::raw('(SELECT SUM(t2.credit) FROM transaksis as t2 WHERE t2.account_id = transaksis.account_id AND t2.id <= transaksis.id) as cumulative_credit')
            ]);

        return datatables()->of($query)
            ->editColumn('created_at', function ($transaksi) {
                //add 8 hours to match with Jakarta timezone
                return '<small>' . date('d-M-Y H:i:s', strtotime($transaksi->created_at . '+8 hours')) . '</small>';
            })
            ->editColumn('posting_date', function ($transaksi) {
                return $transaksi->posting_date ? date('d-M-Y', strtotime($transaksi->posting_date)) : '-';
            })
            ->editColumn('debit', function ($transaksi) {
                return $transaksi->debit ? number_format($transaksi->debit, 2) : '-';
            })
            ->editColumn('credit', function ($transaksi) {
                return $transaksi->credit ? number_format($transaksi->credit, 2) : '-';
            })
            ->addColumn('row_balance', function ($transaksi) {
                // Use the pre-calculated balance from the database
                return number_format($transaksi->balance, 2);
            })
            ->rawColumns(['created_at'])
            ->toJson();
    }
}
