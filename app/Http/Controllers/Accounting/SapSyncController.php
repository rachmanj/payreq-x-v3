<?php

namespace App\Http\Controllers\Accounting;

use App\Exports\VerificationJournalExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\VerificationJournalController;
use App\Models\Account;
use App\Models\Department;
use App\Models\Realization;
use App\Models\User;
use App\Models\VerificationJournal;
use App\Models\VerificationJournalDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class SapSyncController extends Controller
{
    public function index()
    {
        $page = request()->query('page', 'dashboard');

        $views = [
            'dashboard' => 'accounting.sap-sync.dashboard',
            '000H' => 'accounting.sap-sync.000H',
            '001H' => 'accounting.sap-sync.001H',
            '017C' => 'accounting.sap-sync.017C',
            '021C' => 'accounting.sap-sync.021C',
            '022C' => 'accounting.sap-sync.022C',
            '023C' => 'accounting.sap-sync.023C',
        ];

        if ($page == 'dashboard') {
            $data['count_by_user'] = $this->monhtly_count_by_user();
            $data['count_by_project'] = $this->monthly_count_by_project();

            return view($views[$page], compact('data'));
        }

        return view($views[$page]);
    }

    public function show($id)
    {
        $vj = VerificationJournal::find($id);
        $vj_details = VerificationJournalDetail::where('verification_journal_id', $id)
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($detail) {
                $account = Account::where('account_number', $detail->account_code)->first();
                $detail->account_name = $account ? $account->account_name : "not found";
                return $detail;
            });

        // return $vj;

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

        // get realizations
        $realizations = Realization::whereIn('nomor', $verification_journal_details->pluck('realization_no')->toArray())->get();

        // update realization status to close
        foreach ($realizations as $realization) {
            $realization->status = 'close';
            $realization->save();
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

        // get realizations
        $realizations = Realization::whereIn('nomor', $verification_journal_details->pluck('realization_no')->toArray())->get();

        // update realization status to verification-complete
        foreach ($realizations as $realization) {
            $realization->status = 'verification-complete';
            $realization->save();
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
            ->limit(300)
            ->get();


        return datatables()->of($verification_journals)
            ->editColumn('date', function ($journal) {
                $date = new \Carbon\Carbon($journal->date);
                return $date->addHours(8)->format('d-M-Y');
            })
            ->addColumn('status', function ($journal) {
                if ($journal->sap_journal_no == null) {
                    return '<span class="badge badge-danger">Not Posted Yet</span>';
                }
                return '<span class="badge badge-success">Posted</span>';
            })
            ->editColumn('amount', function ($journal) {
                return number_format($journal->amount, 2);
            })
            ->editColumn('sap_posting_date', function ($journal) {
                if ($journal->sap_posting_date == null) {
                    return '-';
                }
                $date = new \Carbon\Carbon($journal->updated_at);
                return $date->addHours(8)->format('d-M-Y H:i');
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

        // return $journal_details;

        return Excel::download(new VerificationJournalExport($journal_details), 'journal.xlsx');
    }

    public function edit_vjdetail_display()
    {
        $vj_id = request()->query('vj_id');
        $vj = VerificationJournal::find($vj_id);

        return view('accounting.sap-sync.edit-vjdetail.index', [
            'vj' => $vj
        ]);
    }

    public function edit_vjdetail_data()
    {
        $vj_id = request()->query('vj_id');

        $vj_details = VerificationJournalDetail::where('verification_journal_id', $vj_id)->get();

        return datatables()->of($vj_details)
            ->addColumn('akun', function ($vj_detail) {
                return $vj_detail->account_code . ' <br><small><b> ' . Account::where('account_number', $vj_detail->account_code)->first()->account_name . '</b></small>';
            })
            ->addColumn('cost_center', function ($vj_detail) {
                return $vj_detail->cost_center . ' <br><small><b> ' . Department::where('sap_code', $vj_detail->cost_center)->first()->akronim . '</b></small>';
            })
            ->addIndexColumn()
            ->addColumn('action', 'accounting.sap-sync.edit-vjdetail.action')
            ->rawColumns(['akun', 'action', 'cost_center'])
            ->toJson();
    }

    public function update_detail(Request $request)
    {
        $vj_detail = VerificationJournalDetail::find($request->vj_detail_id);
        $vj_detail->account_code = $request->account_code;
        $vj_detail->project = $request->project;
        $vj_detail->cost_center = $request->cost_center;
        $vj_detail->description = $request->description;

        $vj_detail->save();

        return back()->with('success', 'Detail Updated');
    }

    public function vjNotPosted()
    {
        $vjs = VerificationJournal::whereNull('sap_journal_no')->get();

        return $vjs;
    }

    public function chart_vj_postby()
    {
        // personel activities by name
        $activities = VerificationJournal::select(
            'posted_by',
            DB::raw("(COUNT(*)) as total_count")
        )
            ->whereYear('updated_at', Carbon::now())
            ->whereNotNull('sap_journal_no') // Added filter for sap_journal_no not null
            ->groupBy(DB::raw("posted_by"))
            ->get();

        //convert user_id to name
        foreach ($activities as $activity) {
            $activity->posted_name = User::find($activity->posted_by) ? User::find($activity->posted_by)->name : "not found";
        }

        $activities_count = $activities->pluck('total_count')->toArray();

        return [
            'activities_count' => array_sum($activities_count),
            'activities' => $activities,
        ];
    }

    public function upload_sap_journal(Request $request)
    {
        $this->validate($request, [
            'sap_journal_file' => 'required|mimes:pdf'
        ]);

        $vj = VerificationJournal::find($request->verification_journal_id);

        $file = $request->file('sap_journal_file');
        $filename = 'sapj_' . rand() . '_' . $file->getClientOriginalName();
        $file->move(public_path('file_upload'), $filename);
        $vj->update([
            'sap_filename' => $filename
        ]);

        return back()->with('success', 'SAP Journal Uploaded');
        // return redirect()->route('accounting.sap-sync.show', $v_id)->with('success', 'SAP Journal Uploaded');
    }

    public function print_sapj()
    {
        $vj_id = request()->query('vj_id');
        $vj = VerificationJournal::find($vj_id);

        return view('accounting.sap-sync.print-sapj', [
            'vj' => $vj
        ]);
    }

    public function monhtly_count_by_user()
    {
        // Get counts grouped by year, month, and user
        $counts = DB::table('verification_journals')
            ->select(
                DB::raw('YEAR(updated_at) as year'),
                DB::raw('MONTH(updated_at) as month'),
                'posted_by',
                DB::raw('COUNT(*) as count')
            )
            ->whereNotNull('posted_by')
            ->whereNotNull('sap_journal_no')
            ->groupBy('year', 'month', 'posted_by')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'asc')
            ->get();

        // Get all users who have posted journals
        $users = User::whereIn('id', $counts->pluck('posted_by')->unique())
            ->get()
            ->keyBy('id');

        // Get unique years
        $years = $counts->pluck('year')->unique()->sortDesc()->values();

        $data = [];

        // Initialize data structure
        foreach ($years as $year) {
            $yearArray = [
                'year' => $year,
                'month_data' => [],
                'user_totals' => [] // Add array for user totals
            ];

            // Initialize user totals
            foreach ($users as $user) {
                $yearArray['user_totals'][$user->id] = [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'total_count' => 0
                ];
            }

            // Initialize months with all users
            for ($month = 1; $month <= 12; $month++) {
                $monthData = [
                    'month' => str_pad($month, 2, '0', STR_PAD_LEFT),
                    'month_name' => date('M', mktime(0, 0, 0, $month, 1)),
                    'users' => []
                ];

                // Add all users with zero count by default
                foreach ($users as $user) {
                    $monthData['users'][] = [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'count' => 0
                    ];
                }

                $yearArray['month_data'][$month] = $monthData;
            }

            // Fill in the actual counts
            foreach ($counts as $count) {
                if ($count->year == $year) {
                    $userName = $users[$count->posted_by]->name ?? 'Unknown User';
                    // Find and update the user count in the month data
                    foreach ($yearArray['month_data'][$count->month]['users'] as &$userData) {
                        if ($userData['user_id'] == $count->posted_by) {
                            $userData['count'] = $count->count;
                            // Add to user's yearly total
                            $yearArray['user_totals'][$count->posted_by]['total_count'] += $count->count;
                            break;
                        }
                    }
                }
            }

            // Convert month_data and user_totals to array values
            $yearArray['month_data'] = array_values($yearArray['month_data']);
            $yearArray['user_totals'] = array_values($yearArray['user_totals']);
            $data[] = $yearArray;
        }

        return $data;
    }

    public function monthly_count_by_project()
    {
        // Get counts grouped by year, month, and project
        $counts = DB::table('verification_journals')
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                'project',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->groupBy('year', 'month', 'project')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'asc')
            ->get();

        // Get all unique projects
        $projects = $counts->pluck('project')->unique()->values();

        // Get unique years
        $years = $counts->pluck('year')->unique()->sortDesc()->values();

        $data = [];

        // Initialize data structure
        foreach ($years as $year) {
            $yearArray = [
                'year' => $year,
                'month_data' => [],
                'project_totals' => [] // Add array for project totals
            ];

            // Initialize project totals
            foreach ($projects as $project) {
                $yearArray['project_totals'][$project] = [
                    'project' => $project,
                    'total_count' => 0,
                    'total_amount' => 0
                ];
            }

            // Initialize months with all projects
            for ($month = 1; $month <= 12; $month++) {
                $monthData = [
                    'month' => str_pad($month, 2, '0', STR_PAD_LEFT),
                    'month_name' => date('M', mktime(0, 0, 0, $month, 1)),
                    'projects' => []
                ];

                // Add all projects with zero count by default
                foreach ($projects as $project) {
                    $monthData['projects'][] = [
                        'project' => $project,
                        'count' => 0,
                        'amount' => 0
                    ];
                }

                $yearArray['month_data'][$month] = $monthData;
            }

            // Fill in the actual counts
            foreach ($counts as $count) {
                if ($count->year == $year) {
                    // Find and update the project count in the month data
                    foreach ($yearArray['month_data'][$count->month]['projects'] as &$projectData) {
                        if ($projectData['project'] == $count->project) {
                            $projectData['count'] = $count->count;
                            $projectData['amount'] = $count->total_amount;
                            // Add to project's yearly total
                            $yearArray['project_totals'][$count->project]['total_count'] += $count->count;
                            $yearArray['project_totals'][$count->project]['total_amount'] += $count->total_amount;
                            break;
                        }
                    }
                }
            }

            // Convert month_data and project_totals to array values
            $yearArray['month_data'] = array_values($yearArray['month_data']);
            $yearArray['project_totals'] = array_values($yearArray['project_totals']);
            $data[] = $yearArray;
        }

        return $data;
    }
}
