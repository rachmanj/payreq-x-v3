<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Payreq;
use Illuminate\Http\Request;

class CashierDashboardController extends Controller
{
    public function index()
    {
        $dashboard_data = $this->dashboard_data();

        return view('cashier.dashboard.index', compact(['dashboard_data']));
    }

    public function dashboard_data()
    {
        $project = auth()->user()->project;

        // Payreqs ready to pay 
        $status_include = ['approved', 'split'];

        $ready_to_pay = Payreq::whereIn('status', $status_include)
            ->where('project', $project);

        $result['ready_to_pay'] = [
            'amount' => $ready_to_pay->sum('amount'),
            'count' => $ready_to_pay->count(),
        ];

        $result['incoming'] = [
            'amount' => 0,
            'count' => 0,
        ];

        $result['pc_balance'] = Account::where('type', 'cash')
            ->where('project', auth()->user()->project)
            ->first()
            ->balance;

        $result['today_outgoing'] = [
            'amount' => 0,
            'count' => 0,
        ];

        $result['today_incoming'] = [
            'amount' => 0,
            'count' => 0,
        ];

        return $result;
    }
}
