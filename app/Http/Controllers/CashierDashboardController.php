<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Incoming;
use App\Models\Outgoing;
use App\Models\Payreq;
use Carbon\Carbon;
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
            'amount' => Incoming::where('project', $project)->whereNull('receive_date')->sum('amount'),
            'count' => Incoming::where('project', $project)->whereNull('receive_date')->count(),
        ];

        $today = Carbon::today();
        $result['today_incoming'] = [
            'amount' => Incoming::where('project', $project)->where('receive_date', $today)->sum('amount'),
            'count' => Incoming::where('project', $project)->where('receive_date', $today)->count(),
        ];

        $result['today_outgoing'] = [
            'amount' => Outgoing::where('project', $project)->where('outgoing_date', $today)->sum('amount'),
            'count' => Outgoing::where('project', $project)->where('outgoing_date', $today)->count()
        ];

        $result['today_pc_balance'] = Account::where('type', 'cash')
            ->where('project', auth()->user()->project)
            ->first()
            ->app_balance;

        return $result;
    }
}
