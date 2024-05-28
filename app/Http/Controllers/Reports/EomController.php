<?php

namespace App\Http\Controllers\Reports;

use App\Exports\EomJournalExport;
use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class EomController extends Controller
{
    public function index()
    {
        $journal = $this->eom_journal();

        return view('reports.eom.index', compact([
            'journal'
        ]));
    }

    public function eom_journal()
    {
        $projects = ['000H', '001H', '017C', '021C', '022C', '023C'];

        foreach ($projects as $project) {
            $journal[] = [
                'debit' => [
                    'account_number' => Account::where('type', 'advance')->where('project', $project)->first()->account_number,
                    'account_name' => Account::where('type', 'advance')->where('project', $project)->first()->account_name,
                    'description' => 'EOM ' . date('dmY') . ' Journal',
                    'project_code' => $project,
                    'ccenter' => '30',
                    'amount' => app(OngoingDashboardController::class)->dashboard_data($project)['total_advance_employee'],
                ],
                'credit' => [
                    'account_number' => Account::where('type', 'cash')->where('project', $project)->first()->account_number,
                    'account_name' => Account::where('type', 'advance')->where('project', $project)->first()->account_name,
                    'description' => 'EOM ' . date('dmY') . ' Journal',
                    'project_code' => $project,
                    'ccenter' => '30',
                    'amount' => app(OngoingDashboardController::class)->dashboard_data($project)['total_advance_employee'],
                ],
            ];
        }

        return $journal;
    }

    public function export()
    {
        $eom_journal = $this->eom_journal();

        return Excel::download(new EomJournalExport($eom_journal), 'eom_journal.xlsx');
    }
}
