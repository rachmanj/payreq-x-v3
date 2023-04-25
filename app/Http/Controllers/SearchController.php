<?php

namespace App\Http\Controllers;

use App\Models\AdvanceCategory;
use App\Models\Payreq;
use App\Models\Rab;
use App\Models\User;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index()
    {
        return view('search.index');
    }

    public function display(Request $request)
    {
        $payreq = Payreq::where('payreq_num', $request->payreq_no)->first();

        return view('search.display', compact('payreq'));
    }

    public function edit($id)
    {
        $payreq = Payreq::find($id);
        $employees = User::where('is_active', 1)->orderBy('name', 'asc')->get();
        $rabs = Rab::where('status', 'progress')->orderBy('rab_no', 'asc')->get();
        $adv_categories = AdvanceCategory::orderBy('code', 'asc')->get();

        // $month_ofr = date('Y-m', strtotime($payreq->periode_ofr));
        // return $month_ofr;
        // die;

        return view('search.edit', compact('payreq', 'employees', 'rabs', 'adv_categories'));
    }

    public function update(Request $request, $id)
    {
        // return $request->all();
        // die;
        $this->validate($request, [
            'user_id' => 'required',
            'payreq_num' => 'required|unique:payreqs,payreq_num,' . $id,
            'approve_date' => 'required',
            'payreq_type' => 'required',
            'payreq_idr' => 'required',
            // 'realization_num' => 'unique:payreqs,realization_num,' . $id,
        ]);

        $payreq = Payreq::findOrFail($id);
        $payreq->user_id = $request->user_id;
        $payreq->payreq_num = $request->payreq_num;
        $payreq->approve_date = $request->approve_date;
        $payreq->payreq_type = $request->payreq_type;
        $payreq->que_group = $request->que_group;
        $payreq->payreq_idr = $request->payreq_idr;
        $payreq->advance_category_id = $request->advance_category_id;
        $payreq->outgoing_date = $request->outgoing_date;
        $payreq->realization_date = $request->realization_date;
        $payreq->realization_num = $request->realization_num;
        $payreq->realization_amount = $request->realization_amount;
        $payreq->verify_date = $request->verify_date;
        $payreq->rab_id = $request->rab_id;

        if ($request->budgeted == 0) {
            $payreq->budgeted = 0;
            $payreq->periode_ofr = null;
        } else {
            $payreq->budgeted = 1;
            if ($request->periode_ofr) {
                $payreq->periode_ofr = $request->periode_ofr . '-01';
            } else {
                $payreq->periode_ofr = date('Y-m-d');
            }
        }
        $payreq->remarks = $request->remarks;

        $payreq->save();

        return redirect()->route('search.index')->with('success', 'Payment Request updated');
    }

    public function destroy($id)
    {
        $payreq = Payreq::findOrFail($id);
        $payreq->delete();

        return redirect()->route('search.index')->with('success', 'Payment Request deleted');
    }
}
