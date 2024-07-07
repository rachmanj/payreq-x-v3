<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use App\Models\Account;
use App\Models\CashierModal;
use App\Models\Incoming;
use App\Models\Outgoing;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Reports\ReportCashierController;

class CashierModalController extends Controller
{
    public function index()
    {
        $project = auth()->user()->project;
        $cashiers = User::role('cashier')->select('id', 'name', 'project')->where('project', $project)->get();
        $head_cashiers = User::role('head_cashier')->select('id', 'name', 'project')->where('project', $project)->get();
        $max_modal = $this->cashier_app_balance();

        $user_open_modal = CashierModal::where('receiver', auth()->user()->id)->where('status', 'open')->first();
        $cashier_button = $user_open_modal ? false : true;
        $closing_balance = app(ReportCashierController::class)->dashboard_data()['closing_balance'];

        return view('cashier.modal.index', compact([
            'cashiers',
            'max_modal',
            'head_cashiers',
            'cashier_button',
            'closing_balance'
        ]));
    }

    public function store(Request $request)
    {
        $request->validate([
            "date" => "required",
            "type" => "required",
            "submit_amount" => "required",
            "receiver" => "required",
        ]);

        // check if submit amount is more than cashier app balance
        $max_modal = $this->cashier_app_balance();
        if ($request->submit_amount > $max_modal) {
            return redirect()->back()->with('error', 'Jumlah modal yang diberikan tidak boleh melebihi saldo modal kas aplikasi');
        }

        if ($request->type == 'eod') {
            $tx_in = Incoming::select('id', 'cashier_id', 'realization_id', 'receive_date', 'amount', 'description')
                ->where('receive_date', $request->date)
                ->where('cashier_id', auth()->user()->id)
                ->sum('amount');

            $tx_out = Outgoing::where('outgoing_date', $request->date)
                ->where('cashier_id', auth()->user()->id)
                ->sum('amount');
        }

        CashierModal::create([
            "date" => $request->date,
            "type" => $request->type,
            "submit_amount" => $request->submit_amount,
            "project" => auth()->user()->project,
            "submitter" => auth()->user()->id,
            "submitter_remarks" => $request->remarks,
            "receiver" => $request->receiver,
            "tx_in" => $request->type == 'eod' ? $tx_in : null,
            "tx_out" => $request->type == 'eod' ? $tx_out : null,
        ]);

        return redirect()->route('cashier.modal.index')->with('success', 'Data berhasil disimpan');
    }

    public function receive(Request $request, $id)
    {
        $request->validate([
            "receive_amount" => "required",
        ]);

        // if receive_amount is not equal to submit_amount then return error
        if ($request->receive_amount != CashierModal::find($id)->submit_amount) {
            return redirect()->back()->with('error', 'Jumlah modal yang diterima harus sama dengan jumlah modal yang diserahkan');
        }

        $modal = CashierModal::find($id);
        $modal->update([
            "receive_amount" => $request->receive_amount,
            "receiver" => auth()->user()->id,
            "receiver_remarks" => $request->remarks,
            'status' => 'close',
        ]);

        return redirect()->back()->with('success', 'Data berhasil disimpan');
    }

    public function data()
    {
        $userRoles = app(UserController::class)->getUserRoles();

        if (in_array(['superadmin', 'admin'], $userRoles)) {
            $modals = CashierModal::orderBy('created_at', 'desc')->get();
        } else {
            $modals = CashierModal::where('receiver', auth()->user()->id)
                ->orWhere('submitter', auth()->user()->id)
                ->orderBy('created_at', 'desc')->get();
        }

        return datatables()->of($modals)
            ->editColumn('submitter', function ($modal) {
                if ($modal->submitter == null) {
                    return '-';
                } else {
                    if ($modal->type == 'eod') {
                        $mutasi = $modal->tx_in - $modal->tx_out;
                        return $modal->submittedBy->name . '<br><small><b>Mutasi: Rp.' . number_format($mutasi, 2) . '<br>Rp.' . number_format($modal->submit_amount, 2) . '</b></small>';
                    } else {
                        return $modal->submittedBy->name . '<br><small><b>Rp.' . number_format($modal->submit_amount, 2) . '</b></small>';
                    }
                }
            })
            ->editColumn('receiver', function ($modal) {
                if ($modal->receiver == null) {
                    return '-';
                } else {
                    return $modal->receivedBy->name . '<br><small><b>Rp.' . number_format($modal->receive_amount, 2) . '</b></small>';
                }
            })
            ->addColumn('mutasi', function ($modal) {
                $mutasi = $modal->tx_in - $modal->tx_out;
                return number_format($mutasi, 2);
            })
            ->addColumn('saldo', function ($modal) {
                $mutasi = $modal->tx_in - $modal->tx_out;
                $saldo = $modal->submit_amount + $mutasi;
                return number_format($saldo, 2);
            })
            ->editColumn('type', function ($modal) {
                return ($modal->type == 'bod') ? 'BOD' : 'EOD';
            })
            ->editColumn('date', function ($modal) {
                return date('d-M-Y', strtotime($modal->date));
            })
            ->addIndexColumn()
            ->addColumn('action', 'cashier.modal.action')
            ->rawColumns(['action', 'submitter', 'receiver'])
            ->toJson();
    }

    public function cashier_app_balance()
    {
        $cashier_app_balance = Account::where('project', auth()->user()->project)->where('type', 'cash')->first()->app_balance;

        return $cashier_app_balance;
    }
}
