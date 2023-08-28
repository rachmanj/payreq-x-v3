<?php

namespace App\Http\Controllers;

use App\Models\ApprovalPlan;
use App\Models\Payreq;
use Illuminate\Http\Request;

class DashboardUserController extends Controller
{
    public function index()
    {
        $wait_approve = ApprovalPlan::where('is_open', 1)
            ->where('status', 0)
            ->where('approver_id', auth()->user()->id)
            ->count();

        return view('dashboard.index', compact('wait_approve'));
    }

    public function show($id)
    {
        // 
    }

    public function just_approved()
    {
        // 
    }

    public function not_realization()
    {
        // 
    }

    public function not_verify()
    {
        //    
    }
}
