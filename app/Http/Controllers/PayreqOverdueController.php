<?php

namespace App\Http\Controllers;

use App\Models\Payreq;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PayreqOverdueController extends Controller
{
    public function index()
    {
        return view('payreq-overdue.index');
    }

    public function extend(Request $request)
    {
        $payreq = Payreq::find($request->payreq_id);
        $payreq->due_date = $request->new_due_date;
        $payreq->save();

        return redirect()->route('payreq-overdue.index')->with('success', 'Payreq extended successfully.');
    }

    public function data()
    {
        $payreqs = Payreq::whereDate('due_date', '<=', now())
            ->get();

        return datatables()->of($payreqs)
            ->addColumn(('employee'), function ($payreq) {
                return $payreq->requestor->name;
            })
            ->addColumn('days', function ($payreq) {
                return Carbon::parse($payreq->due_date)->diffInDays(now());
            })
            ->editColumn('amount', function ($payreq) {
                return number_format($payreq->amount, 2);
            })
            ->addIndexColumn()
            ->addColumn('action', 'payreq-overdue.action')
            ->rawColumns(['action'])
            ->toJson();
    }
}
