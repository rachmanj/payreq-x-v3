<?php

namespace App\Http\Controllers;

use App\Models\ApprovalPlan;
use App\Models\Payreq;
use Illuminate\Http\Request;

class UserPayreqController extends Controller
{
    public function index()
    {
        return view('user-payreqs.index');
    }

    public function update(Request $request, $id)
    {
        $payreq = Payreq::findOrFail($id);
        $payreq->update($request->all());

        return redirect()->route('user-payreqs.index')->with('success', 'Payment Request updated');
    }

    public function show($id)
    {
        $payreq = Payreq::findOrFail($id);

        // update is_read to 1
        ApprovalPlan::where('payreq_id', $payreq->id)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        $aproval_plans = ApprovalPlan::where('payreq_id', $payreq->id)
            ->get();

        $approval_plan_status = app(ApprovalPlanController::class)->approvalStatus();

        return view('user-payreqs.show', compact('payreq', 'approval_plan_status'));
    }

    public function print($id)
    {
        $payreq = Payreq::findOrFail($id);

        $terbilang = app(ToolController::class)->terbilang($payreq->amount);

        return view('user-payreqs.advance.print_pdf', compact('payreq', 'terbilang'));
    }

    public function destroy($id)
    {
        $payreq = Payreq::findOrFail($id);
        $payreq->delete();

        return redirect()->route('user-payreqs.index')->with('success', 'Payment Request deleted');
    }

    public function data()
    {
        // get user's roles
        $userRoles = app(UserController::class)->getUserRoles();

        // payreq with status in array as follows
        $status_include = ['draft', 'submitted', 'approved'];

        if (in_array('superadmin', $userRoles) || in_array('admin', $userRoles)) {
            $payreqs = Payreq::whereIn('status', $status_include)
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $payreqs = Payreq::where('user_id', auth()->user()->id)
                ->whereIn('status', $status_include)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return datatables()->of($payreqs)
            ->editColumn('payreq_no', function ($payreq) {
                $notif_count = ApprovalPlan::where('payreq_id', $payreq->id)
                    ->where('is_read', 0)
                    ->count();

                $notif = '';
                if ($notif_count > 0) {
                    $notif = '<span class="badge badge-info">' . $notif_count . '</span>';
                }
                return '<a href="' . route('user-payreqs.show', $payreq->id) . '">' . $payreq->payreq_no . '</a>' . $notif;
            })
            ->editColumn('type', function ($payreq) {
                return ucfirst($payreq->type);
            })
            ->editColumn('status', function ($payreq) {
                if ($payreq->status === 'submitted') {
                    return 'Waiting Approval';
                } else {
                    return ucfirst($payreq->status);
                }
            })
            ->editColumn('amount', function ($payreq) {
                return number_format($payreq->amount, 2);
            })
            ->editColumn('created_at', function ($payreq) {
                return $payreq->created_at->addHours(8)->format('d-M-Y H:i:s') . ' wita';
            })
            ->addColumn('action', 'user-payreqs.action')
            ->rawColumns(['action', 'payreq_no'])
            ->addIndexColumn()
            ->toJson();
    }
}
