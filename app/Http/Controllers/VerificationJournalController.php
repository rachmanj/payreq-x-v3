<?php

namespace App\Http\Controllers;

use App\Models\Realization;
use Illuminate\Http\Request;

class VerificationJournalController extends Controller
{
    public function index()
    {
        return "ninja";
    }

    public function create()
    {
        $realizations = Realization::whereNull('journal_id')
            ->whereNull('flag')
            ->where('project', auth()->user()->project)
            ->count();

        if ($realizations > 0) {
            $select_all_button = true;
        } else {
            $select_all_button = false;
        }

        $realizations_in_cart = Realization::where('flag', 'JTEMP' . auth()->user()->id)
            ->get();

        if ($realizations_in_cart->count() > 0) {
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

        $realization = Realization::findOrFail($request->realization_id);
        $realization->flag = $flag;
        $realization->save();

        return redirect()->back();
    }

    public function remove_from_cart(Request $request)
    {
        $realization = Realization::findOrFail($request->realization_id);
        $realization->flag = null;
        $realization->save();

        return redirect()->back();
    }

    public function move_all_tocart()
    {
        $realizations = Realization::whereNull('journal_id')
            ->whereNull('flag')
            ->where('project', auth()->user()->project)
            ->get();

        $flag = 'JTEMP' . auth()->user()->id; // JTEMP = Journal Temporary

        foreach ($realizations as $realization) {
            $realization->flag = $flag;
            $realization->save();
        }

        return redirect()->back();
    }

    public function remove_all_fromcart()
    {
        $flag = 'JTEMP' . auth()->user()->id;
        $realizations = Realization::where('flag', $flag)
            ->get();

        foreach ($realizations as $realization) {
            $realization->flag = null;
            $realization->save();
        }

        return redirect()->back();
    }

    public function tocart_data()
    {
        $realizations = Realization::where('project', auth()->user()->project)
            ->whereNull('journal_id')
            ->whereNull('flag')
            ->get();

        return datatables()->of($realizations)
            ->addColumn('employee', function ($realization) {
                return $realization->payreq->requestor->name;
            })
            ->addColumn('realization_no', function ($realization) {
                return $realization->nomor;
            })
            ->addColumn('amount', function ($realization) {
                return number_format($realization->realizationDetails->sum('amount'), 2);
            })
            ->addColumn('action', 'verifications.journal.tocart-action')
            ->addIndexColumn()
            ->toJson();
    }

    public function incart_data()
    {
        $flag = 'JTEMP' . auth()->user()->id; // JTEMP = Journal Temporary

        $realizations = Realization::where('project', auth()->user()->project)
            ->where('flag', $flag)
            ->get();

        return datatables()->of($realizations)
            ->addColumn('employee', function ($realization) {
                return $realization->payreq->requestor->name;
            })
            ->addColumn('realization_no', function ($realization) {
                return $realization->nomor;
            })
            ->addColumn('amount', function ($realization) {
                return number_format($realization->realizationDetails->sum('amount'), 2);
            })
            ->addColumn('action', 'verifications.journal.incart-action')
            ->addIndexColumn()
            ->toJson();
    }

    public function store(Request $request)
    {
        $realizations = Realization::where('flag', 'JTEMP' . auth()->user()->id)
            ->get();

        $realization_details_array = $this->group_and_sum_amount($realizations);

        $amounts = $this->sum_amount_by_department_id($realization_details_array);

        return $amounts;
    }

    public function group_and_sum_amount($realizations)
    {
        foreach ($realizations as $realization) {
            foreach ($realization->realizationDetails as $realization_detail) {
                $realization_details_array[] = [
                    'department_id' => $realization->department_id,
                    'account_id' => $realization_detail->account_id,
                    'amount' => $realization_detail->amount
                ];
            }
        }

        return $realization_details_array;
    }

    public function sum_amount_by_account_id($realization_details_array, $department_id, $account_id)
    {
        $amount = 0;

        foreach ($realization_details_array as $realization_detail) {
            foreach ($realization_detail as $key => $value) {
                if ($key == 'department_id' && $value == $department_id) {
                    if ($key == 'account_id' && $value == $account_id) {
                        $amount += $realization_detail['amount'];
                    }
                }
            }
        }

        return $amount;
    }
}
