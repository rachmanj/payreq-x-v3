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

        $array_durations = $payreqs_to_count->map(function ($payreq) {
            $paid_date = Outgoing::where('payreq_id', $payreq->id)
                ->orderBy('outgoing_date', 'desc')
                ->value('outgoing_date');

            return Carbon::parse($payreq->realization->approved_at)->diffInDays($paid_date);
        })->toArray();

        $sum_durations = array_sum($array_durations);
        $count_durations = count($array_durations);

        return $count_durations ? $sum_durations / $count_durations : 0;
    }

    public function user_monthly_amount()
    {
        $user_id = auth()->user()->id;
        $year = date('Y');
        $monthly_amount = [];

        for ($month = 1; $month <= 12; $month++) {
            $payreqs = Payreq::where('user_id', $user_id)
                ->where('status', 'close')
                ->whereHas('outgoings', function ($query) use ($month, $year) {
                    $query->whereMonth('outgoing_date', $month)
                        ->whereYear('outgoing_date', $year);
                })
                ->get();

            $realization_details = $payreqs->sum(function ($payreq) {
                if ($payreq->realization && $payreq->realization->realizationDetails) {
                    return $payreq->realization->realizationDetails->sum('amount');
                }
                return 0;
            });

            $monthly_amount[] = [
                'month' => $month,
                'month_name' => date('M', mktime(0, 0, 0, $month, 10)),
                'amount' => $realization_details,
            ];
        }

        return $monthly_amount;
    }
}
