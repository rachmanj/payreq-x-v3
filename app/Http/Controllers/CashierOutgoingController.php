<?php

namespace App\Http\Controllers;

use App\Models\Outgoing;
use Illuminate\Http\Request;

class CashierOutgoingController extends Controller
{
    public function index()
    {
        return view('cashier.outgoings.index');
    }

    public function data()
    {
        $roles = app(ToolController::class)->getUserRoles();

        if (in_array('superadmin', $roles) || in_array('admin', $roles)) {
            $outgoings = Outgoing::where('status', 'approved')->orWhere('status', 'split')
                ->orderBy('approved_at', 'desc')
                ->get();
        } else {
            $outgoings = Outgoing::where('cashier_id', auth()->user()->id)
                ->orderBy('outgoing_date', 'desc')
                ->get();
        }

        return datatables()->of($outgoings)
            ->addColumn('employee', function ($outgoing) {
                return $outgoing->payreq->requestor->name;
            })
            ->addColumn('payreq_no', function ($outgoing) {
                return $outgoing->payreq->nomor;
            })
            ->editColumn('outgoing_date', function ($outgoing) {
                $outgoing_date = new \Carbon\Carbon($outgoing->outgoing_date);
                return $outgoing_date->addHours(8)->format('d-M-Y');
            })
            ->editColumn('amount', function ($outgoing) {
                return number_format($outgoing->amount, 2);
            })
            ->addColumn('cashier', function ($outgoing) {
                return $outgoing->cashier->name;
            })
            ->addColumn('account', function ($outgoing) {
                return $outgoing->account->account_number . ' - ' . $outgoing->account->account_name;
            })
            ->addIndexColumn()
            // ->addColumn('action', 'cashier.outgoings.action')
            // ->rawColumns(['action', 'amount'])
            ->toJson();
    }
}
