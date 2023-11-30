<?php

namespace App\Http\Controllers;

use App\Models\Incoming;
use Illuminate\Http\Request;

class CashierIncomingController extends Controller
{
    public function index()
    {
        return view('cashier.incomings.index');
    }

    public function receive(Request $request)
    {
        // update incomings table
        $incoming = Incoming::findOrFail($request->incoming_id);
        $incoming->receive_date = $request->receive_date;
        $incoming->cashier_id = auth()->user()->id;
        $incoming->save();

        // update app_balance in accounts table
        app(AccountController::class)->incoming($incoming->amount);

        return redirect()->back()->with('success', 'Incoming has been received');
    }

    public function create()
    {
        return view('cashier.incomings.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required',
            'amount' => 'required',
        ]);

        $incoming = new Incoming();
        $incoming->cashier_id = auth()->user()->id;
        $incoming->description = $request->description;
        $incoming->amount = $request->amount;
        $incoming->project = auth()->user()->project;
        $incoming->save();

        return redirect()->route('cashier.incomings.index')->with('success', 'Incoming has been created');
    }

    public function data()
    {
        $roles = app(ToolController::class)->getUserRoles();

        if (in_array('superadmin', $roles) || in_array('admin', $roles)) {
            $incomings = Incoming::orderBy('created_at', 'desc')
                ->get();
        } else {
            $incomings = Incoming::where('project', auth()->user()->project)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return datatables()->of($incomings)
            ->addColumn('employee', function ($incoming) {
                if ($incoming->realization_id !== null) {
                    return $incoming->realization->requestor->name;
                } else {
                    return $incoming->cashier->name;
                }
            })
            ->addColumn('realization_no', function ($incoming) {
                if ($incoming->realization_id !== null) {
                    return $incoming->realization->nomor;
                } else {
                    return $incoming->description;
                }
            })
            ->editColumn('receive_date', function ($incoming) {
                return $incoming->receive_date ? date('d-M-Y', strtotime($incoming->receive_date)) : '-';
            })

            ->editColumn('amount', function ($incoming) {
                return number_format($incoming->amount, 2);
            })
            ->addColumn('account', function ($incoming) {
                return $incoming->account_id ? $incoming->account->account_number . ' - ' . $incoming->account->account_name : '-';
            })
            ->addColumn('status', function ($incoming) {
                if ($incoming->receive_date == null) {
                    return '<span class="badge badge-danger">NOT RECEIVE</span>';
                } else {
                    return '<span class="badge badge-success">RECEIVED</span>';
                }
            })
            ->addIndexColumn()
            ->addColumn('action', 'cashier.incomings.action')
            ->rawColumns(['action', 'status'])
            ->toJson();
    }
}
