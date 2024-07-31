<?php

namespace App\Http\Controllers;

use App\Models\Anggaran;
use App\Models\Payreq;
use App\Models\Rab;
use App\Models\Realization;
use Illuminate\Http\Request;

class PayreqAdvanceController extends Controller
{
    public function create()
    {
        $payreq_no = app(PayreqController::class)->generateDraftNumber();
        $rabs = Anggaran::where('created_by', auth()->user()->id)
            ->where('status', 'approved')
            ->orderBy('nomor', 'asc')
            ->get();

        return view('user-payreqs.advance.create', compact(['payreq_no', 'rabs']));
    }

    public function store(Request $request)
    {
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
                return redirect()->route('user-payreqs.index')->with('error', 'No Approval Plan found. Payreq Advance saved as draft, contact IT Department');
            }

            return redirect()->route('user-payreqs.index')->with('success', 'Payreq Advance submitted');
        }
    }

    public function edit($id)
    {
        $payreq = Payreq::findOrFail($id);
        $rabs = Rab::where('status', 'progress')->orderBy('rab_no', 'asc')->get();

        return view('user-payreqs.advance.edit', compact(['payreq', 'rabs']));
    }

    public function update(Request $request, $id)
    {
        $response = app(PayreqController::class)->update($request, $id);

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
                return redirect()->route('user-payreqs.index')->with('error', 'No Approval Plan found. Payreq Advance saved as draft, contact IT Department');
            }

            // $payreq = Payreq::findOrFail($response->id);
            // $payreq->update([
            //     'approval_plan_count' => $approval_plan_response,
            // ]);

            return redirect()->route('user-payreqs.index')->with('success', 'Payreq Advance submitted');
        }
    }

    public function create_new_payreq($realization_id)
    {
        $realization = Realization::findOrFail($realization_id);
        $payreq_amount = $realization->payreq->amount;
        $realization_amount = $realization->realizationDetails->sum('amount');
        $kekurangan = $realization_amount - $payreq_amount;

        $payreq = Payreq::create([
            'amount' => $kekurangan,
            'department_id' => $realization->department_id,
            'project' => $realization->project,
            'status' => 'approved',
            'editable' =>  0,
            'deletable' => 0,
            'printable' => 1,
            'nomor' => "draft",
            'type' => 'other',
            'remarks' => "Kekurangan untuk Realization Nomor " . $realization->nomor,
            'user_id' => $realization->payreq->user_id,
            'approved_at' => date('Y-m-d H:i:s'),
            'submit_at' => date('Y-m-d H:i:s'),
        ]);

        $payreq_no = app(DocumentNumberController::class)->generate_document_number('payreq', $realization->project);

        $payreq->update([
            'nomor' => $payreq_no,
        ]);

        return $payreq;
    }
}
