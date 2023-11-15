<?php

namespace App\Http\Controllers;

use App\Models\Payreq;
use App\Models\Realization;
use App\Models\RealizationDetail;
use Illuminate\Http\Request;

class UserPayreqHistoriesController extends Controller
{
    public function index()
    {
        return view('user-payreqs.histories.index');
    }

    public function show($payreq_id)
    {
        $payreq = Payreq::with(['realization', 'realization.realizationDetails'])->where('id', $payreq_id)->first();
        // return $payreq;
        // die;

        return view('user-payreqs.histories.show', compact('payreq'));
    }

    public function data()
    {
        $status_include = ['canceled', 'close'];

        $payreqs = Payreq::where('user_id', auth()->user()->id)
            ->whereIn('status', $status_include)
            ->orderBy('created_at', 'desc')
            ->get();

        return datatables()->of($payreqs)
            ->editColumn('amount', function ($payreq) {
                return number_format($payreq->amount, 2);
            })
            ->editColumn('type', function ($payreq) {
                return ucfirst($payreq->type);
            })
            ->editColumn('status', function ($payreq) {
                if ($payreq->status === 'canceled') {
                    $cancel_date = new \Carbon\Carbon($payreq->cancelled_at);
                    return '<button class="badge badge-danger">CANCELED</button> at ' . $cancel_date->addHours(8)->format('d-M-Y H:i') . ' wita';
                } else {
                    $close_date = new \Carbon\Carbon($payreq->updated_at);
                    return '<button class="badge badge-success">CLOSE</button> at ' . $close_date->addHours(8)->format('d-M-Y H:i') . ' wita';
                }
            })
            ->editColumn('created_at', function ($payreq) {
                return $payreq->created_at->addHours(8)->format('d-M-Y H:i') . ' wita';
            })
            ->addColumn('action', 'user-payreqs.histories.action')
            ->rawColumns(['action', 'status'])
            ->addIndexColumn()
            ->toJson();
    }
}
