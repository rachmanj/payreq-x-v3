<?php

namespace App\Http\Controllers;

use App\Models\Payreq;
use Illuminate\Http\Request;

class OngoingPayreqController extends Controller
{
    public function index()
    {
        return view('ongoings.index');
    }

    public function create_advance()
    {
        $payreq_no = app(PayreqController::class)->generateDraftNumber();

        return view('ongoings.create', compact('payreq_no'));
    }

    public function store_advance(Request $request)
    {
        $payreq = app(PayreqController::class)->store($request);
        $payreq->update([
            'type' => 'advance',
        ]);

        return redirect()->route('ongoings.index')->with('success', 'Payreq Advance Draft submitted');
    }

    public function destroy($id)
    {
        $payreq = Payreq::findOrFail($id);
        $payreq->delete();

        return redirect()->route('ongoings.index')->with('success', 'Payment Request deleted');
    }

    public function data()
    {
        // get user's roles
        $userRoles = app(UserController::class)->getUserRoles();

        if (in_array('superadmin', $userRoles) || in_array('admin', $userRoles)) {
            $payreqs = Payreq::where('status', '!=', 'close')->orderBy('created_at', 'desc')
                ->get();
        } else {
            $payreqs = Payreq::where('user_id', auth()->user()->id)
                ->where('status', '!=', 'close')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return datatables()->of($payreqs)
            ->editColumn('amount', function ($payreqs) {
                return number_format($payreqs->amount, 2);
            })
            ->editColumn('created_at', function ($payreqs) {
                return $payreqs->created_at->addHours(8)->format('d-M-Y H:i:s');
            })
            ->addColumn('action', 'ongoings.action')
            ->rawColumns(['action'])
            ->addIndexColumn()
            ->toJson();
    }
}
