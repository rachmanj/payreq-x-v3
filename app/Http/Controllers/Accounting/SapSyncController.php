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
            ->whereYear('created_at', Carbon::now())
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
}
