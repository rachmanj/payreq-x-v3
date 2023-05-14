<?php

namespace App\Http\Controllers;

use App\Models\Payreq;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PayreqController extends Controller
{
    public function store($data)
    {
        $validated = $data->validate([
            'amount' => 'required|numeric',
            'remarks' => 'required',
        ]);

        $payreq = Payreq::create(array_merge($validated, [
            'project' => auth()->user()->project,
            'status' => 'draft',
            'payreq_no' => $this->generateDraftNumber(),
            'user_id' => auth()->user()->id,
        ]));

        return $payreq;
    }

    public function generateDraftNumber()
    {
        $payreq_project_count = Payreq::where('project', auth()->user()->project)
            ->where('status', 'draft')
            ->count();
        $nomor = 'FQ' . Carbon::now()->addHours(8)->format('y') . auth()->user()->project . str_pad($payreq_project_count + 1, 3, '0', STR_PAD_LEFT);

        return $nomor;
    }

    public function generatePRNumber()
    {
        $payreq_project_count = Payreq::where('project', auth()->user()->project)
            ->where('status', '<>', 'draft')
            ->count();
        $nomor = Carbon::now()->format('y') . auth()->user()->project . str_pad($payreq_project_count + 1, 3, '0', STR_PAD_LEFT);

        return $nomor;
    }
}
