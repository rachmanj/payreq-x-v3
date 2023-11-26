<?php

namespace App\Http\Controllers;

use App\Imports\AccountImport;
use App\Models\Account;
use App\Models\Realization;
use App\Models\RealizationDetail;
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

    public function outgoing($amount)
    {
        $account_cash = Account::where('type', 'cash')->where('project', auth()->user()->project)->first();
        $account_advance = Account::where('type', 'advance')->where('project', auth()->user()->project)->first();

        $account_cash->app_balance = $account_cash->app_balance - $amount;
        $account_cash->save();

        $account_advance->app_balance = $account_advance->app_balance + $amount;
        $account_advance->save();

        return true;
    }

    public function incoming($amount)
    {
        $account_cash = Account::where('type', 'cash')->where('project', auth()->user()->project)->first();
        $account_advance = Account::where('type', 'advance')->where('project', auth()->user()->project)->first();

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
        // get user's roles
        $userRoles = app(UserController::class)->getUserRoles();

        if (in_array('superadmin', $userRoles) || in_array('admin', $userRoles)) {
            $accounts = Account::orderBy('account_number', 'asc')
                ->get();
        } else {
            $accounts = Account::where('project', auth()->user()->project)
                ->orderBy('account_number', 'asc')
                ->where('is_hidden', 0)
                ->get();
        }

        $accounts = Account::orderBy('account_number', 'asc')
            ->where('project', auth()->user()->project)
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

    public function get_account_name(Request $request)
    {
        $account = Account::where('project', auth()->user()->project)
            ->where('account_number', $request->account_number)
            ->first();

        // $realization_detail = RealizationDetail::findOrFail($request->realization_detail_id);

        if (!$account) {
            $account_name = 'Account not found!';
        } else {
            // $realization_detail->update([
            //     'account_id' => $account->id,
            //     'flag' => 'VERTEMP' . auth()->user()->id,   //verification temporary
            // ]);
            $account_name = $account->account_name;
        }

        return response()->json($account_name);
    }
}
