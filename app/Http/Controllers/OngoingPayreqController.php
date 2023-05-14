<?php

namespace App\Http\Controllers;

use App\Models\Payreq;
use Illuminate\Http\Request;

class OngoingPayreqController extends Controller
{
    public function index()
    {
        return view('ongoings.index');
    }

    public function create_advance()
    {
        $payreq_no = app(PayreqController::class)->generateDraftNumber();

        return view('ongoings.create', compact('payreq_no'));
    }

    public function store_advance(Request $request)
    {
        $payreq = app(PayreqController::class)->store($request);

        $payreq->update([
            'type' => 'advance',

        ]);

        return redirect()->route('ongoings.index', $payreq->id)->with('success', 'Payreq Advance Draft created successfully.');
    }

    public function data()
    {
        $payreqs = Payreq::orderBy('created_at', 'desc')
            ->get();

        return datatables()->of($payreqs)
            ->editColumn('amount', function ($payreqs) {
                return number_format($payreqs->amount, 2);
            })
            ->editColumn('created_at', function ($payreqs) {
                return $payreqs->created_at->addHours(8)->format('d-M-Y H:i:s');
            })
            ->addIndexColumn()
            ->toJson();
    }
}
