<?php

namespace App\Http\Controllers\UserPayreq;

use App\Http\Controllers\ApprovalPlanController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DocumentNumberController;
use App\Http\Controllers\PayreqController;
use App\Http\Requests\StoreRealizationDetailRequest;
use App\Http\Requests\UpdateRealizationDetailRequest;
use App\Models\Equipment;
use App\Models\LotClaim;
use App\Models\Payreq;
use App\Models\Realization;
use App\Models\RealizationDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PayreqReimburseController extends Controller
{
    private function lotClaimForPayreq(Payreq $payreq): ?LotClaim
    {
        if (! $payreq->lot_no) {
            return null;
        }

        return LotClaim::where('lot_no', $payreq->lot_no)->first();
    }

    public function create()
    {
        $payreq_no = app(DocumentNumberController::class)->generate_draft_document_number(auth()->user()->project);
        $rabs = app(UserAnggaranController::class)->getAvailableRabs();

        return view('user-payreqs.reimburse.create', compact('payreq_no', 'rabs'));
    }

    public function store(Request $request)
    {
        // Create new Payreq with type 'reimburse'
        $payreq = app(PayreqController::class)->store($request);

        // Create new Realization
        $realization = Realization::create([
            'payreq_id' => $payreq->id,
            'project' => $payreq->project,
            'department_id' => $payreq->department_id,
            'remarks' => $request->remarks,
            'user_id' => $payreq->user_id,
            'nomor' => app(DocumentNumberController::class)->generate_draft_document_number(auth()->user()->project),
            'status' => 'reimburse-draft',
        ]);

        $equipments = $this->getEquipments();
        $rabs = app(UserAnggaranController::class)->getAvailableRabs();
        $lotc_detail = $this->lotClaimForPayreq($payreq);

        return view('user-payreqs.reimburse.add_details', compact(['payreq', 'equipments', 'realization', 'rabs', 'lotc_detail']));
    }

    public function edit($id)
    {
        $equipments = $this->getEquipments();
        $payreq = Payreq::findOrFail($id);
        $realization = Realization::where('payreq_id', $payreq->id)->first();
        $rabs = app(UserAnggaranController::class)->getAvailableRabs();
        $lotc_detail = $this->lotClaimForPayreq($payreq);

        return view('user-payreqs.reimburse.add_details', compact(['payreq', 'equipments', 'realization', 'rabs', 'lotc_detail']));
    }

    public function store_detail(StoreRealizationDetailRequest $request)
    {
        $realization = Realization::findOrFail($request->validated('realization_id'));
        $payreq = Payreq::findOrFail($realization->payreq_id);

        $rab_id = $payreq->rab_id;

        $detail = $realization->realizationDetails()->create(array_merge($request->realizationDetailPayload(), [
            'project' => $realization->project,
            'department_id' => $realization->department_id,
            'rab_id' => $rab_id,
        ]));

        $payreq->update([
            'amount' => $realization->realizationDetails()->sum('amount'),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Detail added successfully',
                'total' => $realization->realizationDetails()->sum('amount'),
                'detail' => $detail->fresh(),
            ]);
        }

        return $this->edit($realization->payreq_id);
    }

    public function submit_payreq(Request $request)
    {
        $realization = Realization::findOrFail($request->realization_id);
        $payreq = Payreq::findOrFail($realization->payreq_id);

        // cek user project, jika user project adalah 000H, maka field rab_id harus diisi
        if (in_array(auth()->user()->project, ['000H', 'APS'])) {
            if ($payreq->rab_id == null) {
                $payreq->update([
                    'status' => 'draft',
                    'editable' => '1',
                    'deletable' => '1',
                ]);

                return redirect()->route('user-payreqs.index')->with('error', 'RAB harus diisi, payreq belum bisa disubmit');
            }
        }

        // create approval plan
        $approval_plan = app(ApprovalPlanController::class)->create_approval_plan('payreq', $payreq->id);

        if (! $approval_plan) {
            return redirect()->route('user-payreqs.index')->with('error', 'Payreq failed to submit');
        }

        $payreq->update([
            'status' => 'submitted',
            // 'printable' => 1, // saat submit payreq, sudah bisa langsung printable
            'draft_no' => $payreq->nomor, // Simpan draft number
            'nomor' => app(DocumentNumberController::class)->generate_document_number('payreq', auth()->user()->project),
        ]);

        $realization->update([
            'status' => 'reimburse-submitted',
            'submit_at' => Carbon::now(),
            'editable' => 0,
            'deletable' => 0,
            'draft_no' => $realization->nomor, // Simpan draft number
            'nomor' => app(DocumentNumberController::class)->generate_document_number('realization', auth()->user()->project),
        ]);

        return redirect()->route('user-payreqs.index')->with('success', 'Payreq submitted successfully');
    }

    public function delete_detail(Request $request)
    {
        $realization = Realization::findOrFail($request->realization_id);

        $realization_detail = RealizationDetail::findOrFail($request->realization_detail_id);
        $realization_detail->delete();

        // update payreq amount is sum of realization details amount
        $payreq = Payreq::findOrFail($realization->payreq_id);
        $payreq->update([
            'amount' => $realization->realizationDetails()->sum('amount'),
        ]);

        // Check if this is an AJAX request
        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Detail deleted successfully',
                'total' => $realization->realizationDetails()->sum('amount'),
            ]);
        }

        return $this->edit($realization->payreq_id);
    }

    public function update_detail(UpdateRealizationDetailRequest $request)
    {
        $detail = RealizationDetail::findOrFail($request->validated('realization_detail_id'));
        $realization = Realization::findOrFail($detail->realization_id);

        $detail->update($request->realizationDetailPayload());

        $payreq = Payreq::findOrFail($realization->payreq_id);
        $payreq->update([
            'amount' => $realization->realizationDetails()->sum('amount'),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Detail updated successfully',
                'total' => $realization->realizationDetails()->sum('amount'),
                'detail' => $detail->fresh(),
            ]);
        }

        return $this->edit($realization->payreq_id);
    }

    public function getEquipments()
    {
        if (! in_array(auth()->user()->project, ['000H', 'APS', '001H'])) {
            return Equipment::where('project', auth()->user()->project)->orderBy('unit_code', 'asc')->get();
        }

        return Equipment::orderBy('unit_code', 'asc')->get();
    }

    public function update_rab(Request $request)
    {
        $payreq = Payreq::findOrFail($request->payreq_id);
        $payreq->update([
            'rab_id' => $request->rab_id,
            'remarks' => $request->remarks,
        ]);

        return redirect()->route('user-payreqs.index')->with('success', 'RAB updated successfully');
    }
}
