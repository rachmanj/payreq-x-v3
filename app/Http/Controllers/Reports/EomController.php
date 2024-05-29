<?php

namespace App\Http\Controllers\Reports;

use App\Exports\EomJournalExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DocumentNumberController;
use App\Models\Account;
use App\Models\EomJournal;
use App\Models\EomJournalDetail;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class EomController extends Controller
{
    public function index()
    {
        $projects = ['000H', '001H', '017C', '021C', '022C', '023C'];

        return view('reports.eom.index', compact('projects'));
    }

    public function store(Request $request)
    {
        // return $request->all();
        $eom_journal = EomJournal::create([
            'date' => $request->date,
            'nomor' => app(DocumentNumberController::class)->generate_document_number('eom-journal', auth()->user()->project),
            'description' => $request->description,
            'created_by' => auth()->user()->id,
            'project' => auth()->user()->project,
        ]);

        $eom_journal_details = $this->eom_journal($request->projects);

        foreach ($eom_journal_details as $detail) {
            $eom_journal->eomJournalDetails()->create([
                'account_number' => $detail['debit']['account_number'],
                'posting_date' =>  $eom_journal->date,
                'description' => $detail['debit']['description'],
                'project' => $detail['debit']['project_code'],
                'cost_center' => $detail['debit']['cost_center'],
                'amount' => str_replace(',', '', $detail['debit']['amount']),
                'd_c' => 'debit',
            ]);

            $eom_journal->eomJournalDetails()->create([
                'account_number' => $detail['credit']['account_number'],
                'posting_date' => $eom_journal->date,
                'description' => $detail['credit']['description'],
                'project' => $detail['credit']['project_code'],
                'cost_center' => $detail['credit']['cost_center'],
                'amount' => str_replace(',', '', $detail['credit']['amount']),
                'd_c' => 'credit',
            ]);
        }

        // update eom_journal with total amount
        $eom_journal->update([
            'amount' => $eom_journal->eomJournalDetails->where('d_c', 'debit')->sum('amount'),
        ]);

        return redirect()->route('reports.eom.index')->with('success', 'EOM Journal created successfully.');
    }

    public function eom_journal($projects)
    {
        // $projects = ['000H', '001H', '017C', '021C', '022C', '023C'];

        foreach ($projects as $project) {
            $journal[] = [
                'debit' => [
                    'account_number' => Account::where('type', 'advance')->where('project', $project)->first()->account_number,
                    'account_name' => Account::where('type', 'advance')->where('project', $project)->first()->account_name,
                    'description' => 'EOM ' . date('dmY') . ' Journal',
                    'project_code' => $project,
                    'cost_center' => '30',
                    'amount' => app(OngoingDashboardController::class)->dashboard_data($project)['total_advance_employee'],
                ],
                'credit' => [
                    'account_number' => Account::where('type', 'cash')->where('project', $project)->first()->account_number,
                    'account_name' => Account::where('type', 'advance')->where('project', $project)->first()->account_name,
                    'description' => 'EOM ' . date('dmY') . ' Journal',
                    'project_code' => $project,
                    'cost_center' => '30',
                    'amount' => app(OngoingDashboardController::class)->dashboard_data($project)['total_advance_employee'],
                ],
            ];
        }

        return $journal;
    }

    public function show($id)
    {
        $journal = EomJournal::with('EomJournalDetails')->find($id);
        $journal_details = $journal->eomJournalDetails;

        return view('reports.eom.show', compact(['journal', 'journal_details']));
    }

    public function export()
    {
        $eom_journal_id = request()->query('eom_journal_id');

        $eom_journal_details = EomJournalDetail::select(
            "id",
            "eom_journal_id",
            "posting_date",
            "account_number",
            "d_c",
            "description",
            "project",
            "cost_center",
            "amount",
        )->where('eom_journal_id', $eom_journal_id)->get();

        return Excel::download(new EomJournalExport($eom_journal_details), 'eom_journal.xlsx');
    }

    public function data()
    {
        $eom_journals = EomJournal::orderBy('created_at', 'desc')->get();

        return datatables()->of($eom_journals)
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
            ->addColumn('projects', function ($journal) {
                $projects = $journal->eomJournalDetails->pluck('project')->unique()->toArray();
                return '<small><b>' . implode(', ', $projects) . '</b></small>';
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
            ->addColumn('action', 'reports.eom.action')
            ->rawColumns(['status', 'action', 'projects'])
            ->toJson();
    }

    public function update_sap_info(Request $request)
    {
        // update sap_journal_no and sap_posting_date on verification_journals table
        $eom_journal = EomJournal::find($request->eom_journal_id);
        $eom_journal->sap_journal_no = $request->sap_journal_no;
        $eom_journal->sap_posting_date = $request->sap_posting_date;
        $eom_journal->posted_by = auth()->user()->id;
        $eom_journal->save();

        return redirect()->route('reports.eom.show', $request->eom_journal_id)->with('success', 'SAP Info Updated');
    }

    public function cancel_sap_info(Request $request)
    {
        // cek if user is authorized to cancel sap info
        $eom_journal = EomJournal::find($request->eom_journal_id);
        if ($eom_journal->posted_by != auth()->user()->id) {
            return redirect()->route('reports.eom.show', $request->eom_journal_id)->with('error', 'You are not authorized to cancel SAP Info');
        }

        // update sap_journal_no and sap_posting_date on verification_journals table
        $eom_journal = EomJournal::find($request->eom_journal_id);
        $eom_journal->sap_journal_no = null;
        $eom_journal->sap_posting_date = null;
        $eom_journal->posted_by = null;
        $eom_journal->save();

        return redirect()->route('reports.eom.show', $request->eom_journal_id)->with('success', 'SAP Info Cancelled');
    }
}
