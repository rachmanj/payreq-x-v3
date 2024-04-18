<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Realization;
use App\Models\RealizationDetail;
use App\Models\VerificationJournal;
use App\Models\VerificationJournalDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VerificationJournalController extends Controller
{
    public function index()
    {
        $realizations_count = $this->available_realizations();

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
        $vj = VerificationJournal::find($id);
        $vj_details = VerificationJournalDetail::where('verification_journal_id', $id)
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($detail) {
                $account = Account::where('account_number', $detail->account_code)->first();
                $detail->account_name = $account->account_name;
                return $detail;
            });

        return view('verifications.journal.show', compact([
            'vj',
            'vj_details'
        ]));
    }

    public function print($id)
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

        return view('verifications.journal.print_journal', compact([
            'vj',
            'vj_details'
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

        // store verification_journal_details
        $this->store_verification_journal_details($verification_journal->id);

        return redirect()->route('verifications.journal.index')->with('success', 'Verification Journal created successfully');
    }

    public function destroy($id)
    {
        $verification_journal = VerificationJournal::findOrFail($id);

        // update realizations table
        $realizations = Realization::where('verification_journal_id', $verification_journal->id)
            ->get();

        foreach ($realizations as $realization) {
            $realization->verification_journal_id = null;
            $realization->save();
        }

        // update realization_details table
        $realization_details = RealizationDetail::where('verification_journal_id', $verification_journal->id)
            ->get();

        foreach ($realization_details as $realization_detail) {
            $realization_detail->verification_journal_id = null;
            $realization_detail->save();
        }

        // delete verification_journal_details
        $verification_journal_details = VerificationJournalDetail::where('verification_journal_id', $verification_journal->id)
            ->get();

        foreach ($verification_journal_details as $verification_journal_detail) {
            $verification_journal_detail->delete();
        }

        // delete verification_journal
        $verification_journal->delete();

        return redirect()->route('verifications.journal.index')->with('success', 'Verification Journal deleted successfully');
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

        $debit_details = $realizations->pluck('realizationDetails')->flatten()->map(function ($detail) {
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

        $verification_journal = VerificationJournal::findOrFail($verification_journal_id);

        $result = [
            'debits' => [
                'debit_details' => $debit_details,
                'debit_amount' => $debit_details->sum('amount')
            ],
            'credit' => [
                'credit_details' => $this->credit_details($verification_journal_id),
                'credit_amount' => $debit_details->sum('amount'),

            ],
            'verification' => [
                'nomor' => $verification_journal->nomor,
                'date' => $verification_journal->date,
                'project' => $verification_journal->project,
                'department' => auth()->user()->department->akronim,
                'createdBy' => $verification_journal->createdBy->name,
                'amount' => $verification_journal->amount,
            ]
        ];

        return $result;
    }

    public function credit_details($verification_journal_id)
    {
        $realizations = Realization::where('verification_journal_id', $verification_journal_id)
            ->get();

        $realization_details = $realizations->pluck('realizationDetails')->flatten();
        $accounts = $realization_details->pluck('account_id')->unique();
        $cash_account = Account::select(['account_number', 'account_name', 'project'])->where('type', 'cash')->where('project', auth()->user()->project)->first();

        foreach ($accounts as $account) {
            if ($realization_details->where('account_id', $account)->sum('amount') > 0) {
                $array_desc = $realization_details->where('account_id', $account)->pluck('description')->unique();
                $descriptions = implode(', ', $array_desc->toArray());

                $result[] = [
                    'account_number' => $cash_account->account_number,
                    'account_name' => $cash_account->account_name,
                    'amount' => $realization_details->where('account_id', $account)->sum('amount'),
                    'description' => $descriptions,
                    // add project and department
                    'project' => $realization_details->where('account_id', $account)->first()->project,
                    'department' => $realization_details->where('account_id', $account)->first()->department->akronim,
                ];
            }
        }

        return $result;
    }

    public function store_verification_journal_details($verification_journal_id)
    {
        // debits type
        $realizations = Realization::where('verification_journal_id', $verification_journal_id)
            ->get();

        foreach ($realizations as $realization) {
            $realization_details = $realization->realizationDetails;

            foreach ($realization_details as $realization_detail) {
                $data = [
                    'verification_journal_id' => $verification_journal_id,
                    'realization_date' => Carbon::parse($realization->created_at)->format('Y-m-d'),
                    'debit_credit' => 'debit',
                    'realization_no' => $realization_detail->realization->nomor,
                    'account_code' => $realization_detail->account->account_number,
                    'amount' => $realization_detail->amount,
                    'description' => $realization_detail->description,
                    'project' => $realization_detail->project,
                    'cost_center' => $realization_detail->department->akronim,
                ];

                VerificationJournalDetail::create($data);
            }

            // credit type
            $cash_account = Account::where('type', 'cash')->where('project', auth()->user()->project)->first();
            $array_desc = $realization_details->pluck('description')->unique();
            $descriptions = implode(', ', $array_desc->toArray());

            $data = [
                'verification_journal_id' => $verification_journal_id,
                'realization_date' => Carbon::parse($realization->created_at)->format('Y-m-d'),
                'debit_credit' => 'credit',
                'realization_no' => $realization->nomor,
                'account_code' => $cash_account->account_number,
                'amount' => $realization->realizationDetails->sum('amount'),
                'description' => $descriptions,
                'project' => $realization->project,
                'cost_center' => $realization->department->akronim,
            ];

            VerificationJournalDetail::create($data);
        }

        return true;
    }

    public function available_realizations()
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

        return $realizations_count1 + $realizations_count2;
    }
}
