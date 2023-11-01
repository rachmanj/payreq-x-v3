<?php

namespace App\Http\Controllers;

use App\Models\Verification;
use Illuminate\Http\Request;

class VerificationJournalController extends Controller
{
    public function index()
    {
        $verifications = Verification::whereNull('sap_journal_no')
            ->whereNull('flag')
            ->where('project', auth()->user()->project)
            ->count();

        if ($verifications > 0) {
            $select_all_button = true;
        } else {
            $select_all_button = false;
        }

        $verifications_in_cart = Verification::where('flag', 'JTEMP' . auth()->user()->id)
            ->get();

        if ($verifications_in_cart->count() > 0) {
            $remove_all_button = true;
        } else {
            $remove_all_button = false;
        }

        return view('verifications.journal.create', compact([
            'select_all_button',
            'remove_all_button'
        ]));
    }

    public function add_to_cart(Request $request)
    {
        $flag = 'JTEMP' . auth()->user()->id; // JTEMP = Journal Temporary

        $verification = Verification::findOrFail($request->verification_id);
        $verification->flag = $flag;
        $verification->save();

        return redirect()->back();
    }

    public function remove_from_cart(Request $request)
    {
        $verification = Verification::findOrFail($request->verification_id);
        $verification->flag = null;
        $verification->save();

        return redirect()->back();
    }

    public function move_all_tocart()
    {
        $verifications = Verification::whereNull('sap_journal_no')
            ->whereNull('flag')
            ->where('project', auth()->user()->project)
            ->get();

        $flag = 'JTEMP' . auth()->user()->id; // JTEMP = Journal Temporary

        foreach ($verifications as $verification) {
            $verification->flag = $flag;
            $verification->save();
        }

        return redirect()->back();
    }

    public function remove_all_fromcart()
    {
        $flag = 'JTEMP' . auth()->user()->id;
        $verifications = Verification::where('flag', $flag)
            ->get();

        foreach ($verifications as $verification) {
            $verification->flag = null;
            $verification->save();
        }

        return redirect()->back();
    }

    public function tocart_data()
    {
        $verifications = Verification::where('project', auth()->user()->project)
            ->where('status', 'verified')
            ->whereNull('flag')
            ->get();

        return datatables()->of($verifications)
            ->addColumn('employee', function ($verification) {
                return $verification->realization->payreq->requestor->name;
            })
            ->addColumn('realization_no', function ($verification) {
                return $verification->realization->nomor;
            })
            ->addColumn('amount', function ($verification) {
                return number_format($verification->realization->realizationDetails->sum('amount'), 2);
            })
            ->addColumn('action', 'verifications.journal.tocart-action')
            ->addIndexColumn()
            ->toJson();
    }

    public function incart_data()
    {
        $flag = 'JTEMP' . auth()->user()->id; // JTEMP = Journal Temporary

        $verifications = Verification::where('project', auth()->user()->project)
            ->where('flag', $flag)
            ->get();

        return datatables()->of($verifications)
            ->addColumn('employee', function ($verification) {
                return $verification->realization->payreq->requestor->name;
            })
            ->addColumn('realization_no', function ($verification) {
                return $verification->realization->nomor;
            })
            ->addColumn('amount', function ($verification) {
                return number_format($verification->realization->realizationDetails->sum('amount'), 2);
            })
            ->addColumn('action', 'verifications.journal.incart-action')
            ->addIndexColumn()
            ->toJson();
    }
}
