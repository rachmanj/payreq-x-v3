<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Realization;
use App\Models\Verification;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function index()
    {
        $realizations = Realization::where('status', 'approved')
            ->where('project', auth()->user()->project)
            ->whereDoesntHave('verifications')
            ->get();

        return view('verifications.index', compact('realizations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'realization_id' => 'required',
            'date' => 'required',
        ]);

        $verification = Verification::create([
            'realization_id' => $request->realization_id,
            'date' => $request->date,
            'flag' => 'vertemp' . auth()->user()->id,   //verification temporary
            'user_id' => auth()->user()->id,
        ]);

        $realization = Realization::findOrFail($request->realization_id);
        $realization_details = $realization->realizationDetails;
        $accounts = Account::orderBy('account_number')->get();

        return view('verifications.details', compact([
            'verification',
            'realization',
            'realization_details',
            'accounts'
        ]));
    }

    public function data()
    {
        $verifications = Realization::where('status', 'verified')->get();

        return datatables()->of($verifications)
            ->addColumn('realization_no', function ($verification) {
                return $verification->realization_details->realization->nomor;
            })
            ->addColumn('action', function ($verification) {
                return view('verifications.action', compact('verification'));
            })
            ->rawColumns(['action', 'nomor'])
            ->addIndexColumn()
            ->toJson();
    }
}
