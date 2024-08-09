<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaksi;
use Illuminate\Http\Request;

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

        $transaksis = Transaksi::where('account_id', $account_id)->orderBy('id', 'asc')->get();
        // add index column
        $transaksis->map(function ($transaksi, $key) {
            $transaksi->index = $key + 1;
        });

        return datatables()->of($transaksis)
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
            ->addColumn('row_balance', function ($transaksi) use ($transaksis) {
                $first_row = $transaksis->where('index', 1)->first();

                if ($transaksi->index == 1) {
                    return number_format($first_row->balance, 2);
                } else {
                    $previous_sum_of_debit = $transaksis->where('index', '<=', $transaksi->index)
                        ->sum('debit') + $first_row->debit;
                    $previous_sum_of_credit = $transaksis->where('index', '<=', $transaksi->index)
                        ->sum('credit') - $first_row->credit;

                    return number_format($first_row->balance + $previous_sum_of_debit - $previous_sum_of_credit, 2);
                }
            })
            ->addIndexColumn()
            // ->addColumn('action', 'transaksi.action')
            ->rawColumns(['created_at'])
            ->toJson();
    }
}
