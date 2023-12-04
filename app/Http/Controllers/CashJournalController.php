<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\CashJournal;
use App\Models\GeneralLedger;
use App\Models\Incoming;
use App\Models\Outgoing;
use Illuminate\Http\Request;

class CashJournalController extends Controller
{
    public function index()
    {
        $cj_count = $this->cj_to_create();

        return view('cash-journal.index', compact(['cj_count']));
    }

    public function show($id)
    {
        $cash_journal = CashJournal::find($id);


        if ($cash_journal->type === 'cash-out') {
            $outgoings = Outgoing::where('cash_journal_id', $id)->get();
            $debet_account = Account::where('type', 'advance')->where('project', auth()->user()->project)->first();
            $credit_account = Account::where('type', 'cash')->where('project', auth()->user()->project)->first();
            return view('cash-journal.show_cash_out', compact(['cash_journal', 'outgoings', 'debet_account', 'credit_account']));
        } else {
            $incomings = Incoming::where('cash_journal_id', $id)->get();
            $debet_account = Account::where('type', 'cash')->where('project', auth()->user()->project)->first();
            $credit_account = Account::where('type', 'advance')->where('project', auth()->user()->project)->first();
            return view('cash-journal.show_cash_in', compact(['cash_journal', 'incomings', 'debet_account', 'credit_account']));
        }
    }

    public function delete_detail($outgoing_id)
    {
        $outgoing = Outgoing::find($outgoing_id);
        $outgoing->cash_journal_id = null;
        $outgoing->save();

        return redirect()->back();
    }

    // delete cash journal record & update outgoing / incoming record
    public function destroy($id)
    {
        $cash_journal = CashJournal::find($id);

        if ($cash_journal->type === 'cash-out') {
            $outgoings = Outgoing::where('cash_journal_id', $id)->get();

            foreach ($outgoings as $outgoing) {
                $outgoing->cash_journal_id = null;
                $outgoing->save();
            }
        } else {
            $incomings = Incoming::where('cash_journal_id', $id)->get();

            foreach ($incomings as $incoming) {
                $incoming->cash_journal_id = null;
                $incoming->save();
            }
        }

        $cash_journal->delete();

        return redirect()->route('cash-journals.index')->with('success', 'Cash Journal deleted successfully.');
    }

    public function print($id)
    {
        $journal = CashJournal::find($id);

        if ($journal->type === 'cash-out') {
            $outgoings = Outgoing::where('cash_journal_id', $id)->get();
            $debet_account = Account::where('type', 'advance')->where('project', auth()->user()->project)->first();
            $credit_account = Account::where('type', 'cash')->where('project', auth()->user()->project)->first();

            return view('cash-journal.print_cash_out', compact(['journal', 'outgoings', 'debet_account', 'credit_account']));
        } else {
            $incomings = Incoming::where('cash_journal_id', $id)->get();
            $debet_account = Account::where('type', 'cash')->where('project', auth()->user()->project)->first();
            $credit_account = Account::where('type', 'advance')->where('project', auth()->user()->project)->first();

            return view('cash-journal.print_cash_in', compact(['journal', 'incomings', 'debet_account', 'credit_account']));
        }
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

        if ($cash_journal->type === 'cash-out') {
            // update sap_journal_no in outgoings table
            $outgoings = Outgoing::where('cash_journal_id', $request->cash_journal_id)->get();
            foreach ($outgoings as $outgoing) {
                $outgoing->sap_journal_no = $request->sap_journal_no;
                $outgoing->save();
            }
        } else {
            // update sap_journal_no in incomings table
            $incomings = Incoming::where('cash_journal_id', $request->cash_journal_id)->get();
            foreach ($incomings as $incoming) {
                $incoming->sap_journal_no = $request->sap_journal_no;
                $incoming->save();
            }
        }

        // create record in general_ledgers table
        $account_type_include = ['cash', 'advance']; // cash & advance yaitu akun2 yg terpengaruh dgn transaksi ini
        $accounts = Account::whereIn('type', $account_type_include)
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

    public function cj_to_create() // cash journal to create
    {
        $result['outgoings_count'] = Outgoing::whereNull('cash_journal_id')
            ->where('project', auth()->user()->project)
            ->count();

        $result['outgoings_amount'] = Outgoing::whereNull('cash_journal_id')
            ->where('project', auth()->user()->project)
            ->sum('amount');

        $result['incomings_count'] = app(CashInJournalController::class)->incoming_will_post()->count();

        $result['incomings_amount'] = app(CashInJournalController::class)->incoming_will_post()->sum('amount');

        $result['pending_posting_count'] = CashJournal::where('project', auth()->user()->project)
            ->where('sap_journal_no', null)
            ->count();

        $result['pending_posting_amount'] = CashJournal::where('project', auth()->user()->project)
            ->where('sap_journal_no', null)
            ->sum('amount');

        return $result;
    }
}
