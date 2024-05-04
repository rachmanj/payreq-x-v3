<?php

namespace App\Http\Controllers\Accounting;

use App\Exports\VerificationJournalExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\VerificationJournalController;
use App\Models\Account;
use App\Models\Realization;
use App\Models\VerificationJournal;
use App\Models\VerificationJournalDetail;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SapSyncController extends Controller
{
    public function index()
    {
        $project = request()->query('project');

        if ($project === '000H' || $project === 'APS') {
            return view('accounting.sap-sync.index');
        } else {
            return view('accounting.sap-sync.' . $project);
        }
    }

    public function show($id)
    {
        $vj = VerificationJournal::find($id);
        $vj_details = VerificationJournalDetail::where('verification_journal_id', $id)
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($detail) {
                $account = Account::where('account_number', $detail->account_code)->first();
                $detail->account_name = $account->account_name;
                return $detail;
            });

        // return $vj_details;

        return view('accounting.sap-sync.show', compact([
            'vj',
            'vj_details'
        ]));
    }

    public function update_sap_info(Request $request)
    {
        // update sap_journal_no and sap_posting_date on verification_journals table
        $verification_journal = VerificationJournal::find($request->verification_journal_id);
        $verification_journal->sap_journal_no = $request->sap_journal_no;
        $verification_journal->sap_posting_date = $request->sap_posting_date;
        $verification_journal->posted_by = auth()->user()->id;
        $verification_journal->save();

        // update sap_journal_no on verification_journal_details table
        $verification_journal_details = VerificationJournalDetail::where('verification_journal_id', $request->verification_journal_id)->get();
        foreach ($verification_journal_details as $detail) {
            $detail->sap_journal_no = $request->sap_journal_no;
            $detail->save();

            // update sap_balance on accounts table
            $account = Account::where('account_number', $detail->account_code)->first();
            $account->sap_balance = $account->sap_balance - $detail->amount;
            $account->save();
        }

        return redirect()->route('accounting.sap-sync.show', $request->verification_journal_id)->with('success', 'SAP Info Updated');
    }

    public function cancel_sap_info(Request $request)
    {
        // check if user is the one who posted the SAP Info
        $verification_journal = VerificationJournal::find($request->verification_journal_id);
        if ($verification_journal->posted_by != auth()->user()->id) {
            return redirect()->route('accounting.sap-sync.show', $request->verification_journal_id)->with('error', 'You are not allowed to cancel this SAP Info');
        }

        // update sap_journal_no and sap_posting_date on verification_journals table
        $verification_journal->sap_journal_no = null;
        $verification_journal->sap_posting_date = null;
        $verification_journal->posted_by = null;
        $verification_journal->save();

        // update sap_journal_no on verification_journal_details table
        $verification_journal_details = VerificationJournalDetail::where('verification_journal_id', $request->verification_journal_id)->get();
        foreach ($verification_journal_details as $detail) {
            $detail->sap_journal_no = null;
            $detail->save();
        }

        return redirect()->route('accounting.sap-sync.show', $request->verification_journal_id)->with('success', 'SAP Info Canceled');
    }

    public function data()
    {
        $query = request()->query('project');

        if ($query === 'HO') {
            $project = ['000H', 'APS'];
        } else {
            $project = [$query];
        }

        $verification_journals = VerificationJournal::whereIn('project', $project)
            ->orderByRaw('sap_journal_no IS NULL DESC')
            ->orderBy('date', 'desc')
            ->get();


        return datatables()->of($verification_journals)
            ->editColumn('date', function ($journal) {
                $date = new \Carbon\Carbon($journal->date);
                return $date->addHours(8)->format('d-M-Y');
            })
            ->addColumn('status', function ($journal) {
                if ($journal->sap_journal_no == null) {
                    return '<span class="badge badge-danger">Not Posted Yet</span>';
                } else {
                    return '<span class="badge badge-success">Posted</span>';
                }
            })
            ->editColumn('amount', function ($journal) {
                return number_format($journal->amount, 2);
            })
            ->editColumn('sap_posting_date', function ($journal) {
                if ($journal->sap_posting_date == null) {
                    return '-';
                } else {
                    $date = new \Carbon\Carbon($journal->sap_posting_date);
                    return $date->addHours(8)->format('d-M-Y');
                }
            })
            ->addIndexColumn()
            ->addColumn('action', 'accounting.sap-sync.action')
            ->rawColumns(['status', 'action'])
            ->toJson();
    }

    public function export()
    {
        $vj_id = request()->query('vj_id');

        $journal_details = VerificationJournalDetail::select(
            'verification_journal_id',
            'account_code',
            'project',
            'realization_date',
            'debit_credit',
            'description',
            'cost_center',
            'amount',
            'realization_no'
        )->where('verification_journal_id', $vj_id)->get();

        // add payreq number to journal_details
        foreach ($journal_details as $detail) {
            $realization = Realization::where('nomor', $detail->realization_no)->first();
            $payreq_no = $realization->payreq()->first()->nomor;
            $detail->payreq_no = $payreq_no;
            $detail->vj_no = VerificationJournal::where('id', $detail->verification_journal_id)->first()->nomor;
        }

        return $journal_details;

        // return Excel::download(new VerificationJournalExport($journal_details), 'journal.xlsx');
    }
}
