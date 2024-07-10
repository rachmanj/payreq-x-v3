<?php

namespace App\Http\Controllers;

use App\Models\Outgoing;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CashierOutgoingController extends Controller
{
    public function index()
    {
        return view('cashier.outgoings.index');
    }

    public function create()
    {
        return view('cashier.outgoings.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required',
            'amount' => 'required',
        ]);

        $outgoing = new Outgoing();
        $outgoing->cashier_id = auth()->user()->id;
        $outgoing->description = $request->description;
        $outgoing->amount = $request->amount;
        $outgoing->project = auth()->user()->project;
        if ($request->has('will_post')) {
            $outgoing->will_post = 0;
        }
        $outgoing->save();

        return redirect()->route('cashier.outgoings.index')->with('success', 'Outgoing has been created');
    }

    public function payment(Request $request)
    {
        // update incomings table
        $outgoing = Outgoing::findOrFail($request->incoming_id);
        $outgoing->outgoing_date = $request->receive_date;
        $outgoing->save();

        // update app_balance in accounts table
        app(AccountController::class)->outgoing_manual($outgoing->amount);

        return redirect()->route('cashier.outgoings.index')->with('success', 'Payment has been created');
    }

    public function data()
    {
        $roles = app(ToolController::class)->getUserRoles();
        // limit date is 5 months ago
        $limit_date = Carbon::now()->subMonths(5)->format('Y-m-d');

        if (array_intersect(['superadmin', 'admin'], $roles)) {
            $outgoings = Outgoing::orderBy('outgoing_date', 'desc')
                ->get();
        } else {
            $outgoings = Outgoing::where('cashier_id', auth()->user()->id)
                ->where('created_at', '>=', $limit_date)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return datatables()->of($outgoings)
            ->addColumn('employee', function ($outgoing) {
                if ($outgoing->payreq_id !== null) {
                    return $outgoing->payreq->requestor->name;
                } else {
                    return $outgoing->cashier->name;
                }
            })
            ->addColumn('payreq_no', function ($outgoing) {
                return $outgoing->payreq->nomor;
            })
            ->editColumn('outgoing_date', function ($outgoing) {
                $outgoing_date = new \Carbon\Carbon($outgoing->outgoing_date);
                return $outgoing_date->addHours(8)->format('d-M-Y');
            })
            ->editColumn('amount', function ($outgoing) {
                return number_format($outgoing->amount, 2);
            })
            ->addColumn('cashier', function ($outgoing) {
                return $outgoing->cashier->name;
            })
            ->addColumn('account', function ($outgoing) {
                // if account id not null
                if ($outgoing->account_id) {
                    return $outgoing->account->account_number . ' - ' . $outgoing->account->account_name;
                } else {
                    return $outgoing->description;
                }
            })
            ->addIndexColumn()
            ->addColumn('action', 'cashier.outgoings.action')
            ->rawColumns(['action', 'amount'])
            ->toJson();
    }
}
