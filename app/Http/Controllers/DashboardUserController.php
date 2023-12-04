<?php

namespace App\Http\Controllers;

use App\Models\ApprovalPlan;
use App\Models\Outgoing;
use App\Models\Payreq;
use Carbon\Carbon;
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
        $avg_completion_days = $this->user_completion_days(auth()->user()->id);

        return view('dashboard.index', compact([
            'wait_approve',
            'user_ongoing_payreqs',
            'user_ongoing_realizations',
            'avg_completion_days'
        ]));
    }

    public function user_completion_days($user_id)
    {
        $payreqs_to_count = Payreq::where('user_id', $user_id)
            ->where('type', 'advance')
            ->where('status', 'close')
            ->get();

        $array_durations = [];
        foreach ($payreqs_to_count as $payreq) {
            $paid_date = Outgoing::select('outgoing_date')->where('payreq_id', $payreq->id)
                ->orderby('outgoing_date', 'desc')
                ->first();

            $duration = Carbon::parse($payreq->realization->approved_at)->diffInDays($paid_date->outgoing_date);

            array_push($array_durations, $duration);
        }

        $sum_durations = array_sum($array_durations);
        $count_durations = count($array_durations);

        if ($count_durations == 0 || $sum_durations == 0) {
            $average_duration = 0;
        } else {
            $average_duration = $sum_durations / $count_durations;
        }

        return $average_duration;
    }
}
