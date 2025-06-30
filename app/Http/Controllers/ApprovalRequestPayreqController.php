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

    public function show($id)
    {
        $document = ApprovalPlan::find($id);
        $payreq = $document->payreq;
        $realization = $payreq->realization;
        $realization_details = $realization->realizationDetails;

        return view('approvals-request.payreqs.show', compact([
            'document',
            'payreq',
            'realization',
            'realization_details',
        ]));
    }

    public function data()
    {
        $approval_requests = ApprovalPlan::where('document_type', 'payreq')
            ->where('is_open', 1)
            ->where('status', 0)
            ->where('approver_id', auth()->user()->id)
            ->get();

        return datatables()->of($approval_requests)
            ->addColumn('id', function ($approval_request) {
                return $approval_request->id;
            })
            ->addColumn('nomor', function ($approval_request) {
                return $approval_request->payreq->nomor;
            })
            ->addColumn('submit_at', function ($approval_request) {
                return $approval_request->payreq->submit_at->addHours(8)->format('d-M-Y H:i:s');
            })
            ->addColumn('type', function ($approval_request) {
                return ucfirst($approval_request->payreq->type);
            })
            ->addColumn('amount', function ($approval_request) {
                // if payreq type is advance
                if ($approval_request->payreq->type == 'advance') {
                    return number_format($approval_request->payreq->amount, 2);
                } else {
                    // $realization_details = $approval_request->payreq->realization->realizationDetails;
                    // $amount = $realization_details->sum('amount');
                    return number_format($approval_request->payreq->realization->realizationDetails->sum('amount'), 2);
                }
            })
            ->addColumn('requestor', function ($approval_request) {
                return $approval_request->payreq->requestor->name;
            })
            ->addColumn('days', function ($approval_request) {
                return $approval_request->payreq->submit_at->diffInDays(now());
            })
            ->addIndexColumn()
            ->addColumn('action', 'approvals-request.payreqs.action')
            ->rawColumns(['action'])
            ->toJson();
    }
}
