<?php

namespace App\Http\Controllers;

use App\Models\ApprovalPlan;
use App\Models\ApprovalStage;
use App\Models\Payreq;
use App\Models\User;
use Illuminate\Http\Request;

class ApprovalPlanController extends Controller
{
    public function create_approval_plan($payreq_id)
    {
        $payreq = Payreq::findOrFail($payreq_id);

        $approvers = ApprovalStage::where('project', $payreq->project)
            ->where('department_id', $payreq->department_id)
            ->get();

        // if false
        if ($approvers->count() == 0) {
            return false;
        }

        foreach ($approvers as $approver) {
            ApprovalPlan::create([
                'payreq_id' => $payreq_id,
                'approver_id' => $approver->approver_id,
            ]);
        }

        // if success
        return $approvers->count();
    }

    public function update(Request $request, $id)
    {
        $approval_plan = ApprovalPlan::findOrFail($id);
        $approval_plan->update([
            'status' => $request->status,
            'remarks' => $request->remarks,
            'is_read' => 0
        ]);

        // update payreq status
        $payreq = Payreq::findOrFail($approval_plan->payreq_id);

        // update status to approved if all approvers approved
        $approval_plans = ApprovalPlan::where('payreq_id', $payreq->id)->get();

        // if there is at least one rejected or revise than status is rejected or revise
        $rejected_count = 0;
        $revised_count = 0;
        $approved_count = 0;
        foreach ($approval_plans as $approval_plan) {
            if ($approval_plan->status == 3) {
                $rejected_count++;
            }
            if ($approval_plan->status == 2) {
                $revised_count++;
            }
            if ($approval_plan->status == 1) {
                $approved_count++;
            }
        }

        if ($revised_count > 0) {
            $payreq->update([
                'status' => 'revise',
                'editable' => 1,
                'deletable' => 1,
            ]);
        }

        if ($rejected_count > 0) {
            $payreq->update([
                'status' => 'rejected',
                'deletable' => 1,
            ]);
        }

        if ($approved_count == $approval_plans->count()) {
            $payreq->update([
                'status' => 'approved',
                'printable' => 1,
                'approved_at' => $approval_plan->updated_at,
                'payreq_no' => app(PayreqController::class)->generatePRNumber($payreq->id),
            ]);
        }

        return redirect()->route('approvals.request.index')->with('success', 'Approval Request updated');
    }

    public function approvalStatus()
    {
        return [
            0 => 'Pending',
            1 => 'Approved',
            2 => 'Revised',
            3 => 'Rejected',
        ];
    }
}
