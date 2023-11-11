<?php

namespace App\Http\Controllers;

use App\Models\ApprovalPlan;
use App\Models\Outgoing;
use App\Models\Payreq;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UserPayreqController extends Controller
{
    public function index()
    {
        $over_due_payreq = Payreq::where('user_id', auth()->user()->id)
            ->where('status', 'paid')
            ->where('due_date', '<', now())
            ->count();

        if ($over_due_payreq > 0) {
            $enable_payreq = false;
        } else {
            $enable_payreq = true;
        }

        return view('user-payreqs.index', compact('enable_payreq'));
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
        ApprovalPlan::where('document_id', $payreq->id)
            ->where('document_type', 'payreq')
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        $approval_plans = ApprovalPlan::where('document_id', $payreq->id)
            ->where('document_type', 'payreq')
            ->get();

        $approval_plan_status = app(ApprovalPlanController::class)->approvalStatus();

        if ($payreq->submit_at) {
            $date = new \Carbon\Carbon($payreq->submit_at);
            $submit_at = $date->addHours(8)->format('d-M-Y H:i:s') . ' wita';
        } else {
            $submit_at = '';
        }

        if ($payreq->due_date) {
            $date = new \Carbon\Carbon($payreq->due_date);
            $due_date = $date->format('d-M-Y');
        } else {
            $due_date = '';
        }

        if ($payreq->status == 'paid') {
            $paid_date = app(ToolController::class)->getPaidDate($payreq->id);
            $paid_date = new \Carbon\Carbon($paid_date);
            $paid_date = " at " .  $paid_date->format('d-M-Y');
        } else {
            $paid_date = '';
        }

        return view('user-payreqs.show', compact([
            'payreq',
            'approval_plan_status',
            'approval_plans',
            'submit_at',
            'due_date',
            'paid_date',
        ]));
    }

    public function cancel(Request $request)
    {
        $id = $request->payreq_id;
        app(PayreqController::class)->cancel($id);

        return redirect()->route('user-payreqs.index')->with('success', 'Payment Request cancelled');
    }

    public function print($id)
    {
        $payreq = Payreq::findOrFail($id);
        $terbilang = app(ToolController::class)->terbilang($payreq->amount);
        $approvers = app(ToolController::class)->getApproversName($id, 'payreq');

        return view('user-payreqs.advance.print_pdf', compact([
            'payreq',
            'terbilang',
            'approvers'
        ]));
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
        $status_include = ['draft', 'submitted', 'approved', 'revise', 'canceled', 'split', 'paid', 'rejected'];

        if (in_array('superadmin', $userRoles) || in_array('admin', $userRoles)) {
            $payreqs = Payreq::whereIn('status', $status_include)
                ->orderBy('approved_at', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $payreqs = Payreq::where('user_id', auth()->user()->id)
                ->whereIn('status', $status_include)
                ->orderBy('approved_at', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return datatables()->of($payreqs)
            ->editColumn('nomor', function ($payreq) {
                $notif_count = ApprovalPlan::where('document_id', $payreq->id)
                    ->where('document_type', 'payreq')
                    ->where('is_read', 0)
                    ->count();

                $notif = '';
                if ($notif_count > 0) {
                    $notif = '<span class="badge badge-info">' . $notif_count . '</span>';
                }
                return '<a href="' . route('user-payreqs.show', $payreq->id) . '">' . $payreq->nomor . '</a>' . $notif;
            })
            ->editColumn('type', function ($payreq) {
                return ucfirst($payreq->type);
            })
            ->editColumn('status', function ($payreq) {
                if ($payreq->status === 'submitted') {
                    return 'Waiting Approval';
                } elseif ($payreq->status === 'approved') {
                    $approved_date = new \Carbon\Carbon($payreq->approved_at);
                    return '<button class="btn btn-xs btn-success" style="pointer-events: none;">APPROVED at ' . $approved_date->addHours(8)->format('d-M-Y H:i') . ' wita </button>';
                } elseif ($payreq->status === 'revise') {
                    return '<span class="badge badge-warning">REVISED</span>';
                } elseif ($payreq->status === 'canceled') {
                    $cancel_date = new \Carbon\Carbon($payreq->cancelled_at);
                    return '<button class="badge badge-danger">CANCELED</button> at ' . $cancel_date->addHours(8)->format('d-M-Y H:i') . ' wita';
                } elseif ($payreq->status === 'split') {
                    $amount_paid = Outgoing::where('payreq_id', $payreq->id)->sum('amount');
                    $amount_remain = $payreq->amount - $amount_paid;
                    return '<button class="btn btn-xs btn-warning" style="pointer-events: none;">Payment SPLITTED</button>' . ' Remain amount: ' . number_format($amount_remain, 2);
                } elseif ($payreq->status === 'paid') {
                    // get difference between due_date and today
                    $due_date = new \Carbon\Carbon($payreq->due_date);
                    $today = new \Carbon\Carbon();
                    $dif_days = $due_date->diffInDays($today);
                    if ($dif_days > 6) {
                        return '<button class="btn btn-xs btn-outline-info" style="pointer-events: none;"><b>PAID</b></button><button class="btn btn-xs btn-danger mx-2" style="pointer-events: none;">OVER DUE <b>' . $dif_days . '</b> days</button>';
                    }
                    return '<button class="btn btn-xs btn-outline-info" style="pointer-events: none;"><b>PAID</b></button> and due in<b> ' . $dif_days . ' </b> days';
                } else {
                    return ucfirst($payreq->status);
                }
            })
            ->editColumn('amount', function ($payreq) {
                return number_format($payreq->amount, 2);
            })
            ->editColumn('submit_at', function ($payreq) {
                if ($payreq->status == 'draft') {
                    return "Created at " . $payreq->created_at->addHours(8)->format('d-M-Y H:i') . ' wita';
                }
                if ($payreq->status == 'paid') {
                    $paid_date = App(ToolController::class)->getPaidDate($payreq->id);
                    $paid_date = new \Carbon\Carbon($paid_date);
                    return 'Paid at ' . $paid_date->format('d-M-Y');
                }
                $submit_date = new \Carbon\Carbon($payreq->submit_at);
                return 'Submit at ' . $submit_date->addHours(8)->format('d-M-Y H:i') . ' wita';
            })
            ->addColumn('action', 'user-payreqs.action')
            ->rawColumns(['action', 'nomor', 'status'])
            ->addIndexColumn()
            ->toJson();
    }
}
