<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Realization;
use App\Models\RealizationDetail;
use App\Models\VerificationJournal;
use Illuminate\Http\Request;

class VerificationJournalController extends Controller
{
    public function index()
    {
        if (auth()->user()->hasRole(['superadmin', 'admin'])) {
            $realizations_count1 = Realization::whereNull('verification_journal_id')
                ->where('status', 'verification-complete')
                ->count();

            $realizations_count2 = 0;
        } else if (auth()->user()->hasRole(['cashier'])) {
            $projects = ['000H', 'APS'];
            $realizations_count1 = Realization::whereNull('verification_journal_id')
                ->where('status', 'verification-complete')
                ->whereNull('flag')
                ->whereIn('project', $projects)
                ->count();

            $realizations_count2 = Realization::where('flag', 'VJTEMP' . auth()->user()->id)
                ->count();
        } else {
            $realizations_count1 = Realization::whereNull('verification_journal_id')
                ->where('status', 'verification-complete')
                ->whereNull('flag')
                ->where('project', auth()->user()->project)
                ->count();

            $realizations_count2 = Realization::where('flag', 'VJTEMP' . auth()->user()->id)
                ->count();
        }

        $realizations_count = $realizations_count1 + $realizations_count2;

        return view('verifications.journal.index', compact([
            'realizations_count'
        ]));
    }

    public function create()
    {
        $realizations = Realization::whereNull('verification_journal_id')
            ->whereNull('flag')
            ->where('project', auth()->user()->project)
            ->count();

        if ($realizations > 0) {
            $select_all_button = true;
        } else {
            $select_all_button = false;
        }

        $realizations_in_cart = Realization::where('flag', 'VJTEMP' . auth()->user()->id)
            ->get();

        if ($realizations_in_cart->count() > 0) {
            $remove_all_button = true;
        } else {
            $remove_all_button = false;
        }

        return view('verifications.journal.create', compact([
            'select_all_button',
            'remove_all_button'
        ]));
    }

    public function show($id)
    {
        $verification_journal = VerificationJournal::findOrFail($id);
        $journal_details = $this->journal_details($id);
        $debits = $journal_details['debits'];
        $credit = $journal_details['credit'];

        return view('verifications.journal.show', compact([
            'verification_journal',
            'debits',
            'credit'
        ]));
    }

    public function update_sap_info(Request $request)
    {
        $request->validate([
            'sap_journal_no' => 'required',
            'sap_posting_date' => 'required',
        ]);

        $verification_journal = VerificationJournal::findOrFail($request->verification_journal_id);
        $verification_journal->sap_journal_no = $request->sap_journal_no;
        $verification_journal->sap_posting_date = $request->sap_posting_date;
        $verification_journal->save();

        return $this->show($verification_journal->id)->with('success', 'SAP Info updated successfully');
    }

    public function cancel_sap_info(Request $request)
    {
        $verification_journal = VerificationJournal::findOrFail($request->verification_journal_id);
        $verification_journal->sap_journal_no = null;
        $verification_journal->sap_posting_date = null;
        $verification_journal->save();

        return $this->show($verification_journal->id)->with('success', 'SAP Info cancelled successfully');
    }

    public function print($id)
    {
        $verification_journal = VerificationJournal::findOrFail($id);
        $journal_details = $this->journal_details($id);
        $debits = $journal_details['debits'];
        $credit = $journal_details['credit'];

        return view('verifications.journal.print_journal', compact([
            'verification_journal',
            'debits',
            'credit'
        ]));
    }

    public function add_to_cart(Request $request)
    {
        $flag = 'VJTEMP' . auth()->user()->id; // JTEMP = Journal Temporary

        $realization = Realization::findOrFail($request->realization_id);
        $realization->flag = $flag;
        $realization->save();

        return redirect()->back();
    }

    public function remove_from_cart(Request $request)
    {
        $realization = Realization::findOrFail($request->realization_id);
        $realization->flag = null;
        $realization->save();

        return redirect()->back();
    }

    public function move_all_tocart()
    {
        $realizations = Realization::whereNull('verification_journal_id')
            ->where('status', 'verification-complete')
            ->whereNull('flag')
            ->where('project', auth()->user()->project)
            ->get();

        $flag = 'VJTEMP' . auth()->user()->id; // VJTEMP = Verification Journal Temporary

        foreach ($realizations as $realization) {
            $realization->flag = $flag;
            $realization->save();
        }

        return redirect()->back();
    }

    public function remove_all_fromcart()
    {
        $flag = 'VJTEMP' . auth()->user()->id;
        $realizations = Realization::where('flag', $flag)
            ->get();

        foreach ($realizations as $realization) {
            $realization->flag = null;
            $realization->save();
        }

        return redirect()->back();
    }

