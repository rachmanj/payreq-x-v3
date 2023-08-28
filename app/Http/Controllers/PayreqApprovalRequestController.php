<?php

namespace App\Http\Controllers;

use App\Models\ApprovalPlan;
use Illuminate\Http\Request;

class PayreqApprovalRequestController extends Controller
{
    public function index()
    {
        return view('approvals-request.payreqs.index');
    }

    public function data()
    {
        $stage_plans = ApprovalPlan::where('is_open', 1)
            ->where('status', 0)
            ->where('approver_id', auth()->user()->id)
            ->get();

        return datatables()->of($stage_plans)
            ->addColumn('nomor', function ($stage_plans) {
                return $stage_plans->payreq->nomor;
            })
            ->addColumn('created_at', function ($stage_plans) {
                return $stage_plans->payreq->created_at->addHours(8)->format('d-M-Y H:i:s');
            })
            ->addColumn('type', function ($stage_plans) {
                return ucfirst($stage_plans->payreq->type);
            })
            ->addColumn('amount', function ($stage_plans) {
                return number_format($stage_plans->payreq->amount, 2);
            })
            ->addColumn('requestor', function ($stage_plans) {
                return $stage_plans->payreq->requestor->name;
            })
            ->addColumn('days', function ($stage_plans) {
                return $stage_plans->payreq->created_at->diffInDays(now());
            })
            ->addIndexColumn()
            ->addColumn('action', 'approvals-request.payreqs.action')
            ->rawColumns(['action'])
            ->toJson();
    }
}
