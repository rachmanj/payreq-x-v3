<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Cashier\TransaksiController;
use App\Models\Account;
use App\Models\Outgoing;
use App\Models\Payreq;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CashierApprovedController extends Controller
{
    public function index()
    {
        return view('cashier.approved.index');
    }

    public function auto_outgoing($id)
    {
        $payreq = Payreq::findOrfail($id);
        $cashier = auth()->user();

        $request = new Request();
        $request['payreq_id'] = $id;
        $request['amount'] = $payreq->amount;
        $request['cashier_id'] = $cashier->id;
        $request['account_id'] = Account::where('type', 'cash')->where('project', $cashier->project)->first()->id;
        $request['project'] = $cashier->project;
        $request['outgoing_date'] = now();

        $outgoing = app(OutgoingController::class)->store($request);

        $this->payreqStatusUpdate($payreq, $outgoing);

        // create transaksi
        app(TransaksiController::class)->store('outgoing', $outgoing);

        // update app_balance in account table
        app(AccountController::class)->outgoing($payreq->amount);

        return redirect()->route('cashier.approveds.index')->with('success', 'Payreq successfully paid with FULL Payment');
    }

    public function pay($id)
    {
        $payreq = Payreq::findOrfail($id);
        $outgoings = Outgoing::where('payreq_id', $id)->get();
        $cashier = auth()->user();
        $accounts = Account::where('type', 'cash')
            ->where('project', $cashier->project)
            ->get();

        $available_amount = $payreq->amount - $outgoings->sum('amount');

        return view('cashier.approved.split', compact(['payreq', 'outgoings', 'accounts', 'available_amount']));
    }

    public function store_pay(Request $request, $id)
    {
        $this->validate($request, [
            // amount must be more than 0
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date',
        ]);

        $payreq = Payreq::findOrfail($id);

        // check if amount is more than available amount
        $outgoings = Outgoing::where('payreq_id', $id)->get();
        $available_amount = $payreq->amount - $outgoings->sum('amount');
        if ($request->amount > $available_amount) {
            return redirect()->route('cashier.approveds.pay', $id)->with('error', 'Pembayaran tidak boleh melebihi jumlah yang tersisa!');
        }

        // create new outgoing record
        $outgoing = new Outgoing();
        $outgoing->payreq_id = $payreq->id;
        $outgoing->amount = $request->amount;
        $outgoing->cashier_id = auth()->user()->id;
        $outgoing->account_id = $request->account_id;
        $outgoing->project = auth()->user()->project;
        $outgoing->outgoing_date = $request->date;
        $outgoing->save();

        // create transaksi
        app(TransaksiController::class)->store('outgoing', $outgoing);

        // update app_balance in account table
        $response = app(AccountController::class)->outgoing($request->amount);

        if (!$response) {
            return redirect()->route('cashier.approveds.pay', $id)->with('error', 'Account not found!');
        }

        $outgoings = Outgoing::where('payreq_id', $id)->get();

        // update payreq status
        if ($payreq->amount == $outgoings->sum('amount')) {

            $this->payreqStatusUpdate($payreq, $outgoing);

            return redirect()->route('cashier.approveds.pay', $id)->with('success', 'Payreq successfully paid in full');
        } else {

            $payreq->status = 'split';
            $payreq->printable = 0;
            $payreq->save();

            return redirect()->route('cashier.approveds.pay', $id)->with('success', 'Payreq successfully paid splitted');
        }
    }

    public function data()
    {
        $roles = app(ToolController::class)->getUserRoles();

        $status_includes = ['approved', 'split'];

        if (array_intersect(['superadmin', 'admin'], $roles)) {
            $approveds = Payreq::whereIn('status', $status_includes)
                ->orderBy('approved_at', 'asc')
                ->get();
        } elseif (in_array('cashier', $roles)) {
            $project_includes = ['000H', 'APS'];
            $approveds = Payreq::whereIn('status', $status_includes)
                ->whereIn('project', $project_includes)
                ->orderBy('approved_at', 'asc')
                ->get();
        } else {
            $approveds = Payreq::whereIn('status', $status_includes)
                ->where('project', auth()->user()->project)
                ->orderBy('approved_at', 'asc')
                ->get();
        }

        return datatables()->of($approveds)
            ->addColumn('requestor', function ($approved) {
                return $approved->requestor->name;
            })
            ->editColumn('type', function ($approved) {
                return ucfirst($approved->type);
            })
            ->editColumn('nomor', function ($approved) {
                return '<a href="#" style="color: black" title="' . $approved->remarks . '">' . $approved->nomor . '</a>';
            })
            ->editColumn('approved_at', function ($approved) {
                $approved_date = new \Carbon\Carbon($approved->approved_at);
                return $approved_date->addHours(8)->format('d-M-Y H:i:s');
            })
            ->addColumn('days', function ($approved) {
                $approved_date = new \Carbon\Carbon($approved->approved_at);
                return $approved_date->addHours(8)->diffInDays(now());
            })
            ->editColumn('amount', function ($approved) {
                if ($approved->status == 'split') {
                    $outgoings = Outgoing::where('payreq_id', $approved->id)->get();
                    $original_amount = $approved->amount;
                    $applied_amount = $outgoings->sum('amount');
                    $amount_due = $approved->amount - $outgoings->sum('amount');
                    return '<span class="badge badge-warning">split</span><small>Original: ' . number_format($original_amount, 2) .
                        '</small><br><small>applied: ' . number_format($applied_amount, 2)  . '</small><br>' . '<small>Due: ' . number_format($amount_due, 2) . '</small>';
                }
                return number_format($approved->amount, 2);
            })
            ->addIndexColumn()
            ->addColumn('action', 'cashier.approved.action')
            ->rawColumns(['action', 'amount', 'nomor'])
            ->toJson();
    }

    public function payreqStatusUpdate($payreq, $outgoing)
    {
        if ($payreq->type === 'advance') { // if payreq type is 'advance'
            // update payreq status
            $payreq->status = 'paid';
            $payreq->due_date = Carbon::parse($outgoing->outgoing_date)->addDays(7);
            $payreq->printable = 0;
            $payreq->save();
        } elseif ($payreq->type === 'reimburse') { // if payreq type is 'reimburse'
            $payreq->status = 'close';
            $payreq->printable = 0;
            $payreq->deletable = 0;
            $payreq->save();
            // update realiztion status
            $realization = $payreq->realization;
            $realization->status = 'reimburse-paid';
            $realization->save();
        } else { // if payreq type is 'other'
            // update payreq status
            $payreq->status = 'close';
            $payreq->printable = 0;
            $payreq->deletable = 0;
            $payreq->save();
        }
    }
}
