<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Realization;
use App\Models\Verification;
use App\Models\VerificationJournal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerificationJournalController extends Controller
{
    public function index()
    {
        return view('verifications.journal.index');
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
        $realizations = Realization::where('project', auth()->user()->project)
            ->whereNull('verification_journal_id')
            ->whereNull('flag')
            ->get();

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

        $realizations = Realization::where('project', auth()->user()->project)
            ->where('flag', $flag)
            ->get();

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

        // update realization
        foreach ($realizations as $realization) {
            $realization->verification_journal_id = $verification_journal->id;
            $realization->flag = null;
            $realization->save();
        }

        // update realization_details
        foreach ($realization_details as $realization_detail) {
            $realization_detail->verification_journal_id = $verification_journal->id;
            $realization_detail->save();
        }

        return view('verifications.journal.index')->with('success', 'Verification Journal created successfully');
    }

    public function data()
    {
        $verification_journals = VerificationJournal::where('project', auth()->user()->project)
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
            ->addColumn('action', 'verifications.journal.action')
            ->rawColumns(['status', 'action'])
            ->toJson();
    }

    public function journal_details($verification_journal_id)
    {
        $realizations = Realization::where('verification_journal_id', $verification_journal_id)
            ->get();

        $realization_details = $realizations->pluck('realizationDetails')->flatten();
        $accounts = $realization_details->pluck('account_id')->unique();

        foreach ($accounts as $account) {
            $array_desc = $realization_details->where('account_id', $account)->pluck('description')->unique();
            $descriptions = implode(', ', $array_desc->toArray());

            $journals[] = [
                // 'account_id' => $account,
                'account_number' => $realization_details->where('account_id', $account)->first()->account->account_number,
                'account_name' => $realization_details->where('account_id', $account)->first()->account->account_name,
                'amount' => $realization_details->where('account_id', $account)->sum('amount'),
                'description' => $descriptions
            ];
        }

        $advance_account = Account::select(['account_number', 'account_name'])->where('type', 'advance')->where('project', auth()->user()->project)->first();

        $result = [
            'debits' => $journals,
            'credit' => [
                'account_number' => $advance_account->account_number,
                'account_name' => $advance_account->account_name,
                'amount' => $realization_details->sum('amount')
            ]
        ];

        return $result;
    }
}
