<?php

namespace App\Http\Controllers;

use App\Imports\AccountImport;
use App\Models\Account;
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
            'type' => 'required',
            'project' => 'required',
        ]);

        // check if account number is exsist
        $account = Account::where('account_number', $validated['account_number'])
            ->first();

        if ($account) {
            return redirect()->route('accounts.index')->with('error', 'Account number already exists!');
        }

        Account::create(array_merge($validated, $request->description ? ['description' => $request->description] : []));

        return redirect()->route('accounts.index')->with('success', 'Account created successfully!');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'account_number' => 'required|unique:accounts,account_number,' . $id,
            'account_name' => 'required',
            'project' => 'required',
        ]);

        // check if account number is exsist
        $account = Account::where('account_number', $validated['account_number'])
            ->where('id', '!=', $id)
            ->first();

        if ($account) {
            return redirect()->route('accounts.index')->with('error', 'Account number already exists!');
        }

        Account::where('id', $id)->update(array_merge($validated, $request->description ? ['description' => $request->description] : []));

        return redirect()->route('accounts.index')->with('success', 'Account updated successfully!');
    }

    public function destroy($id)
    {
        $account = Account::findOrFail($id);
        $account->delete();

        return redirect()->route('accounts.index')->with('success', 'Account deleted successfully!');
    }

    public function outgoing($amount)
    {
        $account_cash = Account::where('type', 'cash')->where('project', auth()->user()->project)->first();
        $account_advance = Account::where('type', 'advance')->where('project', auth()->user()->project)->first();

        // if account is not found
        if (!$account_cash || !$account_advance) {
            return false;
        }

        $account_cash->app_balance = $account_cash->app_balance - $amount;
        $account_cash->save();

        $account_advance->app_balance = $account_advance->app_balance + $amount;
        $account_advance->save();

        return true;
    }

    public function outgoing_manual($amount)
    {
        $account_cash = Account::where('type', 'cash')->where('project', auth()->user()->project)->first();
        // $account_advance = Account::where('type', 'advance')->where('project', auth()->user()->project)->first();

        $account_cash->app_balance = $account_cash->app_balance - $amount;
        $account_cash->save();

        // $account_advance->app_balance = $account_advance->app_balance + $amount;
        // $account_advance->save();

        return true;
    }

    public function incoming($amount)
    {
        $account_cash = Account::where('type', 'cash')->where('project', auth()->user()->project)->first();
        $account_advance = Account::where('type', 'advance')->where('project', auth()->user()->project)->first();

        // if account is not found
        if (!$account_cash || !$account_advance) {
            return false;
        }

        $account_cash->app_balance = $account_cash->app_balance + $amount;
        $account_cash->save();

        $account_advance->app_balance = $account_advance->app_balance - $amount;
        $account_advance->save();

        return true;
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
        $file->move('public/file_upload', $nama_file);

        // IMPORT DATA
        Excel::import(new AccountImport, public_path('/file_upload/' . $nama_file));

        // REDIRECT
        return redirect()->route('accounts.index')->with('success', 'Account imported successfully!');
    }

    public function data()
    {
        // if user has role superadmin or admin or cashier, get all accounts
        if (auth()->user()->hasRole('superadmin') || auth()->user()->hasRole('admin') || auth()->user()->hasRole('cashier')) {
            $accounts = Account::orderBy('account_number', 'asc')
                ->get();
        } else {
            $projects_include = ['all-site', auth()->user()->project];

            $accounts = Account::whereIn('project', $projects_include)
                ->orderBy('account_number', 'asc')
                ->where('is_hidden', 0)
                ->get();
        }

        return datatables()->of($accounts)
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

    public function get_account_name(Request $request)
    {
        if (auth()->user()->project == '000H') {
            $account = Account::where('account_number', $request->account_number)
                ->first();
        } else {
            $project_includes = ['all-site', auth()->user()->project];
            $account = Account::whereIn('project', $project_includes)
                ->where('account_number', $request->account_number)
                ->first();
        }

        if (!$account) {
            $account_name = 'Account not found!';
        } else {
            $account_name = $account->account_name;
        }

        return response()->json($account_name);
    }
}
