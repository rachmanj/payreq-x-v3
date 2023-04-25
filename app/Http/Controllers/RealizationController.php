<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Payreq;
use Illuminate\Http\Request;

class RealizationController extends Controller
{
    public function index()
    {
        return view('realization.index');
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'realization_num' => 'required|unique:payreqs',
        ]);

        $payreq = Payreq::findOrFail($id);

        if ($request->realization_date) {
            $realization_date = $request->realization_date;
        } else {
            $realization_date = date('Y-m-d');
        }

        $payreq->realization_num = $request->realization_num;
        $payreq->realization_amount = $request->realization_amount;
        $payreq->realization_date = $realization_date;
        $payreq->save();

        // SAVE ACTIVITY
        $activityCtrl = app(ActivityController::class);
        $activityCtrl->store(auth()->user()->id, 'Realization PR', $payreq->payreq_num);

        return redirect()->route('realization.index')->with('success', 'Payment Request updated');
    }

    public function data()
    {
        $payreqs = Payreq::select(
            'id',
            'payreq_num',
            'user_id',
            'approve_date',
            'payreq_type',
            'payreq_idr',
            'outgoing_date',
            'rab_id',
        )
            ->selectRaw('datediff(now(), outgoing_date) as days')
            ->where('payreq_type', 'Advance')
            ->whereNotNull('outgoing_date')
            ->whereNull('realization_date')
            ->orderBy('outgoing_date', 'asc')
            ->get();

        return datatables()->of($payreqs)
            ->editColumn('payreq_num', function ($payreq) {
                if ($payreq->buc_id) {
                    return $payreq->payreq_num . ' ' . '<i class="fas fa-check"></i>';
                }
                return $payreq->payreq_num;
            })
            ->editColumn('approve_date', function ($payreq) {
                return date('d-m-Y', strtotime($payreq->approve_date));
            })
            ->editColumn('outgoing_date', function ($payreq) {
                return date('d-m-Y', strtotime($payreq->outgoing_date));
            })
            ->editColumn('payreq_idr', function ($payreq) {
                return number_format($payreq->payreq_idr, 0);
            })
            ->addColumn('employee', function ($payreq) {
                return $payreq->employee->name;
            })
            ->addIndexColumn()
            ->addColumn('action', 'realization.action')
            ->rawColumns(['action', 'payreq_num'])
            ->toJson();
    }
}
