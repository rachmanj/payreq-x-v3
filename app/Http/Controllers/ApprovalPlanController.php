<?php

namespace App\Http\Controllers;

use App\Models\ApprovalPlan;
use App\Models\ApprovalStage;
use App\Models\Payreq;
use App\Models\User;
use Illuminate\Http\Request;

class ApprovalPlanController extends Controller
{
    public function create_approval_plan($user_id, $payreq_id)
    {
        $user = User::find($user_id);

        $approvers = ApprovalStage::where('project', $user->project)
            ->where('department_id', $user->department_id)
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
        $payreq->update([
            'status' => $request->status,
        ]);

        return redirect()->route('approvals.request.index')->with('success', 'Approval Request updated');
    }
}
