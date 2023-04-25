<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaksi;
use Illuminate\Http\Request;

class TransaksiController extends Controller
{
    public function index()
    {
        $accounts = Account::orderBy('account_no', 'asc')->get();
        return view('transaksi.index', compact('accounts'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'account_id' => 'required',
            'amount' => 'required|numeric',
            'type' => 'required',
        ]);

        //Create new transaksi record
        $transaksi = new Transaksi();

        if ($request->posting_date) {
            $transaksi->posting_date = $request->posting_date;
        } else {
            $transaksi->posting_date = date('Y-m-d');
        }
        $transaksi->account_id = $request->account_id;
        $transaksi->amount = $request->amount;
        $transaksi->type = $request->type;
        $transaksi->description = $request->description;
        $transaksi->save();

        // Update account balance on accounts table
        $account = Account::find($request->account_id);
        if ($request->type == 'plus') {
            $account->balance += $request->amount;
        } else {
            $account->balance -= $request->amount;
        }
        $account->save();

        return redirect()->route('transaksi.index')->with('success', 'Transaksi added successfully');
    }

    public function destroy($id)
    {
        $transaksi = Transaksi::find($id);
        $account = Account::find($transaksi->account_id);
        if ($transaksi->type == 'plus') {
            $account->balance -= $transaksi->amount;
        } else {
            $account->balance += $transaksi->amount;
        }
        $account->save();

        $transaksi->deleted_by = auth()->user()->username;
        $transaksi->save();
        $transaksi->delete();

        return redirect()->route('transaksi.index')->with('success', 'Transaksi deleted successfully');
    }

    public function data()
    {
        $transaksis = Transaksi::with('account')->orderBy('posting_date', 'desc')->latest()->get();

        return datatables()->of($transaksis)
            ->editColumn('created_at', function ($transaksi) {
                return ($transaksi->created_at)->diffForHumans();
                // return date('d-M-Y H:i:s', strtotime('+8 hours', strtotime($transaksi->created_at)));
            })
            ->editColumn('posting_date', function ($transaksi) {
                return $transaksi->posting_date ? date('d-M-Y', strtotime($transaksi->posting_date)) : '-';
            })
            ->editColumn('type', function ($transaksi) {
                return $transaksi->type == 'plus' ? '<i class="fas fa-plus"></i>' : '<i class="fas fa-minus"></i>';
            })
            ->addColumn('account', function ($transaksi) {
                return $transaksi->account->account_no . ' - ' . $transaksi->account->name;
            })
            ->editColumn('amount', function ($transaksi) {
                return number_format($transaksi->amount, 2);
            })
            ->addIndexColumn()
            ->addColumn('action', 'transaksi.action')
            ->rawColumns(['action', 'type'])
            ->toJson();
    }
}
