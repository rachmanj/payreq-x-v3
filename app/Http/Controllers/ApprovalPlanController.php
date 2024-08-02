<?php

namespace App\Http\Controllers;

use App\Models\Anggaran;
use App\Models\ApprovalPlan;
use App\Models\ApprovalStage;
use App\Models\Payreq;
use App\Models\Realization;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ApprovalPlanController extends Controller
{
    public function create_approval_plan($document_type, $document_id)
    {
        if ($document_type == 'payreq') {
            $document = Payreq::findOrFail($document_id);
        } elseif ($document_type == 'realization') {
            $document = Realization::findOrFail($document_id);
        } elseif ($document_type == 'rab') {
            $document = Anggaran::findOrFail($document_id);
        } else {
            return false;
        }

        $approvers = ApprovalStage::where('project', $document->project)
            ->where('department_id', $document->department_id)
            ->where('document_type', $document_type)
            ->get();

        // if success or count > 0
        if ($approvers->count() > 0) {
            foreach ($approvers as $approver) {
                ApprovalPlan::create([
                    'document_id' => $document_id,
                    'document_type' => $document_type,
                    'approver_id' => $approver->approver_id,
                ]);
            }

            $document->submit_at = Carbon::now();
            $document->editable = 0;
            $document->deletable = 0;
            $document->save();

            return $approvers->count();
        }

        // if false
        return false;
    }

    /* this function is to give approval decision to approval plan
    * update approval decision: 1 = approved, 2 = revise, 3 = reject
    */
    public function update(Request $request, $id)
    {
        // update approval plan
        $approval_plan = ApprovalPlan::findOrFail($id);
        $approval_plan->update([
            'status' => $request->status,
            'remarks' => $request->remarks,
            'is_read' => $request->remarks ? 0 : 1,
        ]);

        // update document status
        $document_type = $approval_plan->document_type;

        if ($document_type == 'payreq') {
            $document = Payreq::where('id', $approval_plan->document_id)->first();
            // $nomor = app(DocumentNumberController::class)->generate_document_number('payreq', auth()->user()->project);
        } elseif ($document_type == 'realization') {
            $document = Realization::findOrFail($approval_plan->document_id);
            // $nomor = app(DocumentNumberController::class)->generate_document_number('realization', auth()->user()->project);
        } elseif ($document_type == 'rab') {
            $document = Anggaran::findOrFail($approval_plan->document_id);
        } else {
            return false;
        }

        // update status to approved if all approvers approved
        $approval_plans = ApprovalPlan::where('document_id', $document->id)
            ->where('document_type', $document_type)
            ->where('is_open', 1)
            ->get();

        // if there is at least one rejected or revise than status is rejected or revise
        $rejected_count = 0;
        $revised_count = 0;
        $approved_count = 0;
        foreach ($approval_plans as $approval_plan) {
            if ($approval_plan->status == 3) { // rejected
                $rejected_count++;
            }
            if ($approval_plan->status == 2) { // revised
                $revised_count++;
            }
            if ($approval_plan->status == 1) {  // approved
                $approved_count++;
            }
        }

        if ($revised_count > 0) {
            $document->update([
                'status' => 'revise',
                'editable' => 1,
                'deletable' => 1,
            ]);

            $this->cekExistingAndDisableOpen($document_type, $document->id);
        }

        if ($rejected_count > 0) {
            $document->update([
                'status' => 'rejected',
                'deletable' => 1,
            ]);

            $this->cekExistingAndDisableOpen($document_type, $document->id);
        }

        // jika semua approver menyetujui
        if ($approved_count === $approval_plans->count()) {
            // update fields of document, so all documents must have these fields
            $document->update([
                'status' => 'approved',
                'draft_no' => $document->nomor,
                'printable' => 1,
                'editable' => 0,
                'approved_at' => $approval_plan->updated_at,
                'nomor' => app(DocumentNumberController::class)->generate_document_number($document_type, auth()->user()->project),
            ]);

            if ($request->document_type === 'payreq') {
                // jika payreq jenis reimburse, maka update dulu realization status menjadi approved-reimburse trus redirect ke index payreqs
                if ($document->type === 'reimburse') {
                    $realization = Realization::where('payreq_id', $document->id)->first();
                    $realization->update([
                        'status' => 'reimburse-approved',
                        'approved_at' => $approval_plan->updated_at,
                        'nomor' => app(DocumentNumberController::class)->generate_document_number('realization', auth()->user()->project),
                    ]);
                }
            }

            // additional action for realization when document is approved
            if ($request->document_type === 'realization') {
                // update field due_date of realization
                $realization = Realization::findOrFail($document->id);
                $realization->update([
                    'due_date' => Carbon::now()->addDays(3),
                ]);
                // check the variance between payreq and realization
                app(UserRealizationController::class)->check_realization_amount($document->id);
            }

            // additional action for anggaran when document is approved
            if ($request->document_type === 'rab') {
                // update field periode_ofr of anggaran
                $document->update([
                    'periode_ofr' => $request->periode_ofr,
                ]);
            }
        }

        // redirect base on document type
        if ($request->document_type === 'payreq') {
            return redirect()->route('approvals.request.payreqs.index')->with('success', 'Approval Request updated');
        } elseif ($request->document_type === 'realization') {
            return redirect()->route('approvals.request.realizations.index')->with('success', 'Approval Request updated');
        } elseif ($request->document_type === 'rab') {
            return redirect()->route('approvals.request.anggarans.index')->with('success', 'Approval Request updated');
        } else {
            return false;
        }
    }

    public function approvalStatus()
    {
        return [
            0 => 'Pending',
            1 => 'Approved',
            2 => 'Revised',
            3 => 'Rejected',
            4 => 'Canceled',
        ];
    }

    public function cekExistingAndDisableOpen($document_type, $document_id)
    {
        // cek if there are approval plans still open (is_open = 1) for the document
        $approval_plans = ApprovalPlan::where('document_id', $document_id)
            ->where('document_type', $document_type)
            ->where('is_open', 1)
            ->get();

        // if there is approval plan for this document then change is_open to false / 0
        if ($approval_plans->count() > 0) {
            foreach ($approval_plans as $approval_plan) {
                $approval_plan->update(['is_open' => 0]);
            }
        }
    }
}
