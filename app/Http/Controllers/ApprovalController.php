<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function getApprovalCount($payreq_id)
    {
        $approval_count = Approval::where('payreq_id', $payreq_id)
            ->where('status', 'approved')
            ->count();

        return $approval_count;
    }
}
