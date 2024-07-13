<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use App\Models\CashierModal;
use App\Models\Incoming;
use App\Models\Outgoing;
use Illuminate\Http\Request;

class ReportCashierController extends Controller
{
    public function index()
    {
        return view('reports.cashier.index', ['data' => $this->dashboard_data()]);
    }

    public function dashboard_data()
    {
        $closing_balance = $this->getLastSerahTerimaModal() + $this->getIncomings()->sum('amount') - $this->getOutgoings()->sum('amount');
        $formated_closing_balance = number_format($closing_balance, 2);

        $data = [
            'opening_balance' => $this->getLastSerahTerimaModal(),
            'total_incoming' => number_format($this->getIncomings()->sum('amount'), 2),
            'total_outgoing' => number_format($this->getOutgoings()->sum('amount'), 2),
            'closing_balance' => $formated_closing_balance,
            'incomings' => $this->getIncomings(),
            'outgoings' => $this->getOutgoings(),
        ];

        return $data;
    }

    public function getLastSerahTerimaModal()
    {
        $userRoles = app(UserController::class)->getUserRoles();

        if (array_intersect(['superadmin', 'admin', 'approver'], $userRoles)) {
            $last_serah_terima_modal = CashierModal::where('type', 'bod')
                ->where('status', 'close')
                ->where('date', date('Y-m-d'))
                ->get()
                ->sum('receive_amount');
        } else {
            $last_serah_terima_modal = CashierModal::where('type', 'bod')
                ->where('receiver', auth()->user()->id)
                ->where('status', 'close')
                ->orderBy('date', 'desc')
                ->first()
                ->receive_amount;
        }

        if ($last_serah_terima_modal) {
            return $last_serah_terima_modal;
        } else {
            return 0;
        }
    }

    public function getIncomings()
    {
        $date = date('Y-m-d');
        $userRoles = app(UserController::class)->getUserRoles();

        if (array_intersect(['superadmin', 'admin', 'approver'], $userRoles)) {
            $incomings = Incoming::select('id', 'cashier_id', 'realization_id', 'receive_date', 'amount', 'description')
                ->where('receive_date',  $date)
                ->get();
        } else {
            $incomings = Incoming::select('id', 'cashier_id', 'realization_id', 'receive_date', 'amount', 'description')
                ->where('receive_date',  $date)
                ->where('cashier_id', auth()->user()->id)
                ->get();
        }

        foreach ($incomings as $incoming) {
            $realization_desc = $incoming->realization_id !== null ? $incoming->realization->requestor->name . ", realization no " . $incoming->realization->nomor : $incoming->description;
            $incoming->description = $realization_desc;
        }

        return $incomings;
    }

    public function getOutgoings()
    {
        $date = date('Y-m-d');

        $userRoles = app(UserController::class)->getUserRoles();

        if (array_intersect(['superadmin', 'admin', 'approver'], $userRoles)) {
            $outgoings = Outgoing::where('outgoing_date', $date)
                ->get();
        } else {
            $outgoings = Outgoing::where('outgoing_date', $date)
                ->where('cashier_id', auth()->user()->id)
                ->get();
        }

        foreach ($outgoings as $outgoing) {
            $payreq_no = $outgoing->payreq->nomor;
            $employee = $outgoing->payreq->requestor->name;
            $outgoing->description = $employee . ", payreq no " . $payreq_no;
        }

        return $outgoings;
    }
}
