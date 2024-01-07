<?php

namespace App\Http\Controllers;

use App\Models\CashJournal;
use App\Models\Outgoing;
use Illuminate\Http\Request;

class CashOutJournalController extends Controller
{
    public function create()
    {
        $outgoings = Outgoing::whereNull('cash_journal_id')
            ->whereNull('flag')
            ->where('project', auth()->user()->project)
            ->count();

        if ($outgoings > 0) {
            $select_all_button = true;
        } else {
            $select_all_button = false;
        }

        $outgoings_in_cart = Outgoing::where('flag', 'CJT' . auth()->user()->id)
            ->get();

        if ($outgoings_in_cart->count() > 0) {
            $remove_all_button = true;
        } else {
            $remove_all_button = false;
        }

        return view('cash-journal.out.create', compact(['select_all_button', 'remove_all_button']));
    }

    public function store(Request $request)
    {
        $outgoings = Outgoing::where('flag', 'CJT' . auth()->user()->id)
            ->get();

        $cash_journal = new CashJournal();
        $cash_journal->date = $request->date;
        $cash_journal->type = "cash-out";
        $cash_journal->amount = $outgoings->sum('amount');
        $cash_journal->description = $request->description;
        $cash_journal->project = auth()->user()->project;
        $cash_journal->created_by = auth()->user()->id;
        $cash_journal->save();

        // update cash journal number
        $cash_journal->journal_no = app(ToolController::class)->generateCashJournalNumber($cash_journal->id, 'cash-out');
        $cash_journal->save();

        // update outgoings cash journal id
        foreach ($outgoings as $outgoing) {
            $outgoing->cash_journal_id = $cash_journal->id;
            $outgoing->flag = null;
            $outgoing->save();
        }

        return redirect()->route('cash-journals.index')->with('success', 'Cash Journal created successfully.');
    }

    public function add_to_cart(Request $request)
    {
        $outgoing = Outgoing::findOrFail($request->outgoing_id);
        $outgoing->flag = 'CJT' . auth()->user()->id; // CJT = Cash Journal Temporary
        $outgoing->save();

        return redirect()->back();
    }

    public function remove_from_cart(Request $request)
    {
        $outgoing = Outgoing::findOrFail($request->outgoing_id);
        $outgoing->flag = null;
        $outgoing->save();

        return redirect()->back();
    }

    public function move_all_tocart()
    {
        $outgoings = Outgoing::whereNull('cash_journal_id')
            ->whereNull('flag')
            ->where('project', auth()->user()->project)
            ->get();

        foreach ($outgoings as $outgoing) {
            $outgoing->flag = 'CJT' . auth()->user()->id; // CJT = Cash Journal Temporary
            $outgoing->save();
        }

        return redirect()->back();
    }

    public function remove_all_fromcart()
    {
        $outgoings = Outgoing::where('flag', 'CJT' . auth()->user()->id)
            ->get();

        foreach ($outgoings as $outgoing) {
            $outgoing->flag = null;
            $outgoing->save();
        }

        return redirect()->back();
    }

    public function to_cart_data()
    {
        if (auth()->user()->hasRole(['superadmin', 'admin', 'cashier'])) {
            $project_include = ['000H', 'APS'];
        } else {
            $project_include = explode(',', auth()->user()->project);
        }

        $outgoings = Outgoing::whereNull('cash_journal_id')
            ->whereNull('flag')
            ->whereIn('project', $project_include)
            ->where('will_post', 1)
            ->whereNotNull('outgoing_date')
            ->get();

        return datatables()->of($outgoings)
            ->addColumn('payreq_no', function ($outgoing) {
                return $outgoing->payreq->nomor;
            })
            ->addColumn('amount', function ($outgoing) {
                return number_format($outgoing->amount, 2);
            })
            ->editColumn('outgoing_date', function ($outgoing) {
                $date = new \Carbon\Carbon($outgoing->outgoing_date);
                return $date->addHours(8)->format('d-M-Y');
            })
            ->addIndexColumn()
            ->addColumn('action', 'cash-journal.out.to-cart-action')
            ->toJson();
    }

    public function in_cart_data()
    {
        $outgoings = Outgoing::where('flag', 'CJT' . auth()->user()->id)
            ->get();

        return datatables()->of($outgoings)
            ->addColumn('payreq_no', function ($outgoing) {
                return $outgoing->payreq->nomor;
            })
            ->addColumn('amount', function ($outgoing) {
                return number_format($outgoing->amount, 2);
            })
            ->editColumn('outgoing_date', function ($outgoing) {
                $date = new \Carbon\Carbon($outgoing->outgoing_date);
                return $date->addHours(8)->format('d-M-Y');
            })
            ->addIndexColumn()
            ->addColumn('action', 'cash-journal.out.in-cart-action')
            ->toJson();
    }
}
