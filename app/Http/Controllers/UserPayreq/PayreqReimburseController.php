<?php

namespace App\Http\Controllers\UserPayreq;

use App\Http\Controllers\ApprovalPlanController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DocumentNumberController;
use App\Http\Controllers\PayreqController;
use App\Models\Anggaran;
use App\Models\Equipment;
use App\Models\Payreq;
use App\Models\Realization;
use App\Models\RealizationDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PayreqReimburseController extends Controller
{
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
        // $rabs = $this->getRabs();

        return view('user-payreqs.reimburse.add_details', compact(['payreq', 'equipments', 'realization', 'rabs']));
    }

    public function edit($id)
    {
        $equipments = $this->getEquipments();
        $payreq = Payreq::findOrFail($id);
        $realization = Realization::where('payreq_id', $payreq->id)->first();
        $rabs = app(UserAnggaranController::class)->getAvailableRabs();

        return view('user-payreqs.reimburse.add_details', compact(['payreq', 'equipments', 'realization', 'rabs']));
    }

    public function store_detail(Request $request)
    {
        $this->validate($request, [
            'description' => 'required',
            'amount' => 'required|numeric',
        ]);

        $realization = Realization::findOrFail($request->realization_id);

        $realization->realizationDetails()->create([
            'description' => $request->description,
            'amount' => $request->amount,
            'project' => $realization->project,
            'department_id' => $realization->department_id,
            'unit_no' => $request->unit_no,
            'nopol' => $request->nopol,
            'type' => $request->type,
            'qty' => $request->qty,
            'uom' => $request->uom,
            'km_position' => $request->km_position,
        ]);

        // update payreq amount is sum of realization details amount
        $payreq = Payreq::findOrFail($realization->payreq_id);
        $payreq->update([
            'amount' => $realization->realizationDetails()->sum('amount'),
        ]);

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

        if (!$approval_plan) {
            return redirect()->route('user-payreqs.index')->with('error', 'Payreq failed to submit');
        }

        $payreq->update([
            'status' => 'submitted',
        ]);

        $realization->update([
            'status' => 'reimburse-submitted',
            'submit_at' => Carbon::now(),
            'editable' => 0,
            'deletable' => 0,
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

        return $this->edit($realization->payreq_id);
    }

    public function getEquipments()
    {
        if (!in_array(auth()->user()->project, ['000H', 'APS', '001H'])) {
            return Equipment::where('project', auth()->user()->project)->orderBy('unit_code', 'asc')->get();
        }

        return Equipment::orderBy('unit_code', 'asc')->get();;
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
