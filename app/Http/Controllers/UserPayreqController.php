<?php

namespace App\Http\Controllers;

use App\Models\ApprovalPlan;
use App\Models\Outgoing;
use App\Models\OverdueExtension;
use App\Models\Payreq;
use App\Models\Realization;
use App\Models\TransferAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPayreqController extends Controller
{
    public function index()
    {
        $overdue_payreqs = Payreq::where('user_id', auth()->user()->id)
            ->where('type', 'advance')
            ->where('status', 'paid')
            ->where('due_date', '<', now())
            ->count();

        $overdue_realizations = Realization::where('user_id', auth()->user()->id)
            ->where('status', 'approved')
            ->where('due_date', '<', now())
            ->count();

        $overdue_document_count = $overdue_payreqs + $overdue_realizations;

        if ($overdue_document_count > 0) {
            $enable_payreq = false;
        } else {
            $enable_payreq = true;
        }

        return view('user-payreqs.index', compact([
            'enable_payreq',
            'overdue_document_count',
            'overdue_payreqs',
            'overdue_realizations',
        ]));
    }

    public function overdueDocuments()
    {
        $userId = auth()->user()->id;

        $extensionCountRelations = [
            'overdueExtensions as overdue_extensions_total_count',
            'overdueExtensions as overdue_extensions_pending_count' => function ($query) {
                $query->where('status', OverdueExtension::STATUS_PENDING);
            },
            'overdueExtensions as overdue_extensions_approved_count' => function ($query) {
                $query->where('status', OverdueExtension::STATUS_APPROVED);
            },
        ];

        $overduePayreqs = Payreq::query()
            ->where('user_id', $userId)
            ->where('type', 'advance')
            ->where('status', 'paid')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->withCount($extensionCountRelations)
            ->orderBy('due_date')
            ->get();

        $overdueRealizations = Realization::query()
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->with('payreq')
            ->withCount($extensionCountRelations)
            ->orderBy('due_date')
            ->get();

        return view('user-payreqs.overdue-documents', compact(
            'overduePayreqs',
            'overdueRealizations'
        ));
    }

    public function update(Request $request, $id)
    {
        $payreq = Payreq::findOrFail($id);
        $payreq->update($request->all());

        return redirect()->route('user-payreqs.index')->with('success', 'Payment Request updated');
    }

    public function show($id)
    {
        $payreq = Payreq::query()
            ->with([
                'anggaranAllocations.anggaran',
                'anggaran',
                'transferAccount.bank',
                'outgoings.attachments',
            ])
            ->findOrFail($id);

        // update is_read to 1
        ApprovalPlan::where('document_id', $payreq->id)
            ->where('document_type', 'payreq')
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        $approval_plans = ApprovalPlan::where('document_id', $payreq->id)
            ->where('document_type', 'payreq')
            ->with('approver')
            ->get();

        $approval_plan_status = app(ApprovalPlanController::class)->approvalStatus();

        if ($payreq->submit_at) {
            $date = new \Carbon\Carbon($payreq->submit_at);
            $submit_at = $date->format('d-M-Y H:i:s').' wita';
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
            $cashier = $payreq->last_outgoing()->cashier->name;
            $paid_date_conv = new \Carbon\Carbon($paid_date);
            $paid_date = ' on '.$paid_date_conv->format('d-M-Y').' by '.$cashier;
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
        $payreq = Payreq::query()
            ->with([
                'requestor',
                'department',
                'anggaran',
                'anggaranAllocations.anggaran',
                'transferAccount.bank',
            ])
            ->findOrFail($id);
        $terbilang = app(ToolController::class)->terbilang($payreq->amount);
        $approvers = app(ToolController::class)->getApproversName($id, 'payreq');

        if ($payreq->type === 'reimburse') {
            $realization_details = $payreq->realization->realizationDetails;

            if ($payreq->project == '000H' || $payreq->project == 'APS') {
                return view('user-payreqs.reimburse.print_pdf', compact([
                    'payreq',
                    'terbilang',
                    'realization_details',
                    'approvers',
                ]));
            }

            return view('user-payreqs.reimburse.print_pdf', compact([
                'payreq',
                'terbilang',
                'realization_details',
                'approvers',
            ]));
        } else {
            if ($payreq->project == '022C' && $payreq->type == 'advance') {
                return view('user-payreqs.advance.print_pdf_signed_advance_022c', compact([
                    'payreq',
                    'terbilang',
                    'approvers',
                ]));
            }

            if ($payreq->project == '000H' || $payreq->project == 'APS') {
                return view('user-payreqs.advance.print_pdf_signed_advance', compact([
                    'payreq',
                    'terbilang',
                    'approvers',
                ]));
            }

            return view('user-payreqs.advance.print_pdf', compact([
                'payreq',
                'terbilang',
                'approvers',
            ]));
        }
    }

    public function destroy(Request $request, $id)
    {
        if ($request->type === 'advance') {
            $payreq = Payreq::findOrFail($id);
            $payreq->delete();
        } else {
            $payreq = Payreq::findOrFail($id);
            $realization = $payreq->realization;
            $realization_details = $realization->realizationDetails;

            // delete records
            if ($realization_details->count() > 0) {
                foreach ($realization_details as $detail) {
                    $detail->delete();
                }
            }
            $realization->delete();
            $payreq->delete();
        }

        return redirect()->route('user-payreqs.index')->with('success', 'Payment Request deleted');
    }

    public function data()
    {
        // get user's roles
        $userRoles = app(UserController::class)->getUserRoles();

        // payreq with status in array as follows
        $status_include = ['draft', 'submitted', 'approved', 'revise', 'split', 'paid', 'rejected', 'realization'];

        if (in_array('superadmin', $userRoles)) {
            $payreqs = Payreq::with(['realization', 'transferAccount.bank'])
                ->whereIn('status', $status_include)
                ->orderBy('status', 'asc')
                ->orderBy('approved_at', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $payreqs = Payreq::with(['realization', 'transferAccount.bank'])
                ->where('user_id', auth()->user()->id)
                ->whereIn('status', $status_include)
                ->orderBy('status', 'asc')
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
                    $notif = '<span class="vj-chip vj-chip-primary">'.$notif_count.'</span>';
                }

                return '<a href="'.route('user-payreqs.show', $payreq->id).'" class="font-weight-bold">'.$payreq->nomor.'</a>'.$notif;
            })
            ->editColumn('type', function ($payreq) {
                $chipClass = match ($payreq->type) {
                    'advance' => 'vj-chip-info',
                    'reimburse' => 'vj-chip-primary',
                    default => 'vj-chip-neutral',
                };

                return '<span class="vj-chip '.$chipClass.'">'.ucfirst($payreq->type).'</span>';
            })
            ->addColumn('payment_method', function ($payreq) {
                if (! in_array($payreq->type, ['advance', 'reimburse'], true)) {
                    return '-';
                }

                return $payreq->payment_method_badge;
            })
            ->editColumn('status', function ($payreq) {
                $chips = [];

                if ($payreq->status === 'submitted') {
                    $chips[] = '<span class="vj-chip vj-chip-warning">Waiting Approval</span>';
                } elseif ($payreq->status === 'approved') {
                    $approved_date = \Carbon\Carbon::parse($payreq->approved_at);
                    $chips[] = '<span class="vj-chip vj-chip-success">APPROVED at '.$approved_date->format('d-M-Y H:i').' wita</span>';
                } elseif ($payreq->status === 'revise') {
                    $chips[] = '<span class="vj-chip vj-chip-warning">REVISED</span>';
                } elseif ($payreq->status === 'split') {
                    $amount_paid = Outgoing::where('payreq_id', $payreq->id)->sum('amount');
                    $amount_remain = $payreq->amount - $amount_paid;
                    $chips[] = '<span class="vj-chip vj-chip-warning">Payment SPLITTED</span>';
                    $chips[] = '<span class="vj-chip vj-chip-neutral">Remain: '.number_format($amount_remain, 2).'</span>';
                } elseif ($payreq->status === 'paid') {
                    $due_date = \Carbon\Carbon::parse($payreq->due_date);
                    $today = \Carbon\Carbon::now();
                    $dif_days = $due_date->diffInDays($today);

                    $chips[] = '<span class="vj-chip vj-chip-info"><strong>PAID</strong></span>';

                    if ($today->greaterThan($due_date)) {
                        $chips[] = '<span class="vj-chip vj-chip-danger">OVERDUE <strong>'.$dif_days.'</strong> days</span>';
                    } else {
                        $chips[] = '<span class="vj-chip vj-chip-neutral">Due in <strong>'.$dif_days.'</strong> days</span>';
                    }
                } elseif ($payreq->status === 'draft') {
                    $chips[] = '<span class="vj-chip vj-chip-neutral">Draft</span>';
                } elseif ($payreq->status === 'rejected') {
                    $chips[] = '<span class="vj-chip vj-chip-danger">Rejected</span>';
                } elseif ($payreq->status === 'realization') {
                    $chips[] = '<span class="vj-chip vj-chip-success">Realization</span>';
                } else {
                    $chips[] = '<span class="vj-chip vj-chip-neutral">'.ucfirst($payreq->status).'</span>';
                }

                if ($payreq->type === 'reimburse' && $payreq->realization && $payreq->realization->modified_by_approver) {
                    $chips[] = '<span class="vj-chip vj-chip-warning" title="Modified by approver on '.$payreq->realization->modified_by_approver_at->format('d-M-Y H:i').'"><i class="fas fa-exclamation-triangle"></i> Needs Reprint</span>';
                }

                return '<div class="vj-inline-actions flex-wrap">'.implode('', $chips).'</div>';
            })
            ->editColumn('amount', function ($payreq) {
                if ($payreq->type === 'advance') {
                    return number_format($payreq->amount, 2);
                } elseif ($payreq->type === 'other') {
                    return number_format($payreq->amount, 2);
                } else {
                    // if realization has realization_details
                    if ($payreq->realization) {
                        if ($payreq->realization->realizationDetails->count() > 0) {
                            $amount = 0;
                            foreach ($payreq->realization->realizationDetails as $detail) {
                                $amount += $detail->amount;
                            }

                            return number_format($amount, 2);
                        }
                    }

                    return number_format($payreq->amount, 2);
                }
            })
            ->editColumn('submit_at', function ($payreq) {
                if ($payreq->status == 'draft') {
                    return 'Created at '.$payreq->created_at->format('d-M-Y H:i').' wita';
                }
                if ($payreq->status == 'paid') {
                    $paid_date = App(ToolController::class)->getPaidDate($payreq->id);
                    $paid_date = new \Carbon\Carbon($paid_date);

                    return 'Paid at '.$paid_date->format('d-M-Y');
                }
                $submit_date = new \Carbon\Carbon($payreq->submit_at);

                return 'Submit at '.$submit_date->format('d-M-Y H:i').' wita';
            })
            ->addColumn('action', 'user-payreqs.action')
            ->rawColumns(['action', 'nomor', 'type', 'status', 'payment_method'])
            ->addIndexColumn()
            ->toJson();
    }

    public function ongoing_payreqs()
    {
        $status = ['submitted', 'approved', 'paid', 'revise', 'split', 'rejected', 'realization'];

        foreach ($status as $stat) {
            $payreq = Payreq::where('user_id', auth()->user()->id)
                ->where('status', $stat);

            $count = $payreq->count();

            $amount = $payreq->sum('amount');

            $status_cek[] = [
                'status' => $stat,
                'count' => $count,
                'amount' => $amount,
            ];
        }

        $od_payreq = Payreq::where('user_id', auth()->user()->id)
            ->where('status', 'paid')
            ->where('due_date', '<', now());

        $over_due_payreq = [
            'count' => $od_payreq->count(),
            'amount' => $od_payreq->sum('amount'),
        ];

        $result = [
            'payreq_status' => $status_cek,
            'over_due_payreq' => $over_due_payreq,
        ];

        return $result;
    }

    public function storeTransferAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'bank_id' => 'required|integer|exists:banks,id',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:255',
        ]);

        $account = TransferAccount::create([
            'user_id' => auth()->id(),
            'bank_id' => $validated['bank_id'],
            'account_number' => $validated['account_number'],
            'account_name' => $validated['account_name'],
            'label' => $validated['label'],
        ]);

        $account->load('bank');

        return response()->json([
            'status' => 'success',
            'id' => $account->id,
            'label' => $account->displayLabel,
        ]);
    }
}
