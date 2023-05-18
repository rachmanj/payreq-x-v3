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



        return true;
    }
}
