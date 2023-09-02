<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\CashJournal;
use App\Models\GeneralLedger;
use App\Models\Outgoing;
use Illuminate\Http\Request;

class CashJournalController extends Controller
{
    public function index()
    {
        $outgoings_count = Outgoing::whereNull('cash_journal_id')
            ->where('project', auth()->user()->project)
            ->count();

        $incomings_count = 2;

        return view('cash-journal.index', compact(['outgoings_count', 'incomings_count']));
    }

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

        return view('cash-journal.create', compact(['select_all_button', 'remove_all_button']));
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

    public function show($id)
    {
        $cash_journal = CashJournal::find($id);
        $outgoings = Outgoing::where('cash_journal_id', $id)->get();
        $advance_account = Account::where('type_id', 5)->where('project', auth()->user()->project)->first();
        $pc_account = Account::where('type_id', 2)->where('project', auth()->user()->project)->first();

        return view('cash-journal.show', compact(['cash_journal', 'outgoings', 'advance_account', 'pc_account']));
    }

    public function delete_detail($outgoing_id)
    {
        $outgoing = Outgoing::find($outgoing_id);
        $outgoing->cash_journal_id = null;
        $outgoing->save();

        return redirect()->back();
    }

    // delete cash journal record & update outgoing record
    public function destroy($id)
    {
        $cash_journal = CashJournal::find($id);
        $outgoings = Outgoing::where('cash_journal_id', $id)->get();

        foreach ($outgoings as $outgoing) {
            $outgoing->cash_journal_id = null;
            $outgoing->save();
        }

        $cash_journal->delete();

        return redirect()->route('cash-journals.index')->with('success', 'Cash Journal deleted successfully.');
    }

    public function print($id)
    {
        $journal = CashJournal::find($id);

        $journal['type'] = $journal->type == 'cash-out' ? 'Cash Out' : 'Cash In';
        $outgoings = Outgoing::where('cash_journal_id', $id)->get();
        $advance_account = '122222 - Employee Cash Advance 000H';
        $pc_account = '11111111 - PC Site 000H';

        return view('cash-journal.print_pdf', compact(['journal', 'outgoings', 'advance_account', 'pc_account']));
    }

    public function update_sap(Request $request)
    {
        $request->validate([
            'sap_journal_no' => 'required',
        ]);

        $cash_journal = CashJournal::find($request->cash_journal_id);
        $cash_journal->sap_journal_no = $request->sap_journal_no;
        $cash_journal->sap_posting_date = $request->sap_posting_date;
        $cash_journal->save();

        // update sap_journal_no in outgoings table
        $outgoings = Outgoing::where('cash_journal_id', $request->cash_journal_id)->get();
        foreach ($outgoings as $outgoing) {
            $outgoing->sap_journal_no = $request->sap_journal_no;
            $outgoing->save();
        }

        // create record in general_ledgers table
        $account_type_include = [2, 5]; // cash & advance yaitu akun2 yg terpengaruh dgn transaksi ini
        $accounts = Account::whereIn('type_id', $account_type_include)
            ->where('project', auth()->user()->project)
            ->get();

        foreach ($accounts as $account) {
            app(GeneralLedgerController::class)->store($account, $cash_journal);
        }

        return redirect()->back()->with('success', 'Cash Journal updated successfully.');
    }

    public function cancel_sap_info(Request $request)
    {
        $cash_journal = CashJournal::find($request->cash_journal_id);
        $cash_journal->sap_journal_no = null;
        $cash_journal->sap_posting_date = null;
        $cash_journal->save();

        // update sap_journal_no in outgoings table
        $outgoings = Outgoing::where('cash_journal_id', $request->cash_journal_id)->get();
        foreach ($outgoings as $outgoing) {
            $outgoing->sap_journal_no = null;
            $outgoing->save();
        }

        // delete record in general_ledgers table
        $general_ledgers = GeneralLedger::where('journal_no', $cash_journal->journal_no)->get();

        foreach ($general_ledgers as $general_ledger) {
            app(GeneralLedgerController::class)->delete($general_ledger);
        }

        return redirect()->back()->with('success', 'Cash Journal Cancel updated successfully.');
    }

    public function add_to_cart(Request $request)
    {
        $outgoing = Outgoing::find($request->outgoing_id);
        $outgoing->flag = 'CJT' . auth()->user()->id; // CJT = Cash Journal Temporary
        $outgoing->save();

        return redirect()->back();
    }

    public function remove_from_cart(Request $request)
    {
        $outgoing = Outgoing::find($request->outgoing_id);
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

    public function data()
    {
        $cash_journals = CashJournal::where('project', auth()->user()->project)
            ->orderBy('date', 'desc')
            ->get();

        return datatables()->of($cash_journals)
            ->editColumn('date', function ($cash_journal) {
                $date = new \Carbon\Carbon($cash_journal->date);
                return $date->addHours(8)->format('d-M-Y');
            })
            ->addColumn('status', function ($cash_journal) {
                if ($cash_journal->sap_journal_no == null) {
                    return '<span class="badge badge-danger">Not Posted Yet</span>';
                } else {
                    return '<span class="badge badge-success">Posted</span>';
                }
            })
            ->editColumn('amount', function ($cash_journal) {
                return number_format($cash_journal->amount, 2);
            })
            ->addIndexColumn()
            ->addColumn('action', 'cash-journal.action')
            ->rawColumns(['status', 'action'])
            ->toJson();
    }

    public function to_cart_data()
    {
        $outgoings = Outgoing::whereNull('cash_journal_id')
            ->whereNull('flag')
            ->where('project', auth()->user()->project)
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
            ->addColumn('action', 'cash-journal.to-cart-action')
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
            ->addColumn('action', 'cash-journal.in-cart-action')
            ->toJson();
    }
}
