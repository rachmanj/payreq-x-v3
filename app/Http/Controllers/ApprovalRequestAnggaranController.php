<?php

namespace App\Http\Controllers;

use App\Models\ApprovalPlan;

class ApprovalRequestAnggaranController extends Controller
{
    public function index()
    {
        $document_count = app(ToolController::class)->approval_documents_count();

        return view('approvals-request.anggarans.index', compact('document_count'));
    }

    public function data()
    {
        $approval_requests = ApprovalPlan::query()
            ->where('document_type', 'rab')
            ->where('is_open', 1)
            ->where('status', 0)
            ->where('approver_id', auth()->user()->id)
            ->with(['anggaran.details', 'anggaran.createdBy'])
            ->get();

        return datatables()->of($approval_requests)
            ->addColumn('id', function ($approval_request) {
                return $approval_request->id;
            })
            ->addColumn('nomor', function ($approval_request) {
                return $approval_request->anggaran->nomor;
            })
            ->addColumn('project', function ($approval_request) {
                return $approval_request->anggaran->rab_project;
            })
            ->addColumn('created_at', function ($approval_request) {
                return $approval_request->anggaran->created_at->addHours(8)->format('d-M-Y H:i:s');
            })
            ->addColumn('type', function ($approval_request) {
                return ucfirst($approval_request->anggaran->type);
            })
            ->addColumn('amount', function ($approval_request) {
                return number_format($approval_request->anggaran->amount, 2);
            })
            ->addColumn('requestor', function ($approval_request) {
                return $approval_request->anggaran->createdBy->name;
            })
            ->addColumn('days', function ($approval_request) {
                return $approval_request->anggaran->created_at->diffInDays(now());
            })
            ->addIndexColumn()
            ->addColumn('action', 'approvals-request.anggarans.action')
            ->rawColumns(['action'])
            ->toJson();
    }
}
