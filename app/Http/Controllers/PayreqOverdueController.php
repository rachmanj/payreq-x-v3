<?php

namespace App\Http\Controllers;

use App\Models\Payreq;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PayreqOverdueController extends Controller
{
    public function index()
    {
        return view('document-overdue.payreq.index');
    }

    public function extend(Request $request)
    {
        $payreq = Payreq::find($request->payreq_id);
        $payreq->due_date = $request->new_due_date;
        $payreq->save();

        return redirect()->route('document-overdue.payreq.index')->with('success', 'Payreq extended successfully.');
    }

    public function data()
    {
        $status_include = ['paid'];
        $payreqs = Payreq::whereDate('due_date', '<=', now())
            ->where('type', 'advance')
            ->whereIn('status', $status_include)
            ->get();

        return datatables()->of($payreqs)
            ->addColumn(('employee'), function ($payreq) {
                return $payreq->requestor->name;
            })
            ->editColumn('nomor', function ($approved) {
                return '<a href="#" style="color: black" title="' . $approved->remarks . '">' . $approved->nomor . '</a>';
            })
            ->addColumn('days', function ($payreq) {
                return Carbon::parse($payreq->due_date)->diffInDays(now());
            })
            ->editColumn('amount', function ($payreq) {
                return number_format($payreq->amount, 2);
            })
            ->addIndexColumn()
            ->addColumn('action', 'document-overdue.payreq.action')
            ->rawColumns(['action', 'nomor'])
            ->toJson();
    }
}
