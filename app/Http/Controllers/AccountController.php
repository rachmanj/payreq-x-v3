<?php

namespace App\Http\Controllers;

use App\Imports\AccountImport;
use App\Models\Account;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AccountController extends Controller
{
    public function index()
    {
        return view('accounts.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_number' => 'required|unique:accounts',
            'account_name' => 'required',
            'type_id' => 'required',
            'project' => 'required',
            'description' => 'required',
        ]);

        Account::create($validated);

        return redirect()->route('accounts.index')->with('success', 'Account created successfully!');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'account_number' => 'required|unique:accounts,account_number,' . $id,
            'account_name' => 'required',
            'type_id' => 'required',
            'project' => 'required',
            'description' => 'required',
        ]);

        Account::where('id', $id)->update($validated);

        return redirect()->route('accounts.index')->with('success', 'Account updated successfully!');
    }

    public function destroy($id)
    {
        $account = Account::findOrFail($id);
        $account->delete();

        return redirect()->route('accounts.index')->with('success', 'Account deleted successfully!');
    }

    public function upload(Request $request)
    {
        // VALIDATE
        $this->validate($request, [
            'file_upload' => 'required|mimes:xls,xlsx'
        ]);

        // GET FILE
        $file = $request->file('file_upload');

        // GET a UNIQUE FILE NAME
        $nama_file = rand() . $file->getClientOriginalName();

        // UPLOAD FILE TO FOLDER FILE_IMPORT
        $file->move('file_upload', $nama_file);

        // IMPORT DATA
        Excel::import(new AccountImport, public_path('/file_upload/' . $nama_file));

        // REDIRECT
        return redirect()->route('accounts.index')->with('success', 'Account imported successfully!');
    }

    public function data()
    {
        $accounts = Account::orderBy('account_number', 'asc')
            ->get();

        return datatables()->of($accounts)
            ->addColumn('type', function ($account) {
                return $account->account_type->type_name;
            })
            ->addIndexColumn()
            ->addColumn('action', 'accounts.action')
            ->rawColumns(['action'])
            ->toJson();
    }

    // ACCOUNT TYPE
    public function accountTypes()
    {
        $account_types = [
            ['id' => 1, 'name' => 'Bank'],
            ['id' => 2, 'name' => 'Cash'],
            ['id' => 3, 'name' => 'Revenue'],
            ['id' => 4, 'name' => 'Expense'],
        ];

        return $account_types;
    }
}
