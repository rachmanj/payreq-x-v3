<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Installment;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index()
    {
        return view('reports.loan.index');
    }

    public function index_7997()
    {
        return view('reports.loan.index_7997');
    }

    public function index_all()
    {
        return view('reports.loan.index_all');
    }

    public function update(Request $request)
    {
        $installment = Installment::find($request->installment_id);

        $status = $request->paid_date ? 'paid' : null;

        $installment->paid_date = $request->paid_date;
        $installment->status = $status;
        $installment->bilyet_no = $request->bilyet_no;
        $installment->account_id = $request->account_id;
        $installment->save();

        return redirect()->route('reports.loan.index');
    }

    public function data(Request $request)
    {
        if ($request->akun_no === 'all') {
            $installments = Installment::with('loan')
                ->where('due_date', '<=', date('Y-m-d', strtotime('last day of this month')))
                ->whereNull('paid_date')
                ->orderBy('due_date', 'asc')
                ->get();
        } else {
            $akun = Account::where('account_number', $request->akun_no)->first();

            if ($akun) {
                $akun_id = $akun->id;
            } else {
                $akun_id = "";
            }

            $installments = Installment::with('loan')
                ->where('account_id', $akun_id)
                ->where('due_date', '<=', date('Y-m-d', strtotime('last day of this month')))
                ->whereNull('paid_date')
                ->orderBy('due_date', 'asc')
                ->get();
        }

        return datatables()->of($installments)
            ->addColumn('creditor', function ($installment) {
                return $installment->loan->creditor->name;
            })
            ->editColumn('due_date', function ($installment) {
                return date('d-M-Y', strtotime($installment->due_date));
            })
            ->editColumn('bilyet_amount', function ($installment) {
                return number_format($installment->bilyet_amount, 2, ',', '.');
            })
            ->addIndexColumn()
            ->addColumn('action', 'reports.loan.action')
            ->toJson();
    }

    public function paid_data(Request $request)
    {
        if ($request->akun_no === 'all') {
            $installments = Installment::with('loan')
                ->whereMonth('paid_date', date('m'))
                ->whereNotNull('paid_date')
                ->orderBy('due_date', 'asc')
                ->get();
        } else {
            $akun = Account::where('account_number', $request->akun_no)->first();

            if ($akun) {
                $akun_id = $akun->id;
            } else {
                $akun_id = "";
            }

            $installments = Installment::with('loan')
                ->where('account_id', $akun_id)
                ->whereMonth('paid_date', date('m'))
                ->whereNotNull('paid_date')
                ->orderBy('due_date', 'asc')
                ->get();
        }

        return datatables()->of($installments)
            ->addColumn('creditor', function ($installment) {
                return $installment->loan->creditor->name;
            })
            ->editColumn('paid_date', function ($installment) {
                return date('d-M-Y', strtotime($installment->due_date));
            })
            ->editColumn('bilyet_amount', function ($installment) {
                return number_format($installment->bilyet_amount, 2, ',', '.');
            })
            ->addIndexColumn()
            ->addColumn('action', 'reports.loan.action')
            ->toJson();
    }
}
