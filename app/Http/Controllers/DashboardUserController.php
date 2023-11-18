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

        $user_ongoing_payreqs = app(UserPayreqController::class)->ongoing_payreqs();
        $user_ongoing_realizations = app(UserRealizationController::class)->ongoing_realizations();

        return view('dashboard.index', compact([
            'wait_approve',
            'user_ongoing_payreqs',
            'user_ongoing_realizations'
        ]));
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
