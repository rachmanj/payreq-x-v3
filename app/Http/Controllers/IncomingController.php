<?php

namespace App\Http\Controllers;

use App\Models\Incoming;
use App\Models\Realization;
use Illuminate\Http\Request;

class IncomingController extends Controller
{
    public function store(Request $request)
    {
        // 
    }

    public function create_new_incomming_realization($realization_id)
    {
        $realization = Realization::findOrFail($realization_id);
        $payreq_amount = $realization->payreq->amount;
        $amount = $payreq_amount - $realization->realizationDetails->sum('amount');

        $incoming = Incoming::create([
            'realization_id' => $realization_id,
            'amount' => $amount,
            'description' => "Incoming from realization no. " . $realization->nomor,
            'project' => $realization->project,
        ]);

        return $incoming;
    }
}