    public function tocart_data()
    {
        $userRoles = app(UserController::class)->getUserRoles();

        if (in_array('superadmin', $userRoles) || in_array('admin', $userRoles)) {
            $realizations = Realization::where('status', 'verification-complete')
                ->whereNull('verification_journal_id')
                ->whereNull('flag')
                ->get();
        } elseif (in_array('cashier', $userRoles)) {
            $include_projects = ['000H', 'APS'];
            $realizations = Realization::where('status', 'verification-complete')
                ->whereIn('project', $include_projects)
                ->whereNull('verification_journal_id')
                ->whereNull('flag')
                ->get();
        } else {
            $realizations = Realization::where('project', auth()->user()->project)
                ->where('status', 'verification-complete')
                ->whereNull('verification_journal_id')
                ->whereNull('flag')
                ->get();
        }

        return datatables()->of($realizations)
            ->addColumn('employee', function ($realization) {
                return $realization->payreq->requestor->name;
            })
            ->addColumn('realization_no', function ($realization) {
                return $realization->nomor;
            })
            ->addColumn('amount', function ($realization) {
                return number_format($realization->realizationDetails->sum('amount'), 2);
            })
            ->addColumn('action', 'verifications.journal.tocart-action')
            ->addIndexColumn()
            ->toJson();
    }

    public function incart_data()
    {
        $flag = 'VJTEMP' . auth()->user()->id; // VJTEMP = Verification Journal Temporary

        $realizations = Realization::where('flag', $flag)->get();

        return datatables()->of($realizations)
            ->addColumn('employee', function ($realization) {
                return $realization->payreq->requestor->name;
            })
            ->addColumn('realization_no', function ($realization) {
                return $realization->nomor;
            })
            ->addColumn('amount', function ($realization) {
                return number_format($realization->realizationDetails->sum('amount'), 2);
            })
            ->addColumn('action', 'verifications.journal.incart-action')
            ->addIndexColumn()
            ->toJson();
    }

    public function store(Request $request)
    {
        $realizations = Realization::where('flag', 'VJTEMP' . auth()->user()->id)
            ->get();

        $realization_details = $realizations->pluck('realizationDetails')->flatten();

        $verification_amount = $realization_details->sum('amount');

        $verification_journal = new VerificationJournal();
        $verification_journal->date = $request->date;
        $verification_journal->amount = $verification_amount;
        $verification_journal->description = $request->description;
        $verification_journal->project = auth()->user()->project;
        $verification_journal->created_by = auth()->user()->id;
        $verification_journal->save();

        $nomor = app(ToolController::class)->generateVerificationJournalNumber($verification_journal->id);

        $verification_journal->update([
            'nomor' => $nomor
        ]);

        // update realization table for verification_journal_id and remove flag
        foreach ($realizations as $realization) {
            $realization->verification_journal_id = $verification_journal->id;
            $realization->flag = null;
            $realization->save();
        }

        // update realization_details table for verification_journal_id
        foreach ($realization_details as $realization_detail) {
            $realization_detail->verification_journal_id = $verification_journal->id;
            $realization_detail->save();
        }

        return $this->index()->with('success', 'Verification Journal created successfully');
    }

    public function destroy($id)
    {
        $verification_journal = VerificationJournal::findOrFail($id);

        $realizations = Realization::where('verification_journal_id', $verification_journal->id)
            ->get();

        foreach ($realizations as $realization) {
            $realization->verification_journal_id = null;
            $realization->save();
        }

        $realization_details = RealizationDetail::where('verification_journal_id', $verification_journal->id)
            ->get();

        foreach ($realization_details as $realization_detail) {
            $realization_detail->verification_journal_id = null;
            $realization_detail->save();
        }

        $verification_journal->delete();

        return $this->index()->with('success', 'Verification Journal deleted successfully');
    }

    public function data()
    {
        if (auth()->user()->hasRole(['superadmin', 'admin'])) {
            $verification_journals = VerificationJournal::orderBy('date', 'desc')
                ->get();
        } else if (auth()->user()->hasRole(['cashier'])) {
            $projects = ['000H', 'APS'];
            $verification_journals = VerificationJournal::whereIn('project', $projects)
                ->orderBy('date', 'desc')
                ->get();
        } else {
            $verification_journals = VerificationJournal::where('project', auth()->user()->project)
                ->orderBy('date', 'desc')
                ->get();
        }

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
            ->addColumn('action', 'verifications.journal.action')
            ->rawColumns(['status', 'action'])
            ->toJson();
    }

    public function journal_details($verification_journal_id)
    {
        $realizations = Realization::where('verification_journal_id', $verification_journal_id)
            ->get();

        $realization_details = $realizations->pluck('realizationDetails')->flatten()->map(function ($detail) {
            return [
                'account_number' => $detail->account->account_number,
                'account_name' => $detail->account->account_name,
                'description' => $detail->description,
                'project' => $detail->project,
                'department' => $detail->department->akronim,
                'amount' => $detail->amount,
                'realization_number' => $detail->realization->nomor,
            ];
        });

        $cash_account = Account::select(['account_number', 'account_name', 'project'])->where('type', 'cash')->where('project', auth()->user()->project)->first();

        $result = [
            'debits' => [
                'debit_details' => $realization_details,
                'amount' => $realization_details->sum('amount')
            ],
            'credit' => [
                'account_number' => $cash_account->account_number,
                'account_name' => $cash_account->account_name,
                'amount' => $realization_details->sum('amount'),
                'project' => $cash_account->project,
                'department' => auth()->user()->department->akronim
            ]
        ];

        return $result;
    }
}
