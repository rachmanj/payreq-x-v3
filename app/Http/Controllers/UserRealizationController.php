<?php

namespace App\Http\Controllers;

use App\Http\Controllers\UserPayreq\PayreqAdvanceController;
use App\Http\Controllers\DocumentNumberController;
use App\Models\ApprovalPlan;
use App\Models\Equipment;
use App\Models\Incoming;
use App\Models\LotClaim;
use App\Models\Payreq;
use App\Models\Realization;
use App\Models\RealizationDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\LotService;

class UserRealizationController extends Controller
{
    public function index()
    {
        $user_payreqs_no_realization = Payreq::where('user_id', auth()->user()->id)
            ->where('status', 'paid')
            ->whereDoesntHave('realization')
            ->get();

        $payreq_with_realization_rejected = Payreq::where('user_id', auth()->user()->id)
            ->where('status', 'paid')
            ->whereHas('realization', function ($query) {
                $query->where('status', 'rejected');
            })
            ->distinct()
            ->get();


        $user_payreqs = $user_payreqs_no_realization->merge($payreq_with_realization_rejected);

        // $realization_no = app(ToolController::class)->generateDraftRealizationNumber();
        $realization_no = app(DocumentNumberController::class)->generate_draft_document_number(auth()->user()->project);

        return view('user-payreqs.realizations.index', compact('user_payreqs', 'realization_no'));
    }

    public function create()
    {
        //
    }

