<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApprovalRequestRabController extends Controller
{
    public function index()
    {
        $document_count = app(ToolController::class)->approval_documents_count();

        return "Page Not Fund";

        // return view('approvals-request.rabs.index', compact('document_count'));
    }
}
