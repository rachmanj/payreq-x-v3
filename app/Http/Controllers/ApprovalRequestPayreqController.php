<?php

namespace App\Http\Controllers;

use App\Models\ApprovalPlan;
use Illuminate\Http\Request;

class ApprovalRequestPayreqController extends Controller
{
    public function index()
    {
        $document_count = app(ToolController::class)->approval_documents_count();

        return view('approvals-request.payreqs.index', compact('document_count'));
    }

    public function data()
    {
        $approval_requests = ApprovalPlan::where('document_type', 'payreq')
            ->where('is_open', 1)
            ->where('status', 0)
            ->where('approver_id', auth()->user()->id)
            ->get();

        return datatables()->of($approval_requests)
            ->addColumn('nomor', function ($approval_request) {
                return $approval_request->payreq->nomor;
            })
            ->addColumn('created_at', function ($approval_request) {
                return $approval_request->payreq->created_at->addHours(8)->format('d-M-Y H:i:s');
            })
            ->addColumn('type', function ($approval_request) {
                return ucfirst($approval_request->payreq->type);
            })
            ->addColumn('amount', function ($approval_request) {
                return number_format($approval_request->payreq->amount, 2);
            })
            ->addColumn('requestor', function ($approval_request) {
                return $approval_request->payreq->requestor->name;
            })
            ->addColumn('days', function ($approval_request) {
                return $approval_request->payreq->created_at->diffInDays(now());
            })
            ->addIndexColumn()
            ->addColumn('action', 'approvals-request.payreqs.action')
            ->rawColumns(['action'])
            ->toJson();
    }
}
