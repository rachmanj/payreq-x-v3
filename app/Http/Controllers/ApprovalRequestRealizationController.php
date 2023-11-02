<?php

namespace App\Http\Controllers;

use App\Models\ApprovalPlan;
use Illuminate\Http\Request;

class ApprovalRequestRealizationController extends Controller
{
    public function index()
    {
        $document_count = app(ToolController::class)->approval_documents_count();

        return view('approvals-request.realizations.index', compact('document_count'));
    }

    public function show($id)
    {
        $document = ApprovalPlan::find($id);
        $document_details = $document->realization->realizationDetails;
        $payreq = $document->realization->payreq;

        return view('approvals-request.realizations.show', compact([
            'document',
            'document_details',
            'payreq'
        ]));
    }

    public function data()
    {
        $approval_requests = ApprovalPlan::where('is_open', 1)
            ->where('document_type', 'realization')
            ->where('status', 0)
            ->where('approver_id', auth()->user()->id)
            ->get();

        return datatables()->of($approval_requests)
            ->addColumn('nomor', function ($approval_request) {
                return $approval_request->realization->nomor;
            })
            ->addColumn('payreq_no', function ($approval_request) {
                return $approval_request->realization->payreq->nomor;
            })
            ->addColumn('submit_at', function ($approval_request) {
                $date = new \Carbon\Carbon($approval_request->realization->submit_at);
                return $date->addHours(8)->format('d-M-Y H:i:s') . ' wita';
            })
            ->addColumn('amount', function ($approval_request) {
                return number_format($approval_request->realization->realizationDetails->sum('amount'), 2);
            })
            ->addColumn('requestor', function ($approval_request) {
                return $approval_request->realization->requestor->name;
            })
            ->addColumn('days', function ($approval_request) {
                return $approval_request->realization->created_at->diffInDays(now());
            })
            ->addIndexColumn()
            ->addColumn('action', 'approvals-request.realizations.action')
            ->rawColumns(['action'])
            ->toJson();
    }
}
