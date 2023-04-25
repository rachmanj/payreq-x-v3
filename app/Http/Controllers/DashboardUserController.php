<?php

namespace App\Http\Controllers;

use App\Models\Payreq;
use Illuminate\Http\Request;

class DashboardUserController extends Controller
{
    public function index()
    {
        $outstanding_payreq = $this->not_realization()->sum('payreq_idr') + $this->not_verify()->sum('payreq_idr');

        return view('user-dashboard.index', [
            'not_realization' => $this->not_realization(),
            'not_verify' => $this->not_verify(),
            'just_approved' => $this->just_approved(),
            'outstanding_payreq' => $outstanding_payreq,
        ]);
    }

    public function show($id)
    {
        return view('user-dashboard.show', [
            'payreq' => Payreq::find($id),
        ]);
    }

    public function just_approved()
    {
        return Payreq::where('user_id', auth()->user()->id)
            ->whereNull('outgoing_date')
            ->select('id', 'payreq_num', 'payreq_idr', 'payreq_type', 'approve_date')
            ->selectRaw('datediff(now(), approve_date) as days')
            ->orderBy('approve_date', 'asc');
    }

    public function not_realization()
    {
        return Payreq::where('user_id', auth()->user()->id)
            ->where('payreq_type', 'advance')
            ->whereNotNull('outgoing_date')
            ->whereNull('realization_date')
            ->select('id', 'payreq_num', 'payreq_idr', 'outgoing_date', 'realization_date')
            ->selectRaw('datediff(now(), outgoing_date) as days')
            ->orderBy('outgoing_date', 'asc');
    }

    public function not_verify()
    {
        return Payreq::where('user_id', auth()->user()->id)
            ->where('payreq_type', 'advance')
            ->whereNotNull('outgoing_date')
            ->whereNotNull('realization_date')
            ->whereNull('verify_date')
            ->select('id', 'payreq_num', 'payreq_idr', 'outgoing_date', 'realization_date', 'realization_num')
            ->selectRaw('datediff(now(), realization_date) as days')
            ->orderBy('realization_date', 'asc');
    }
}
