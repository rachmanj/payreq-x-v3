<?php

namespace App\Http\Controllers;

use App\Models\Payreq;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PayreqController extends Controller
{
    public function store($data)
    {
        // return $data;
        // die;

        // payreq type is advance 
        if ($data->form_type == 'advance') {
            $validated = $data->validate([
                'remarks' => 'required',
                'amount' => 'required|numeric',
                'project' => 'required',
                'department_id' => 'required',
            ]);

            $payreq = Payreq::create(array_merge($validated, [
                'project' => auth()->user()->project,
                'status' => $data->draft == '1' ? 'draft' : 'submitted',
                'editable' => $data->draft == '1' ? '1' : '0',
                'deletable' => $data->draft == '1' ? '1' : '0',
                'payreq_no' => $this->generateDraftNumber(),
                'type' => 'advance',
                'user_id' => auth()->user()->id,
            ]));

            return $payreq;
        } else {
            // payreq type is other
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
            ]));

            return $payreq;
        } else {
            // payreq type is other
        }
    }

    public function generateDraftNumber()
    {
        $status_include = ['draft', 'submitted'];
        $payreq_project_count = Payreq::where('project', auth()->user()->project)
            ->whereIn('status', $status_include)
            ->count();
        $nomor = 'FQ' . Carbon::now()->addHours(8)->format('y') . auth()->user()->project . str_pad($payreq_project_count + 1, 3, '0', STR_PAD_LEFT);

        return $nomor;
    }

    public function generatePRNumber($payreq_id)
    {
        $payreq = Payreq::findOrFail($payreq_id);
        $payreq_project_count = Payreq::where('project', $payreq->project)
            ->where('status', 'approved')
            ->count();
        $nomor = Carbon::now()->format('y') . auth()->user()->project . str_pad($payreq_project_count + 1, 5, '0', STR_PAD_LEFT);

        return $nomor;
    }
}
