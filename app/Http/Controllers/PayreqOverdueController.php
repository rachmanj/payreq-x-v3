<?php

namespace App\Http\Controllers;

use App\Models\Payreq;
use App\Support\AdvancePayreqOverdueRules;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PayreqOverdueController extends Controller
{
    public function index()
    {
        return view('document-overdue.payreq.index');
    }

    public function extend(Request $request)
    {
        $payreq = Payreq::query()->findOrFail($request->payreq_id);

        if (! AdvancePayreqOverdueRules::isAdvanceStillOverdue($payreq)) {
            return redirect()->route('document-overdue.payreq.index')
                ->with('error', 'Payment request is not eligible for extension (not overdue or realization already finished).');
        }

        $payreq->due_date = $request->new_due_date;
        $payreq->save();

        return redirect()->route('document-overdue.payreq.index')->with('success', 'Payreq extended successfully.');
    }

    public function bulkExtend(Request $request)
    {
        $request->validate([
            'payreq_ids' => 'required|array',
            'payreq_ids.*' => 'exists:payreqs,id',
            'new_due_date' => 'required|date',
        ]);

        $eligibleIds = Payreq::query()
            ->whereIn('id', $request->payreq_ids)
            ->advanceStillOverdue()
            ->pluck('id');

        $count = Payreq::query()
            ->whereIn('id', $eligibleIds)
            ->update(['due_date' => $request->new_due_date]);

        return redirect()->route('document-overdue.payreq.index')
            ->with('success', $count.' payment requests have been updated successfully.');
    }

    public function data()
    {
        $payreqs = Payreq::query()
            ->advanceStillOverdue()
            ->get();

        return datatables()->of($payreqs)
            ->addColumn('checkbox', function ($payreq) {
                if (! auth()->user()?->can('approve_overdue_extension')) {
                    return '';
                }

                return '<input type="checkbox" name="payreq_ids[]" form="bulk-update-form" class="payreq-checkbox" value="'.$payreq->id.'">';
            })
            ->addColumn(('employee'), function ($payreq) {
                return $payreq->requestor->name;
            })
            ->editColumn('nomor', function ($approved) {
                return '<a href="#" style="color: black" title="'.$approved->remarks.'">'.$approved->nomor.'</a>';
            })
            ->addColumn('dfp', function ($payreq) {
                $last_outgoing = $payreq->outgoings->last();

                return Carbon::parse($last_outgoing->outgoing_date)->diffInDays(now()); // Days from paid date
            })
            ->addColumn('dfd', function ($payreq) {
                return Carbon::parse($payreq->due_date)->diffInDays(now()); // Days from due date
            })
            ->editColumn('amount', function ($payreq) {
                return number_format($payreq->amount, 2);
            })
            ->addIndexColumn()
            ->addColumn('action', 'document-overdue.payreq.action')
            ->rawColumns(['action', 'nomor', 'checkbox'])
            ->toJson();
    }
}
