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

    public function data()
    {
        $status_include = ['approved'];
        $realizations = Realization::whereDate('due_date', '<=', now())
            ->whereIn('status', $status_include)
            ->get();

        return datatables()->of($realizations)
            ->addColumn(('employee'), function ($realization) {
                return $realization->requestor->name;
            })
            ->editColumn('nomor', function ($realization) {
                return '<a href="#" style="color: black" title="' . $realization->payreq->remarks . '">' . $realization->nomor . '</a>';
            })
            ->editColumn('amount', function ($realization) {
                return number_format($realization->realizationDetails->sum('amount'), 2);
            })
            ->editColumn('status', function ($realization) {
                return ucfirst($realization->status);
            })
            ->addColumn('dfa', function ($realization) {
                // Days from approved date
                return Carbon::parse($realization->approved_at)->diffInDays(now());
            })
            ->addColumn('dfd', function ($realization) {
                return Carbon::parse($realization->due_date)->diffInDays(now()); // Days from due date
            })
            ->addIndexColumn()
            ->addColumn('action', 'document-overdue.realization.action')
            ->rawColumns(['nomor', 'action'])
            ->toJson();
    }
}
