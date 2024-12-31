<?php

namespace App\Http\Controllers;

use App\Models\Payreq;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PayreqController extends Controller
{
    public function store($data)
    {
        $payreq = Payreq::create([
            'remarks' => $data->remarks,
            'amount' => $data->amount ? str_replace(',', '', $data->amount) : null,
            'project' => $data->project,
            'department_id' => $data->department_id,
            'nomor' => $data->payreq_no,
            'status' => 'draft',
            'type' => $data->payreq_type,
            'rab_id' => $data->rab_id,
            'user_id' => $data->employee_id,
        ]);

        return $payreq;
    }

    public function update($data)
    {
        $validated = $data->validate([
            'remarks' => 'required',
            'amount' => 'required',
        ]);

        $validated['amount'] = str_replace(',', '', $validated['amount']);

        $payreq = Payreq::findOrFail($data->payreq_id);
        $payreq->update(array_merge($validated, [
            'rab_id' => $data->rab_id,
        ]));

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
