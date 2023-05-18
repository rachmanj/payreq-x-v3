<?php

namespace App\Http\Controllers;

use App\Models\Payreq;
use Illuminate\Http\Request;

class MyPayreqController extends Controller
{
    public function index()
    {
        return view('mypayreqs.index');
    }



    public function update(Request $request, $id)
    {
        $payreq = Payreq::findOrFail($id);
        $payreq->update($request->all());

        return redirect()->route('mypayreqs.index')->with('success', 'Payment Request updated');
    }

    public function destroy($id)
    {
        $payreq = Payreq::findOrFail($id);
        $payreq->delete();

        return redirect()->route('mypayreqs.index')->with('success', 'Payment Request deleted');
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
            ->editColumn('payreq_no', function ($payreqs) {
                return '<a href="' . route('mypayreqs.show', $payreqs->id) . '">' . $payreqs->payreq_no . '</a>';
            })
            ->editColumn('amount', function ($payreqs) {
                return number_format($payreqs->amount, 2);
            })
            ->editColumn('created_at', function ($payreqs) {
                return $payreqs->created_at->addHours(8)->format('d-M-Y H:i:s');
            })
            ->addColumn('action', 'mypayreqs.action')
            ->rawColumns(['action', 'payreq_no'])
            ->addIndexColumn()
            ->toJson();
    }
}
