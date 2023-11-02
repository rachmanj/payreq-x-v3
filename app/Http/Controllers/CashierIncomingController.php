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
        $incoming = Incoming::findOrFail($request->outgoing_id);
        $incoming->receive_date = $request->receive_date;
        $incoming->cashier_id = auth()->user()->id;
        $incoming->save();

        return redirect()->back()->with('success', 'Incoming has been received');
    }

    public function data()
    {
        $roles = app(ToolController::class)->getUserRoles();

        if (in_array('superadmin', $roles) || in_array('admin', $roles)) {
            $incomings = Incoming::orderBy('approved_at', 'desc')
                ->get();
        } else {
            $incomings = Incoming::where('project', auth()->user()->project)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return datatables()->of($incomings)
            ->addColumn('employee', function ($incoming) {
                return $incoming->realization->requestor->name;
            })
            ->addColumn('realization_no', function ($incoming) {
                return $incoming->realization->nomor;
            })
            ->editColumn('created_date', function ($incoming) {
                $created_date = new \Carbon\Carbon($incoming->created_at);
                return $created_date->addHours(8)->format('d-M-Y');
            })
            ->editColumn('amount', function ($incoming) {
                return number_format($incoming->amount, 2);
            })
            ->addColumn('account', function ($incoming) {
                return $incoming->account_id ? $incoming->account->account_number . ' - ' . $incoming->account->account_name : '-';
            })
            ->addColumn('status', function ($incoming) {
                if ($incoming->incoming_date == null) {
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
