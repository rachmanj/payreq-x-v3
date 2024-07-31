<?php

namespace App\Http\Controllers;

use App\Models\Payreq;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PayreqController extends Controller
{
    public function store($data)
    {
        // PAYREQ TYPE IS ADVANCE
        if ($data->form_type == 'advance') {
            $validated = $data->validate([
                'remarks' => 'required',
                'amount' => 'required|numeric',
                'project' => 'required',
                'department_id' => 'required',
            ]);

            $payreq = Payreq::create(array_merge($validated, [
                'project' => $data->project,
                'status' => $data->draft == '1' ? 'draft' : 'submitted',
                'editable' => $data->draft == '1' ? '1' : '0',
                'deletable' => $data->draft == '1' ? '1' : '0',
                'nomor' => $data->payreq_no,
                'type' => 'advance',
                'rab_id' => $data->rab_id,
                'user_id' => $data->employee_id,
            ]));

            return $payreq;
        } else {
            // PAYREQ TYPE IS OTHER
        }
    }

    public function update($data, $id)
    {
        // if payreq type is advance
        if ($data->form_type == 'advance') {
            $validated = $data->validate([
                'remarks' => 'required',
                'amount' => 'required|numeric',
                'project' => 'required',
                'department_id' => 'required',
            ]);

            $payreq = Payreq::findOrFail($id);
            $payreq->update(array_merge($validated, [
                'status' => $data->draft == '1' ? 'draft' : 'submitted',
                'editable' => $data->draft == '1' ? '1' : '0',
                'deletable' => $data->draft == '1' ? '1' : '0',
                'rab_id' => $data->rab_id,
            ]));

            return $payreq;
        } else {
            // payreq type is other
        }
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
