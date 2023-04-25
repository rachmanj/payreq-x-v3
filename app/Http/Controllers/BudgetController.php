<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Payreq;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function index()
    {
        return view('budget.index');
    }

    public function update(Request $request, $id)
    {
        $payreq = Payreq::findOrfail($id);
        if ($request->periode_ofr) {
            $periode_ofr = $request->periode_ofr . '-01';
        } else {
            $periode_ofr = date('Y-m-d');
        }

        $payreq->budgeted = 1;
        $payreq->periode_ofr = $periode_ofr;
        $payreq->save();

        // SAVE ACTIVITY
        $activityCtrl = app(ActivityController::class);
        $activityCtrl->store(auth()->user()->id, 'Budget', $payreq->payreq_num);

        return redirect()->route('budget.index')->with('success', 'Payreq successfully updated');
    }

    public function just_updated()
    {
        return view('budget.just_updated');
    }

    public function data()
    {
        $payreqs = Payreq::select('id', 'payreq_num', 'user_id', 'approve_date', 'payreq_type', 'payreq_idr', 'outgoing_date', 'rab_id', 'budgeted', 'remarks')
            ->where('budgeted', 0)
            ->where('periode_ofr', null)
            ->orderBy('approve_date', 'desc')
            ->get();

        return datatables()->of($payreqs)
            ->editColumn('payreq_num', function ($payreq) {
                if ($payreq->rab_id) {
                    return $payreq->payreq_num . ' ' . '<i class="fas fa-check"></i>';
                }
                return $payreq->payreq_num;
            })
            ->editColumn('approve_date', function ($payreq) {
                return date('d-m-Y', strtotime($payreq->approve_date));
            })
            ->editColumn('payreq_idr', function ($payreq) {
                return number_format($payreq->payreq_idr, 0);
            })
            ->addColumn('employee', function ($payreq) {
                return $payreq->employee->name;
            })
            ->addIndexColumn()
            ->addColumn('action', 'budget.action')
            ->rawColumns(['action'])
            ->toJson();
    }

    public function just_updated_data()
    {
        $payreqs = Payreq::select('id', 'payreq_num', 'user_id', 'approve_date', 'payreq_type', 'payreq_idr', 'outgoing_date', 'rab_id', 'budgeted', 'remarks', 'periode_ofr')
            ->where('budgeted', 1)
            ->where('periode_ofr', '<>', null)
            ->orderBy('approve_date', 'desc')
            ->get();

        return datatables()->of($payreqs)
            ->editColumn('payreq_num', function ($payreq) {
                if ($payreq->rab_id) {
                    return $payreq->payreq_num . ' ' . '<i class="fas fa-check"></i>';
                }
                return $payreq->payreq_num;
            })
            ->editColumn('approve_date', function ($payreq) {
                return date('d-m-Y', strtotime($payreq->approve_date));
            })
            ->editColumn('periode_ofr', function ($payreq) {
                return date('M Y', strtotime($payreq->periode_ofr));
            })
            ->editColumn('payreq_idr', function ($payreq) {
                return number_format($payreq->payreq_idr, 0);
            })
            ->addColumn('employee', function ($payreq) {
                return $payreq->employee->name;
            })
            ->addIndexColumn()
            // ->addColumn('action', 'budget.action')
            // ->rawColumns(['action'])
            ->toJson();
    }
}
