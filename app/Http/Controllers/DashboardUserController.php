<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Accounting\SapSyncController;
use App\Models\ApprovalPlan;
use App\Models\Outgoing;
use App\Models\Payreq;
use Carbon\Carbon;

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
        $monthly_chart = $this->user_monthly_amount();
        $vj_not_posted = app(SapSyncController::class)->vjNotPosted()->count();
        $chart_activites = app(SapSyncController::class)->chart_vj_postby();

        return view('dashboard.index', compact([
            'wait_approve',
            'user_ongoing_payreqs',
            'user_ongoing_realizations',
            'avg_completion_days',
            'monthly_chart',
            'vj_not_posted',
            'chart_activites',
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

    public function user_monthly_amount()
    {
        $user_id = auth()->user()->id;

        $year = date('Y'); // Assign the current year to the variable
        // get months in current year
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            array_push($months, $i);
        }

        foreach ($months as $month) {
            $payreqs = Payreq::select('id', 'user_id', 'nomor')->where('user_id', $user_id)
                ->where('status', 'close')
                // ->whereMonth('approved_at', $month)
                // ->whereYear('approved_at', $year)
                ->whereHas('outgoings', function ($query) use ($month, $year) {
                    $query->whereMonth('outgoing_date', $month)
                        ->whereYear('outgoing_date', $year);
                })
                ->get();

            $realization_details = $payreqs->pluck('realization')->flatten()->pluck('realizationDetails')->flatten()->sum('amount');

            $month_name = substr(date('F', mktime(0, 0, 0, $month, 10)), 0, 3);

            $monthly_amount[] = [
                'month' => $month,
                'month_name' => $month_name,
                'amount' => $realization_details,
            ];
        }

        // return $realization_details;
        return $monthly_amount;
    }
}
