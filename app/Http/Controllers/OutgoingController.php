<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Outgoing;
use App\Models\Payreq;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OutgoingController extends Controller
{
    public function index()
    {
        return view('outgoings.index');
    }

    public function create($payreq_id)
    {
        $payreq = Payreq::findOrfail($payreq_id);
        return view('outgoings.create', compact('payreq'));
    }

    public function quick($payreq_id)
    {
        $payreq = Payreq::findOrfail($payreq_id);
        $account_id = Account::where('account_name', 'petty cash')
            ->where('project', auth()->user()->project)
            ->first()->id;

        Outgoing::create([
            'payreq_id' => $payreq->id,
            'account_id' => $account_id,
            'amount' => $payreq->amount,
            'cashier_id' => auth()->user()->id,
            'outgoing_date' => now(),
        ]);

        $payreq->update([
            'status' => 'paid',
            'printable' => 0,
        ]);

        return view('outgoings.index')->with('success', 'Payment Request paid successfully.');
    }

    public function store(Request $request, $payreq_id)
    {
        $payreq = Payreq::findOrfail($payreq_id);
    }

    public function data()
    {
        $payreqs = Payreq::where('status', 'approved')
            ->orderBy('approved_at', 'desc')
            ->get();

        return datatables()->of($payreqs)
            ->editColumn('approved_at', function ($payreq) {
                $date =  new Carbon($payreq->approved_at);
                return $date->addHours(8)->format('d M Y H:i:s');
            })
            ->addColumn('requestor', function ($payreq) {
                return $payreq->requestor->name;
            })
            ->editColumn('amount', function ($payreq) {
                $sum_outgoings = Outgoing::where('payreq_id', $payreq->id)->sum('amount');
                if (!$sum_outgoings) {
                    return number_format($payreq->amount, 2, ',', '.');
                } else {
                    return number_format($payreq->amount - $sum_outgoings, 2, ',', '.') . ' of ' . number_format($payreq->amount, 2, ',', '.');
                }
            })
            ->editColumn('type', function ($payreq) {
                return ucfirst($payreq->type);
            })
            ->addColumn('days', function ($payreq) {
                $date =  new Carbon($payreq->approved_at);
                $days = $date->diffInDays(now());
                return $days;
            })
            ->addIndexColumn()
            ->addColumn('action', 'outgoings.action')
            ->rawColumns(['action'])
            ->toJson();
    }
}
