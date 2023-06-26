<?php

namespace App\Http\Controllers;

use App\Models\Outgoing;
use App\Models\Payreq;
use Illuminate\Http\Request;

class OngoingController extends Controller
{
    public function index()
    {
        return view('ongoings.index');
    }

    public function data()
    {
        // get user's roles
        $userRoles = app(UserController::class)->getUserRoles();

        if (in_array('superadmin', $userRoles) || in_array('admin', $userRoles)) {
            $payreqs = Payreq::where('status', 'paid')
                ->get();
        } else {
            $payreqs = Payreq::where('user_id', auth()->user()->id)
                ->where('status', 'paid')
                // ->orderBy('outgoing_date', 'desc')
                ->get();
        }

        return datatables()->of($payreqs)
            ->editColumn('amount', function ($payreqs) {
                return number_format($payreqs->amount, 2);
            })
            ->addColumn('outgoing_date', function ($payreq) {
                $outgoing_date = $this->getLastOutgoing($payreq->id);
                return date('d-M-Y', strtotime($outgoing_date->outgoing_date));
            })
            ->editColumn('status', function ($payreq) {
                return ucfirst($payreq->status);
            })
            ->addColumn('days', function ($payreq) {
                $outgoing = $this->getLastOutgoing($payreq->id);
                $date1 = date_create($outgoing->outgoing_date);
                $date2 = date_create(date('Y-m-d'));
                $diff = date_diff($date1, $date2);
                $days = $diff->format("%a");

                return $days;
            })
            ->addColumn('action', 'ongoings.action')
            ->rawColumns(['action'])
            ->addIndexColumn()
            ->toJson();
    }

    public function getLastOutgoing($payreq_id)
    {
        $lastOutgoing = Outgoing::where('payreq_id', $payreq_id)
            ->orderBy('created_at', 'desc')
            ->first();

        return $lastOutgoing;
    }
}
