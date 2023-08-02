<?php

namespace App\Http\Controllers;

use App\Models\Payreq;
use App\Models\Realization;
use Illuminate\Http\Request;

class UserRealizationController extends Controller
{
    public function index()
    {
        return view('user-payreqs.realizations.index');
    }

    public function create()
    {
        $user_payreqs = Payreq::where('user_id', auth()->user()->id)
            ->where('status', 'paid')
            ->get();

        $realization_no = app(ToolController::class)->generateDraftRealizationNumber();

        return view('user-payreqs.realizations.create', compact('user_payreqs', 'realization_no'));
    }

    public function data()
    {
        // get user's roles
        $userRoles = app(UserController::class)->getUserRoles();

        if (in_array('superadmin', $userRoles) || in_array('admin', $userRoles)) {
            $realizations = Realization::get();
        } else {
            $realizations = Realization::where('user_id', auth()->user()->id)
                ->get();
        }

        return datatables()->of($realizations)
            ->addColumn('payreq_no', function ($realization) {
                return $realization->payreq->payreq_no;
            })
            ->addColumn('amount', function ($realization) {
                return number_format($realization->realizationDetails->sum('amount'), 2, ',', '.');
            })
            ->addIndexColumn()
            ->toJson();
    }
}
