<?php

namespace App\Http\Controllers;

use App\Models\Payreq;
use Illuminate\Http\Request;

class PayreqAdvanceController extends Controller
{
    public function create()
    {
        $payreq_no = app(PayreqController::class)->generateDraftNumber();

        return view('user-payreqs.advance.create', compact('payreq_no'));
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

        return view('user-payreqs.advance.edit', compact('payreq'));
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
}
