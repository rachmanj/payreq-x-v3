<?php

namespace App\Http\Controllers;

use App\Models\Payreq;
use App\Models\Realization;
use App\Models\User;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function index()
    {
        $test = app(CashierDashboardController::class)->dashboard_data();
        // $test = app(UserPayreqController::class)->ongoing_payreqs();
        // $test = app(UserRealizationController::class)->ongoing_realizations();
        // $test = Realization::with('realizationDetails')->where('id', 3)->first();

        // $test = $this->join_array_test();

        return $test;
    }

    public function join_array_test()
    {
        $user_payreqs_no_realization = Payreq::where('user_id', auth()->user()->id)
            ->where('status', 'paid')
            ->whereDoesntHave('realization')
            ->get();

        $payreq_with_realization_rejected = Payreq::where('user_id', auth()->user()->id)
            ->where('status', 'paid')
            ->whereHas('realization', function ($query) {
                $query->where('status', 'rejected');
            })
            ->distinct()
            ->get();

        // $realization_array = [];
        $realization_array = $user_payreqs_no_realization->merge($payreq_with_realization_rejected);

        return $realization_array;
    }
}