    public function show($id)
    {
        $realization = Realization::findOrFail($id);
        $realization_details = $realization->realizationDetails;
        $approved_at = new Carbon($realization->approved_at);

        $approval_plans = ApprovalPlan::where('document_id', $id)
            ->where('document_type', 'realization')
            ->get();

        $submit_at = new Carbon($realization->submit_at);

        $approval_plan_status = app(ApprovalPlanController::class)->approvalStatus();

        return view('user-payreqs.realizations.show', compact([
            'realization',
            'realization_details',
            'approved_at',
            'approval_plans',
            'submit_at',
            'approval_plan_status'
        ]));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'payreq_id' => 'required'
        ]);

        $payreq = Payreq::findOrFail($request->payreq_id);

        $realization = Realization::create([
            'nomor' => $request->realization_no,
            'payreq_id' => $request->payreq_id,
            'user_id' => auth()->user()->id,
            'project' => $payreq->project,
            'department_id' => $payreq->department_id,
            'status' => 'draft',
            // 'status' => 'pre-draft', //pre-draft, utk ber-jaga2 jika pada saat add details gagal terhubung dgn ark-fleet server, maka pre-draft akan dihapus
        ]);

        return redirect()->route('user-payreqs.realizations.add_details', $realization->id);
    }

    public function submit_realization(Request $request)
    {
        try {
            $realization = Realization::findOrFail($request->realization_id);

            // update payreq status to 'realization'
            $realization->payreq->update([
                'status' => 'realization',
            ]);

            // If payreq has LOT number, try to claim it
            if ($realization->payreq->lot_no) {
                $lotService = app(LotService::class);
                $claimResult = $lotService->claim($realization->payreq->lot_no);

                if (!$claimResult['success']) {
                    throw new \Exception('Failed to claim LOT: ' . $claimResult['message']);
                }

                // Update local LOT claim status
                $lotc = LotClaim::where('lot_no', $realization->payreq->lot_no)->first();
                if ($lotc) {
                    $lotc->update([
                        'is_claimed' => 'yes',
                    ]);
                }
            }

            // create approval plan
            $approval_plan = app(ApprovalPlanController::class)->create_approval_plan('realization', $realization->id);
            if (!$approval_plan) {
                throw new \Exception('Failed to create approval plan');
            }

            $realization->update([
                'status' => 'submitted',
                'printable' => 1, // saat create realization, sudah bisa langsung printable
                'draft_no' => $realization->nomor, // Simpan draft number
                'nomor' => app(DocumentNumberController::class)->generate_document_number('realization', auth()->user()->project),
            ]);

            return redirect()->route('user-payreqs.realizations.index')
                ->with('success', 'Realization submitted successfully');
        } catch (\Exception $e) {
            // Log the error
            Log::error('Realization Submission Error', [
                'error' => $e->getMessage(),
                'realization_id' => $request->realization_id ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            // If the error is from LOT claiming, show the specific error message
            if (str_contains($e->getMessage(), 'Failed to claim LOT:')) {
                return redirect()->route('user-payreqs.realizations.index')
                    ->with('error', $e->getMessage());
            }

            return redirect()->route('user-payreqs.realizations.index')
                ->with('error', 'Failed to submit realization. Please try again or contact support.');
        }
    }

    public function print($id)
    {
        $realization = Realization::findOrFail($id);
        $realization_details = $realization->realizationDetails;
        $approved_at = new Carbon($realization->approved_at);
        $terbilang = app(ToolController::class)->terbilang($realization_details->sum('amount'));
        $approvers = app(ToolController::class)->getApproversName($id, 'realization');

        return view('user-payreqs.realizations.print_pdf', compact([
            'realization',
            'realization_details',
            'approved_at',
            'terbilang',
            'approvers'
        ]));
    }

    public function cancel($realization_id)
    {

        $realization = Realization::where('id', $realization_id)->first();
        $payreq_amount = $realization->payreq->amount;

        // if realization has details
        if ($realization->realizationDetails->count() > 0) {
            // check if realization amount is different from payreq amount
            $realization_amount = $realization->realizationDetails->sum('amount');

            // if realization amount > advance amount, then delete payreq
            if ($realization_amount > $payreq_amount) {
                // delete payreq
                $payreq = Payreq::where('remarks', 'LIKE', '%' . $realization->nomor . '%')->first();
                $payreq->delete();
            }

            // if realization amount < advance amount, then delete incomming payreq
            if ($realization_amount < $payreq_amount) {
                // delete incomming payreq
                $incomming = Incoming::where('realization_id', $realization_id)->first();
                $incomming->delete();
            }

            // delete realization details
            $realization->realizationDetails()->delete();
        }

        // reverse payreq status to 'paid'
        $realization->payreq->update([
            'status' => 'paid',
            'cancel_count' => $realization->payreq->cancel_count + 1,
        ]);

        // delete realization
        $realization->delete();

        return redirect()->route('user-payreqs.realizations.index')->with('success', 'Realization deleted');
    }

    public function destroy($id)
    {
        $realization = Realization::where('id', $id)->first();

        // if realizaiton has details, delete details first
        if ($realization->realizationDetails->count() > 0) {
            $realization->realizationDetails()->delete();
        }

        // reverse payreq status to 'paid'
        $realization->payreq->update([
            'status' => 'paid',
        ]);

        // delete realization
        $realization->delete();

        return $this->index();
    }

    private function checkPayreqLot($payreq_id)
    {
        $payreq = Payreq::findOrFail($payreq_id);
        $lotc = LotClaim::where('lot_no', $payreq->lot_no)->first();
        return $lotc;
    }

    public function add_details($realization_id)
    {
        $realization = Realization::with('payreq')->findOrFail($realization_id);
        $realization_details = $realization->realizationDetails;
        // $equipments = app(ToolController::class)->getEquipments($realization->project);
        // $equipments = Equipment::where('project', $realization->project)->get();

        $roles = app(ToolController::class)->getUserRoles();

        if (auth()->user()->project == '000H' || auth()->user()->project == 'APS' || auth()->user()->project == '001H') {
            $equipments = Equipment::orderBy('unit_code', 'asc')->get();
        } else {
            $equipments = Equipment::where('project', auth()->user()->project)->orderBy('unit_code', 'asc')->get();
        }

        // Check if payreq has LOTC
        $lotc_detail = $this->checkPayreqLot($realization->payreq_id);

        return view('user-payreqs.realizations.add_details', compact([
            'realization',
            'realization_details',
            // 'project_equipment',
            'equipments',
            'lotc_detail'
        ]));
    }

    public function store_detail(Request $request)
    {
        $this->validate($request, [
            'description' => 'required',
            'amount' => 'required',
        ]);

        $realization = Realization::findOrFail($request->realization_id);
        $payreq = Payreq::findOrFail($realization->payreq_id);

        // if payreq of rab_id is not null than realization_detail rab_id = payreq rab_id
        if ($payreq->rab_id != null) {
            $rab_id = $payreq->rab_id;
        } else {
            $rab_id = null;
        }

        $realization->realizationDetails()->create([
            'description' => $request->description,
            'amount' => str_replace(',', '', $request->amount),
            'project' => $realization->project,
            'department_id' => $realization->department_id,
            'unit_no' => $request->unit_no,
            'nopol' => $request->nopol,
            'type' => $request->type,
            'qty' => $request->qty,
            'uom' => $request->uom,
            'km_position' => $request->km_position,
            'rab_id' => $rab_id,
        ]);

        return redirect()->route('user-payreqs.realizations.add_details', $realization->id);
    }

    public function delete_detail($realization_detail_id)
    {
        $realization_detail = RealizationDetail::findOrFail($realization_detail_id);

        $realization_detail->delete();

        return redirect()->route('user-payreqs.realizations.add_details', $realization_detail->realization_id)->with('success', 'Realization Detail deleted');
    }

    public function data()
    {
        // get user's roles
        $userRoles = app(UserController::class)->getUserRoles();
        $status_include = ['approved', 'revise', 'submitted', 'draft', 'rejected', 'verification', 'verification-complete'];

        if (in_array('superadmin', $userRoles)) {
            $realizations = Realization::whereIn('status', $status_include)
                ->get();
        } else {
            $realizations = Realization::whereIn('status', $status_include)
                ->where('user_id', auth()->user()->id)
                ->whereNotNull('payreq_id')
                ->get();
        }

        return datatables()->of($realizations)
            ->editColumn('nomor', function ($realization) {
                return '<a href="' . route('user-payreqs.realizations.show', $realization->id) . '">' . $realization->nomor . '</a>';
            })
            ->addColumn('payreq_no', function ($realization) {
                return $realization->payreq->nomor;
            })
            ->addColumn('amount', function ($realization) {
                return number_format($realization->realizationDetails->sum('amount'), 2, ',', '.');
            })
            ->editColumn('created_at', function ($realization) {
                return $realization->created_at->format('d-M-Y H:i') . ' wita';
            })
            ->addColumn('days', function ($realization) {
                if ($realization->approved_at) {
                    $diff = Carbon::now()->diffInDays(Carbon::parse($realization->approved_at));
                } else {
                    $diff = '-';
                }

                return $diff;
            })
            ->editColumn('status', function ($realization) {
                if ($realization->status === 'submitted') {
                    return 'Waiting Approval';
                } elseif ($realization->status === 'verification-complete') {
                    return '<button class="btn btn-xs btn-outline-success mx-2" style="pointer-events: none;">Verification Complete</button>';
                } else {
                    if ($realization->due_date == null) {
                        return ucfirst($realization->status);
                    }

                    $due_date = new \Carbon\Carbon($realization->due_date);
                    $today = new \Carbon\Carbon();
                    $dif_days = $due_date->diffInDays($today);
                    if ($today > $due_date && $realization->status == 'approved') {
                        return ucfirst($realization->status) . '<button class="btn btn-xs btn-danger mx-2" style="pointer-events: none;">OVER DUE <b>' . $dif_days . '</b> days</button>';
                    } else {
                        return '<button class="btn btn-xs btn-outline-danger mx-2" style="pointer-events: none;">Approved and due in <b>' . $dif_days . '</b> days</button>';
                    }
                }
            })
            ->addColumn('action', 'user-payreqs.realizations.action')
            ->rawColumns(['action', 'nomor', 'status'])
            ->addIndexColumn()
            ->toJson();
    }

    /*
    * check if realization amount is different from payreq amount
    * if realization amount > payreq amount, then create a new payreq
    * if realization amount < payreq amount, then create a new incomming payreq
    */
    public function check_realization_amount($realization_id)
    {
        $realization = Realization::findOrFail($realization_id);
        $realization_amount = $realization->realizationDetails->sum('amount');
        $payreq_amount = $realization->payreq->amount;

        if ($realization_amount > $payreq_amount) {
            // create new payreq
            $new_payreq = app(PayreqAdvanceController::class)->create_new_payreq($realization_id);

            if ($new_payreq) {
                return redirect()->route('user-payreqs.realizations.index')->with('success', 'Realization amount is different from Payreq amount. New Payreq created');
            } else {
                return redirect()->route('user-payreqs.realizations.index')->with('error', 'Realization amount is different from Payreq amount. Failed to create new Payreq');
            }
        } elseif ($realization_amount < $payreq_amount) {
            // create new incomming payreq
            $new_incomming_payreq = app(IncomingController::class)->create_new_incomming_realization($realization_id);

            if ($new_incomming_payreq) {
                return redirect()->route('user-payreqs.realizations.index')->with('success', 'Realization amount is different from Payreq amount. New Incomming Payreq created');
            } else {
                return redirect()->route('user-payreqs.realizations.index')->with('error', 'Realization amount is different from Payreq amount. Failed to create new Incomming Payreq');
            }
        } else {
            return redirect()->route('user-payreqs.realizations.index')->with('success', 'Realization amount is equal to Payreq amount');
        }
    }

    public function ongoing_realizations()
    {
        $status = ['submitted', 'approved', 'verification', 'revise', 'rejected'];

        foreach ($status as $stat) {
            $realizations = Realization::where('user_id', auth()->user()->id)
                ->where('status', $stat);

            $realization_count = $realizations->count();
            // realization amount is sum of realization details amount
            $realization_amount = 0;
            foreach ($realizations->get() as $realization) {
                $realization_amount += $realization->realizationDetails->sum('amount');
            }


            $status_cek[] = [
                'status' => $stat,
                'count' => $realization_count,
                'amount' => $realization_amount,
            ];
        }

        // overdue realization
        $od_realization = Realization::with('realizationDetails')->where('user_id', auth()->user()->id)
            ->where('status', 'approved')
            ->where('due_date', '<', Carbon::now())
            ->get();

        $overdue_realization = [
            'count' => $od_realization->count(),
            'amount' => $od_realization->sum(function ($realization) {
                return $realization->realizationDetails->sum('amount');
            })
        ];

        $result = [
            'realization_status' => $status_cek,
            'overdue_realization' => $overdue_realization
        ];

        return $result;
    }

    /**
     * Get all details for a realization.
     *
     * @param  \App\Models\Realization  $realization
     * @return \Illuminate\Http\Response
     */
    public function getDetails($realization_id)
    {
        $details = RealizationDetail::where('realization_id', $realization_id)->get();

        return response()->json([
            'success' => true,
            'details' => $details
        ]);
    }

    /**
     * Get a specific detail.
     *
     * @param  \App\Models\RealizationDetail  $detail
     * @return \Illuminate\Http\Response
     */
    public function getDetail($detail_id)
    {
        $detail = RealizationDetail::findOrFail($detail_id);
        return response()->json($detail);
    }

    /**
     * Update a specific detail.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RealizationDetail  $detail
     * @return \Illuminate\Http\Response
     */
    public function updateDetail(Request $request, $detail_id)
    {
        $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'unit_no' => 'nullable|string|max:50',
            'nopol' => 'nullable|string|max:50',
            'qty' => 'nullable|numeric',
            'km_position' => 'nullable|numeric',
            'type' => 'nullable|string|max:50',
            'uom' => 'nullable|string|max:50',
        ]);

        $detail = RealizationDetail::findOrFail($detail_id);

        // Update the detail
        $detail->update([
            'description' => $request->description,
            'amount' => $request->amount,
            'unit_no' => $request->unit_no,
            'nopol' => $request->nopol,
            'qty' => $request->qty,
            'km_position' => $request->km_position,
            'type' => $request->type,
            'uom' => $request->uom,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Detail updated successfully',
            'detail' => $detail
        ]);
    }
}
