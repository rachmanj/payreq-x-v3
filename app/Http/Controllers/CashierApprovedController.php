<?php

namespace App\Http\Controllers;

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
        $account = Account::where('type_id', 2)
            ->where('project', $cashier->project)
            ->first();

        $request = new Request();
        $request['payreq_id'] = $id;
        $request['amount'] = $payreq->amount;
        $request['cashier_id'] = $cashier->id;
        $request['account_id'] = $account->id;
        $request['project'] = $cashier->project;
        $request['outgoing_date'] = now();

        $outgoing = app(OutgoingController::class)->store($request);

        // update payreq status
        $payreq->status = 'paid';
        $payreq->due_date = Carbon::parse($outgoing->outgoing_date)->addDays(7);
        $payreq->printable = 0;
        $payreq->save();

        return redirect()->route('cashier.approveds.index')->with('success', 'Payreq successfully paid with FULL Payment');
    }

    public function pay($id)
    {
        $payreq = Payreq::findOrfail($id);
        $outgoings = Outgoing::where('payreq_id', $id)->get();
        $cashier = auth()->user();
        $accounts = Account::where('type_id', 2)
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

        // create new outgoing record
        $outgoing = new Outgoing();
        $outgoing->payreq_id = $payreq->id;
        $outgoing->amount = $request->amount;
        $outgoing->cashier_id = auth()->user()->id;
        $outgoing->account_id = $request->account_id;
        $outgoing->project = auth()->user()->project;
        $outgoing->outgoing_date = $request->date;

        $outgoing->save();

        $outgoings = Outgoing::where('payreq_id', $id)->get();

        // update payreq status
        if ($payreq->amount == $outgoings->sum('amount')) {
            $payreq->status = 'paid';
            $payreq->due_date = Carbon::parse($outgoing->outgoing_date)->addDays(7);
            $payreq->printable = 0;
            $payreq->save();
        } else {
            $payreq->status = 'split';
            $payreq->printable = 0;
            $payreq->save();
        }

        return redirect()->route('cashier.approveds.pay', $id)->with('success', 'Payreq successfully paid splitted');
    }

    public function data()
    {
        $roles = app(ToolController::class)->getUserRoles();

        if (in_array('superadmin', $roles) || in_array('admin', $roles)) {
            $approveds = Payreq::where('status', 'approved')->orWhere('status', 'split')
                ->orderBy('approved_at', 'desc')
                ->get();
        } else {
            $approveds = Payreq::where('status', 'approved')->orWhere('status', 'split')
                ->where('project', auth()->user()->project)
                ->orderBy('approved_at', 'desc')
                ->get();
        }

        return datatables()->of($approveds)
            ->addColumn('requestor', function ($approved) {
                return $approved->requestor->name;
            })
            ->editColumn('type', function ($approved) {
                return ucfirst($approved->type);
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
                    $amount = $approved->amount - $outgoings->sum('amount');
                    return '<span class="badge badge-warning">split</span>' . ' ' . number_format($amount, 2);
                }
                return number_format($approved->amount, 2);
            })
            ->addIndexColumn()
            ->addColumn('action', 'cashier.approved.action')
            ->rawColumns(['action', 'amount'])
            ->toJson();
    }
}
