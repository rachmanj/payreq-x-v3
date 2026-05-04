<?php

namespace App\Http\Controllers;

use App\Models\Payreq;
use App\Support\PayreqBudgetLinkMode;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PayreqController extends Controller
{
    public function store($data)
    {
        $budgetLinkMode = (($data->payreq_type ?? null) === 'advance')
            ? ($data->budget_link_mode ?? PayreqBudgetLinkMode::LEGACY)
            : null;

        $payreq = Payreq::create([
            'remarks' => $data->remarks,
            'amount' => $data->amount ? str_replace(',', '', (string) $data->amount) : null,
            'project' => $data->project,
            'department_id' => $data->department_id,
            'nomor' => $data->payreq_no,
            'status' => 'draft',
            'type' => $data->payreq_type,
            'rab_id' => $data->rab_id,
            'budget_link_mode' => $budgetLinkMode,
            'lot_no' => $data->lot_no,
            'user_id' => $data->employee_id,
        ]);

        return $payreq;
    }

    public function update(Request $data)
    {
        $validated = $data->validate([
            'remarks' => 'required',
            'amount' => 'required',
        ]);

        $validated['amount'] = str_replace(',', '', $validated['amount']);

        $payreq = Payreq::findOrFail($data->payreq_id);

        $merge = array_merge($validated, [
            'rab_id' => $data->rab_id,
            'lot_no' => $data->lot_no,
        ]);

        $mode = data_get($data, 'budget_link_mode');
        if (($payreq->type ?? null) === 'advance' && $mode !== null) {
            $merge['budget_link_mode'] = $mode;
        }

        $payreq->update($merge);

        return $payreq;
    }

    public function cancel($id)
    {
        $payreq = Payreq::findOrFail($id);

        $payreq->update([
            'status' => 'canceled',
            'canceled_at' => Carbon::now(),
            'printable' => '0',
        ]);

        if ($payreq->type === 'reimburse') {

            if ($payreq->realization->realizationDetails->count() > 0) {
                foreach ($payreq->realization->realizationDetails as $detail) {
                    $detail->delete();
                }
            }
            $payreq->realization->delete();
        }

        return $payreq;
    }
}
