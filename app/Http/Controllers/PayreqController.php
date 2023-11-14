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
                'project' => auth()->user()->project,
                'status' => $data->draft == '1' ? 'draft' : 'submitted',
                'editable' => $data->draft == '1' ? '1' : '0',
                'deletable' => $data->draft == '1' ? '1' : '0',
                'nomor' => $this->generateDraftNumber(),
                'type' => 'advance',
                'user_id' => auth()->user()->id,
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

        return $payreq;
    }

    public function generateDraftNumber()
    {
        $status_include = ['draft', 'submitted', 'revised', 'approved'];
        $payreq_project_count = Payreq::where('project', auth()->user()->project)
            ->whereYear('created_at', Carbon::now()->format('Y'))
            ->whereIn('status', $status_include)
            ->count();
        $nomor = 'FQ' . Carbon::now()->addHours(8)->format('y') . substr(auth()->user()->project, 0, 3) . str_pad($payreq_project_count + 1, 3, '0', STR_PAD_LEFT);

        return $nomor;
    }

    public function generatePRNumber($payreq_id)
    {
        $payreq = Payreq::where('id', $payreq_id)->first();
        // $status_included = ['approved', 'split', 'paid', 'cancelled'];

        $payreq_project_count = Payreq::where('project', $payreq->project)
            ->whereYear('created_at', Carbon::now()->format('Y'))
            // ->whereIn('status', $status_included)
            ->count();
        $nomor = Carbon::now()->format('y') . substr(auth()->user()->project, 0, 3) . str_pad($payreq->id, 5, '0', STR_PAD_LEFT);
        // $nomor = Carbon::now()->format('y') . substr(auth()->user()->project, 0, 3) . str_pad($payreq_project_count + 1, 5, '0', STR_PAD_LEFT);

        return $nomor;
    }

    public function old_generateDraftNumber()
    {
        $status_include = ['draft', 'submitted', 'revised', 'approved'];
        $payreqs = Payreq::select('nomor')->where('project', auth()->user()->project)
            ->whereYear('created_at', Carbon::now()->format('Y'))
            ->whereIn('status', $status_include)
            ->get()
            ->toArray();

        // Convert each string to a number
        $numbers = array_map(function ($nomor) {
            return intval(substr($nomor, -3));
        }, $payreqs);

        if (empty($numbers)) {
            $numbers = [0];
        }

        // Find the highest number
        $highestNumber = max($numbers);

        $nomor = 'FQ' . Carbon::now()->addHours(8)->format('y') . substr(auth()->user()->project, 0, 3) . str_pad($highestNumber + 1, 3, '0', STR_PAD_LEFT);

        return $nomor;
    }
}
