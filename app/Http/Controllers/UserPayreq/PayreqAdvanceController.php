<?php

namespace App\Http\Controllers\UserPayreq;

use App\Http\Controllers\ApprovalPlanController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DocumentNumberController;
use App\Http\Controllers\PayreqController;
use App\Models\Anggaran;
use App\Models\Payreq;
use App\Models\Realization;
use Illuminate\Http\Request;

class PayreqAdvanceController extends Controller
{
    public function create()
    {
        $payreq_no = app(DocumentNumberController::class)->generate_draft_document_number(auth()->user()->project);
        $rabs = $this->getUserRabs();

        return view('user-payreqs.advance.create', compact(['payreq_no', 'rabs']));
    }

    public function edit($id)
    {
        $payreq = Payreq::findOrFail($id);
        $rabs = $this->getUserRabs();

        return view('user-payreqs.advance.edit', compact(['payreq', 'rabs']));
    }

    public function proses(Request $request)
    {
        $request->validate([
            'remarks' => 'required',
            'amount' => 'required|numeric',
        ]);

        if ($request->button_type === 'create') {
            return $this->draft_redirect(app(PayreqController::class)->store($request));
        } elseif ($request->button_type === 'edit') {
            return $this->draft_redirect(app(PayreqController::class)->update($request));
        } elseif ($request->button_type === 'create_submit') {
            $response = app(PayreqController::class)->store($request);
            return $this->submit_redirect($response->id);
        } elseif ($request->button_type === 'edit_submit') {
            $response = app(PayreqController::class)->update($request);
            return $this->submit($response->id);
        } else {
            return redirect()->back()->with('error', 'There is an error in the form');
        }
    }

    public function submit($id)
    {
        $payreq = Payreq::find($id);
        $projecs = ['000H', 'APS'];

        if (!in_array(auth()->user()->project, $projecs)) {
            $response = app(ApprovalPlanController::class)->create_approval_plan('payreq', $id);

            if ($response) {

                $payreq->update([
                    'status' => 'submitted',
                    'editable' => '0',
                    'deletable' => '0',
                ]);
                return redirect()->route('user-payreqs.index')->with('success', 'Payreq berhasil disubmit');
            } else {
                return redirect()->route('user-payreqs.index')->with('error', 'Payreq gagal disubmit. Hubungi IT Administrator');
            }
        }

        if ($payreq->rab_id == null) {
            $payreq->update([
                'status' => 'draft',
            ]);

            return redirect()->route('user-payreqs.index')->with('error', 'RAB harus diisi, payreq belum bisa disubmit');
        }

        $response = app(ApprovalPlanController::class)->create_approval_plan('payreq', $id);

        if ($response) {
            $payreq = Payreq::find($id);
            $payreq->update([
                'status' => 'submitted',
                'editable' => '0',
                'deletable' => '0',
            ]);
            return redirect()->route('user-payreqs.index')->with('success', 'Payreq berhasil disubmit');
        } else {
            return redirect()->route('user-payreqs.index')->with('error', 'Payreq gagal disubmit. Hubungi IT Administrator');
        }
    }

    public function draft_redirect($response)
    {
        if ($response) {
            return redirect()->route('user-payreqs.index')->with('success', 'Payreq draft berhasil disimpan');
        } else {
            return redirect()->route('user-payreqs.index')->with('error', 'Payreq draft gagal disimpan. Hubungi IT Administrator');
        }
    }

    public function create_new_payreq($realization_id)
    {
        $realization = Realization::findOrFail($realization_id);
        $payreq_amount = $realization->payreq->amount;
        $realization_amount = $realization->realizationDetails->sum('amount');
        $kekurangan = $realization_amount - $payreq_amount;
        $payreq_no = app(DocumentNumberController::class)->generate_document_number('payreq', $realization->project);

        $payreq = Payreq::create([
            'amount' => $kekurangan,
            'department_id' => $realization->department_id,
            'project' => $realization->project,
            'status' => 'approved',
            'editable' =>  0,
            'deletable' => 0,
            'printable' => 1,
            'nomor' => $payreq_no,
            'type' => 'other',
            'remarks' => "Kekurangan untuk Realization Nomor " . $realization->nomor,
            'user_id' => $realization->payreq->user_id,
            'approved_at' => date('Y-m-d H:i:s'),
            'submit_at' => date('Y-m-d H:i:s'),
        ]);

        return $payreq;
    }

    public function getUserRabs()
    {
        $rabs = Anggaran::where('created_by', auth()->user()->id)
            ->where('status', 'approved')
            ->orderBy('nomor', 'asc')
            ->get();

        return $rabs;
    }
}