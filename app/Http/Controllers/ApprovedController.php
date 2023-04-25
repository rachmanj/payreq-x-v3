<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\AdvanceCategory;
use App\Models\Payreq;
use App\Models\Rab;
use App\Models\User;
use Illuminate\Http\Request;

class ApprovedController extends Controller
{
    public function index()
    {
        return view('approved.index');
    }

    public function create()
    {
        $employees = User::where('is_active', 1)->orderBy('name', 'asc')->get();
        $rabs = Rab::where('status', 'progress')->orderBy('rab_no', 'asc')->get();
        $adv_categories = AdvanceCategory::orderBy('code', 'asc')->get();

        return view('approved.create', compact('employees', 'rabs', 'adv_categories'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'employee_id' => 'required',
            'payreq_num' => 'required|unique:payreqs',
            'payreq_idr' => 'required',
        ]);

        if ($request->approve_date) {
            $approve_date = $request->approve_date;
        } else {
            $approve_date = date('Y-m-d');
        }

        $payreq = new Payreq();
        $payreq->user_id = $request->employee_id;
        $payreq->payreq_num = $request->payreq_num;
        $payreq->approve_date = $approve_date;
        $payreq->payreq_type = $request->payreq_type;
        $payreq->que_group = $request->que_group;
        $payreq->payreq_idr = $request->payreq_idr;
        $payreq->advance_category_id = $request->advance_category_id;
        $request->rab_id ? $payreq->rab_id = $request->rab_id : $payreq->rab_id = null;
        $payreq->remarks = $request->remarks;
        $payreq->created_by = auth()->user()->username;
        $payreq->budgeted = $request->budgeted;
        $payreq->save();

        // SAVE ACTIVITY
        $activityCtrl = app(ActivityController::class);
        $activityCtrl->store(auth()->user()->id, 'Approve PR', $request->payreq_num);

        return redirect()->route('approved.index')->with('success', 'Payment Request created');
    }

    public function show($id)
    {
        $payreq = Payreq::findOrFail($id);

        return view('approved.show', compact('payreq'));
    }

    public function edit($id)
    {
        $payreq = Payreq::findOrFail($id);
        $employees = User::where('is_active', 1)->orderBy('name', 'asc')->get();
        $rabs = Rab::where('status', 'progress')->orderBy('rab_no', 'asc')->get();
        $adv_categories = AdvanceCategory::orderBy('code', 'asc')->get();

        return view('approved.edit', compact('payreq', 'employees', 'rabs', 'adv_categories'));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'employee_id' => 'required',
            'payreq_num' => 'required|unique:payreqs,payreq_num,' . $id,
            'approve_date' => 'required',
            'payreq_type' => 'required',
            'payreq_idr' => 'required',
        ]);

        $payreq = Payreq::findOrFail($id);
        $payreq->user_id = $request->employee_id;
        $payreq->payreq_num = $request->payreq_num;
        $payreq->approve_date = $request->approve_date;
        $payreq->payreq_type = $request->payreq_type;
        $payreq->que_group = $request->que_group;
        $payreq->payreq_idr = $request->payreq_idr;
        $payreq->advance_category_id = $request->advance_category_id;
        $payreq->remarks = $request->remarks;
        $request->rab_id ? $payreq->rab_id = $request->rab_id : $payreq->rab_id = null;
        $payreq->updated_by = auth()->user()->username;
        $payreq->budgeted = $request->budgeted;
        $payreq->save();

        return redirect()->route('approved.index')->with('success', 'Payment Request updated');
    }

    public function destroy($id)
    {
        $payreq = Payreq::findOrFail($id);
        $payreq->delete();

        return redirect()->route('approved.index')->with('success', 'Payment Request deleted');
    }

    public function all()
    {
        return view('approved.all');
    }

    public function data()
    {
        $payreqs = Payreq::select('id', 'payreq_num', 'user_id', 'approve_date', 'payreq_type', 'payreq_idr', 'outgoing_date', 'rab_id')
            ->selectRaw('datediff(now(), approve_date) as days')
            ->whereNull('outgoing_date')
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
            ->addColumn('action', 'approved.action')
            ->rawColumns(['action', 'payreq_num'])
            ->toJson();
    }

    public function all_data()
    {
        $payreqs = Payreq::select(
            'id',
            'payreq_num',
            'user_id',
            'approve_date',
            'payreq_idr',
            'outgoing_date',
            'realization_num',
            'realization_amount',
            'realization_date',
            'verify_date',
        )
            // ->selectRaw('datediff(now(), realization_date) as days')
            ->orderBy('approve_date', 'desc')
            ->get();

        return datatables()->of($payreqs)
            ->editColumn('approve_date', function ($payreq) {
                return date('d-m-Y', strtotime($payreq->approve_date));
            })
            ->editColumn('outgoing_date', function ($payreq) {
                if ($payreq->outgoing_date) {
                    return date('d-m-Y', strtotime($payreq->outgoing_date));
                } else {
                    return '-';
                }
            })
            ->editColumn('realization_date', function ($payreq) {
                if ($payreq->realization_date) {
                    return date('d-m-Y', strtotime($payreq->realization_date));
                } else {
                    return '-';
                }
            })
            ->editColumn('verify_date', function ($payreq) {
                if ($payreq->verify_date) {
                    return date('d-m-Y', strtotime($payreq->verify_date));
                } else {
                    return '-';
                }
            })
            ->editColumn('payreq_idr', function ($payreq) {
                return number_format($payreq->payreq_idr, 0);
            })
            ->editColumn('realization_amount', function ($payreq) {
                if ($payreq->realization_amount == null) return '-';
                return number_format($payreq->realization_amount, 0);
            })
            ->addColumn('employee', function ($payreq) {
                return $payreq->employee->name;
            })
            ->addIndexColumn()
            ->addColumn('action', 'approved.action_all')
            ->rawColumns(['action'])
            ->toJson();
    }
}
