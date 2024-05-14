<?php

namespace App\Http\Controllers;

use App\Models\Payreq;
use App\Models\Realization;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index()
    {
        return view('search.index');
    }

    public function display(Request $request)
    {
        if (auth()->user()->project === "000H") {
            $payreqs = Payreq::where('nomor', 'like', '%' . $request->document_no . '%')->get();
            $realizations = Realization::where('nomor', 'like', '%' . $request->document_no . '%')->get();
        } else {
            $payreqs = Payreq::where('nomor', 'like', '%' . $request->document_no . '%')->where('project', auth()->user()->project)->get();
            $realizations = Realization::where('nomor', 'like', '%' . $request->document_no . '%')->where('project', auth()->user()->project)->get();
        }

        if ($payreqs->isNotEmpty()) {
            foreach ($payreqs as $payreq) {
                $payreq->document_type = 'payreq';
            }
        }

        if ($realizations->isNotEmpty()) {
            foreach ($realizations as $realization) {
                $realization->document_type = 'realization';
            }
        }

        return view('search.display', [
            'payreqs' => $payreqs,
            'realizations' => $realizations
        ]);
    }
}
