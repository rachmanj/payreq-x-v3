<?php

namespace App\Http\Controllers;

use App\Models\Payreq;
use App\Models\Rab;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AccountingPayreqController extends Controller
{
    public function index()
    {
        return view('accounting.payreqs.index');
    }

    public function create()
    {
        $employees = User::orderBy('name')->get();
        $rabs = Rab::where('status', 'progress')->orderBy('rab_no', 'asc')->get();
        $payreq_no = app(PayreqController::class)->generateDraftNumber();

        return view('accounting.payreqs.create', compact([
            'employees',
            'rabs',
            'payreq_no',
        ]));
    }

    public function store(Request $request)
    {
        $request['project'] = User::find($request->employee_id)->project;
        $request['department_id'] = User::find($request->employee_id)->department_id;
        // return $request;
        // die;

        $response = app(PayreqController::class)->store($request);

        if ($response->status == 'draft') {
            return redirect()->route('user-payreqs.index')->with('success', 'Payreq Advance Draft saved');
        } else {
            $approval_plan_response = app(ApprovalPlanController::class)->create_approval_plan('payreq', $response->id);

            if ($approval_plan_response == false) {
                // update payreq status to draft
                $payreq = Payreq::findOrFail($response->id);
                $payreq->update([
                    'status' => 'draft',
                    'editable' => '1',
                    'deletable' => '1',
                ]);
                return redirect()->route('accounting.payreqs.index')->with('error', 'No Approval Plan found. Payreq Advance saved as draft, contact IT Department');
            }

            return redirect()->route('accounting.payreqs.index')->with('success', 'Payreq Advance submitted');
        }
    }

    public function show($payreq_id)
    {
        $payreq = Payreq::with(['realization', 'realization.realizationDetails'])->where('id', $payreq_id)->first();

        return view('accounting.payreqs.show', compact('payreq'));
    }


    public function data()
    {
        $userRoles = app(UserController::class)->getUserRoles();

        if (in_array(['superadmin', 'admin'], $userRoles)) {
            $payreqs = Payreq::orderBy('created_at', 'desc')
                ->get();
        } elseif (in_array(['cashier'], $userRoles)) {
            $projects_include = ['000H', 'APS'];
            $payreqs = Payreq::whereIn('project', $projects_include)
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $payreqs = Payreq::where('project', auth()->user()->project)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return datatables()->of($payreqs)
            ->addColumn(('employee'), function ($payreq) {
                return $payreq->requestor->name;
            })
            ->editColumn('amount', function ($payreq) {
                return number_format($payreq->amount, 2);
            })
            ->editColumn(('created_at'), function ($payreq) {
                return Carbon::parse($payreq->created_at)->format('d-M-Y');
            })
            ->addColumn('realization_no', function ($payreq) {
                if ($payreq->realization) {
                    return $payreq->realization->nomor;
                } else {
                    return 'n/a';
                }
            })
            ->addIndexColumn()
            ->addColumn('action', 'accounting.payreqs.action')
            ->rawColumns(['action'])
            ->toJson();
    }
}
