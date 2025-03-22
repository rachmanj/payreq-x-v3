<?php

namespace App\Http\Controllers;

use App\Models\Realization;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RealizationOverdueController extends Controller
{
    public function index()
    {
        return view('document-overdue.realization.index');
    }

    public function extend(Request $request)
    {
        $realization = Realization::find($request->realization_id);
        $realization->due_date = $request->new_due_date;
        $realization->save();

        return redirect()->route('document-overdue.realization.index')->with('success', 'Realization extended successfully.');
    }

    public function bulkExtend(Request $request)
    {
        $request->validate([
            'realization_ids' => 'required|array',
            'realization_ids.*' => 'exists:realizations,id',
            'new_due_date' => 'required|date'
        ]);

        $count = Realization::whereIn('id', $request->realization_ids)
            ->update(['due_date' => $request->new_due_date]);

        return redirect()->route('document-overdue.realization.index')
            ->with('success', $count . ' realizations have been updated successfully.');
    }

    public function data()
    {
        $status_include = ['approved'];
        $realizations = Realization::whereDate('due_date', '<=', now())
            ->whereIn('status', $status_include)
            ->get();

        return datatables()->of($realizations)
            ->addColumn('checkbox', function ($realization) {
                return '<input type="checkbox" name="realization_ids[]" class="realization-checkbox" value="' . $realization->id . '">';
            })
            ->addColumn(('employee'), function ($realization) {
                return $realization->requestor->name;
            })
            ->editColumn('nomor', function ($realization) {
                return '<a href="#" style="color: black" title="' . $realization->remarks . '">' . $realization->nomor . '</a>';
            })
            ->addColumn('dfa', function ($realization) {
                return Carbon::parse($realization->approved_date)->diffInDays(now()); // Days from approved date
            })
            ->addColumn('dfd', function ($realization) {
                return Carbon::parse($realization->due_date)->diffInDays(now()); // Days from due date
            })
            ->editColumn('amount', function ($realization) {
                return number_format($realization->realizationDetails->sum('amount'), 2);
            })
            ->addIndexColumn()
            ->addColumn('action', 'document-overdue.realization.action')
            ->rawColumns(['action', 'nomor', 'checkbox'])
            ->toJson();
    }
}
