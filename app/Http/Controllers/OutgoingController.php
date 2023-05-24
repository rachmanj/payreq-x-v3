<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Outgoing;
use App\Models\Payreq;
use App\Models\Rekap;
use App\Models\Split;
use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OutgoingController extends Controller
{
    public function index()
    {
        return view('outgoings.index');
    }

    public function store($payreq_id)
    {
        $payreq = Payreq::findOrfail($payreq_id);
    }

    public function data()
    {
        $payreqs = Payreq::where('status', 'approved')
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
            ->addIndexColumn()
            ->addColumn('action', 'outgoings.action')
            ->rawColumns(['action'])
            ->toJson();
    }
}
