<?php

namespace App\Http\Controllers\Accounting;

use App\Exports\VerificationJournalExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\VerificationJournalController;
use App\Models\VerificationJournal;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SapSyncController extends Controller
{
    public function index()
    {
        $project = request()->query('project');

        if ($project === 'HO') {
            return view('accounting.sap-sync.index');
        } else {
            return view('accounting.sap-sync.' . $project);
        }
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

        $journal_details = app(VerificationJournalController::class)->journal_details($vj_id);

        return Excel::download(new VerificationJournalExport($journal_details), 'journal.xlsx');
    }
}
