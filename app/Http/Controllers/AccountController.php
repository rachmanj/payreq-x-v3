<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaksi;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index()
    {
        $accounts = Account::orderBy('account_no', 'asc')->get();
        return view('account.index', compact('accounts'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:50|unique:accounts',
            'account_no' => 'required|max:50|unique:accounts',
        ]);

        $account = new Account();
        $account->name = $request->name;
        $account->account_no = $request->account_no;
        $account->save();

        return redirect()->route('account.index')->with('success', 'Account created successfully');
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required|max:50|unique:accounts,name,' . $id,
            'account_no' => 'required|max:50|unique:accounts,account_no,' . $id,
        ]);

        $account = Account::find($id);
        $account->name = $request->name;
        $account->account_no = $request->account_no;
        $account->save();

        return redirect()->route('account.index')->with('success', 'Account updated successfully');
    }

    public function transaksi_store(Request $request)
    {
        $this->validate($request, [
            'account_id' => 'required',
            'amount' => 'required|numeric',
            'type' => 'required',
        ]);

        Transaksi::create($request->all());

        $account = Account::find($request->account_id);
        if ($request->type == 'plus') {
            $account->balance += $request->amount;
        } else {
            $account->balance -= $request->amount;
        }
        $account->save();

        return redirect()->route('account.index')->with('success', 'Account updated successfully');
    }

    public function data()
    {
        $accounts = Account::orderBy('name', 'asc')->get();

        return datatables()->of($accounts)
            ->editColumn('balance', function ($account) {
                return number_format($account->balance, 2);
            })
            ->addIndexColumn()
            ->addColumn('action', 'account.action')
            ->rawColumns(['action'])
            ->toJson();
    }
}
