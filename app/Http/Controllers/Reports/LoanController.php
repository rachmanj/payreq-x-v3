<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Installment;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index()
    {
        return view('reports.loan.index');
    }

    public function update(Request $request)
    {
        $installment = Installment::find($request->installment_id);

        $installment->paid_date = $request->paid_date;
        $installment->status = 'paid';
        $installment->save();

        return redirect()->route('reports.loan.index');
    }

    public function data()
    {
        $installments = Installment::with('loan')
            ->where('account_id', 142)
            ->where('due_date', '<=', date('Y-m-d'))
            ->whereNull('paid_date')
            ->orderBy('due_date', 'asc')
            ->get();

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
}
