<?php

namespace App\Http\Controllers;

use App\Models\Outgoing;
use App\Models\Payreq;
use Illuminate\Http\Request;

class UserOngoingController extends Controller
{
    public function index()
    {
        return view('user-payreqs.ongoings.index');
    }

    public function data()
    {
        // get user's roles
        $userRoles = app(UserController::class)->getUserRoles();

        if (in_array('superadmin', $userRoles) || in_array('admin', $userRoles)) {
            $payreqs = Payreq::where('type', 'advance')
                ->where('status', 'paid')
                ->get();
        } else {
            $payreqs = Payreq::where('user_id', auth()->user()->id)
                ->where('type', 'advance')
                ->where('status', 'paid')
                ->get();
        }

        return datatables()->of($payreqs)
            ->editColumn('amount', function ($payreqs) {
                return number_format($payreqs->amount, 2);
            })
            ->addColumn('outgoing_date', function ($payreq) {
                $last_outgoing = app(ToolController::class)->getLastOutgoing($payreq->id);
                return date('d-M-Y', strtotime($last_outgoing->outgoing_date));
            })
            ->editColumn('status', function ($payreq) {
                return ucfirst($payreq->status);
            })
            ->addColumn('days', function ($payreq) {
                $last_outgoing = app(ToolController::class)->getLastOutgoing($payreq->id);
                $date1 = date_create($last_outgoing->outgoing_date);
                $date2 = date_create(date('Y-m-d'));
                $diff = date_diff($date1, $date2);
                $days = $diff->format("%a");

                return $days;
            })
            // ->addColumn('action', 'user-payreqs.ongoings.action')
            // ->rawColumns(['action'])
            ->addIndexColumn()
            ->toJson();
    }
}
