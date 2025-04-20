<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VerificationJournal;
use App\Models\VerificationJournalDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class BankTransactionController extends Controller
{
    public function index()
    {
        return view('cashier.bank-transactions.index');
    }

    public function data()
    {
        $journals = VerificationJournal::with('createdBy', 'postedBy')
            ->where('type', 'bank')
            ->select('verification_journals.*');
            
        return DataTables::of($journals)
            ->addIndexColumn()
            ->addColumn('date', function ($journal) {
                return $journal->date ? date('d M Y', strtotime($journal->date)) : '-';
            })
            ->addColumn('created_by', function ($journal) {
                return $journal->createdBy->name ?? '-';
            })
            ->addColumn('bank_account', function ($journal) {
                return $journal->bank_account ?? '-';
            })
            ->addColumn('action', function ($journal) {
                $viewBtn = '<a href="' . route('cashier.bank-transactions.show', $journal->id) . '" class="btn btn-info btn-xs mr-1" title="View transaction details"><i class="fas fa-eye"></i></a>';
                $editBtn = '';
                $deleteBtn = '';
                $submitBtn = '';
                
                if ($journal->status == 'draft') {
                    $editBtn = '<a href="' . route('cashier.bank-transactions.edit', $journal->id) . '" class="btn btn-warning btn-xs mr-1" title="Edit transaction"><i class="fas fa-edit"></i></a>';
                    $deleteBtn = '<form action="' . route('cashier.bank-transactions.destroy', $journal->id) . '" method="POST" style="display: inline;">
                                ' . csrf_field() . '
                                ' . method_field('DELETE') . '
                                <button type="submit" class="btn btn-danger btn-xs mr-1 delete-transaction" title="Delete transaction"><i class="fas fa-trash"></i></button>
                            </form>';
                    $submitBtn = '<form action="' . route('cashier.bank-transactions.submit', $journal->id) . '" method="POST" style="display: inline;">
                                ' . csrf_field() . '
                                <button type="submit" class="btn btn-success btn-xs submit-transaction" title="Submit transaction"><i class="fas fa-paper-plane"></i></button>
                            </form>';
                }
                
                return '<div class="btn-group">' . $viewBtn . $editBtn . $deleteBtn . $submitBtn . '</div>';
            })
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    public function create()
    {
        return view('cashier.bank-transactions.create');
    }

    public function store(Request $request)
    {
        // Ensure project is a string
        $project = is_array($request->project) ? strval($request->project[0]) : strval($request->project);
        
        $document_number = app('App\Http\Controllers\DocumentNumberController')->generate_document_number('verification-journal', $project);
        
        $request->validate([
            'date' => 'required|date',
            'project' => 'required',
            'bank_account' => 'required',
            'description' => 'required|string',
            'account_code.*' => 'required|string',
            'debit_credit.*' => 'required|in:debit,credit',
            'detail_description.*' => 'required|string',
            'project.*' => 'required',
            'cost_center.*' => 'required|string',
            'amount.*' => 'required|numeric',
        ]);

        DB::beginTransaction();
        try {
            // Convert bank_account to string if it's not already
            $bankAccount = is_array($request->bank_account) ? strval($request->bank_account[0]) : strval($request->bank_account);
            
            $journal = VerificationJournal::create([
                'nomor' => $document_number,
                'date' => $request->date,
                'type' => 'bank',
                'project' => $project,
                'bank_account' => $bankAccount,
                'description' => $request->description,
                'created_by' => Auth::id(),
                'status' => 'draft',
                'amount' => array_sum($request->amount),
            ]);

            // create new verification_journal_details for credit side. other fields based on $journal
            $journal->verificationJournalDetails()->create([
                'verification_journal_id' => $journal->id,
                'account_code' => $journal->bank_account,
                'debit_credit' => 'credit',
                'description' => $journal->description,
                'project' => $journal->project,
                'cost_center' => Auth::user()->department->sap_code,
                'amount' => $journal->amount,
                'realization_no' => $journal->nomor,
                'realization_date' => $journal->date,
            ]);

            foreach ($request->account_code as $key => $account_code) {
                VerificationJournalDetail::create([
                    'verification_journal_id' => $journal->id,
                    'account_code' => $account_code,
                    'debit_credit' => 'debit',
                    'description' => $request->detail_description[$key],
                    'project' => $request->project[$key],
                    'cost_center' => $request->cost_center[$key],
                    'amount' => $request->amount[$key],
                    'realization_no' => $journal->nomor,
                    'realization_date' => $journal->date,
                ]);
            }

            DB::commit();
            return redirect()->route('cashier.bank-transactions.index')
                ->with('success', 'Bank transaction created successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Error occurred: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show($id)
    {
        $journal = VerificationJournal::with(['verificationJournalDetails', 'createdBy', 'postedBy'])
            ->findOrFail($id);
            
        // Check if there's a related incoming record (for submitted transactions)
        $incoming = null;
        if ($journal->status == 'submitted') {
            $incoming = \App\Models\Incoming::where('description', 'like', '%Bank Transaction: ' . $journal->nomor . '%')
                ->latest()
                ->first();
        }
            
        return view('cashier.bank-transactions.show', compact('journal', 'incoming'));
    }

    public function edit($id)
    {
        $journal = VerificationJournal::with('verificationJournalDetails')
            ->findOrFail($id);
            
        if ($journal->status != 'draft') {
            return redirect()->route('cashier.bank-transactions.index')
                ->with('error', 'Cannot edit a transaction that is not in draft status');
        }
            
        return view('cashier.bank-transactions.edit', compact('journal'));
    }

    public function update(Request $request, $id)
    {        
        $request->validate([
            'date' => 'required|date',
            'project' => 'required',
            'bank_account' => 'required',
            'description' => 'required|string',
            'account_code.*' => 'required|string',
            'debit_credit.*' => 'required|in:debit,credit',
            'detail_description.*' => 'required|string',
            'project.*' => 'required',
            'cost_center.*' => 'required|string',
            'amount.*' => 'required|numeric',
        ]);

        $journal = VerificationJournal::findOrFail($id);
        
        if ($journal->status != 'draft') {
            return redirect()->route('cashier.bank-transactions.index')
                ->with('error', 'Cannot update a transaction that is not in draft status');
        }

        DB::beginTransaction();
        try {
            // Convert bank_account to string if it's not already
            $bankAccount = is_array($request->bank_account) ? strval($request->bank_account[0]) : strval($request->bank_account);
            // Convert project to string if it's not already
            $project = is_array($request->project) ? strval($request->project[0]) : strval($request->project);
            
            $journal->update([
                'date' => $request->date,
                'type' => 'bank',
                'project' => $project,
                'bank_account' => $bankAccount,
                'description' => $request->description,
                'amount' => array_sum($request->amount),
            ]);

            // Delete all existing details
            $journal->verificationJournalDetails()->delete();

            // Create new details
            foreach ($request->account_code as $key => $account_code) {
                VerificationJournalDetail::create([
                    'verification_journal_id' => $journal->id,
                    'account_code' => $account_code,
                    'debit_credit' => $request->debit_credit[$key],
                    'description' => $request->detail_description[$key],
                    'project' => $request->project[$key],
                    'cost_center' => $request->cost_center[$key],
                    'amount' => $request->amount[$key],
                    'realization_date' => $request->date, // Use the main transaction date
                ]);
            }

            DB::commit();
            return redirect()->route('cashier.bank-transactions.index')
                ->with('success', 'Bank transaction updated successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Error occurred: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        $journal = VerificationJournal::findOrFail($id);
        
        if ($journal->status != 'draft') {
            return redirect()->route('cashier.bank-transactions.index')
                ->with('error', 'Cannot delete a transaction that is not in draft status');
        }

        DB::beginTransaction();
        try {
            // Delete all details first
            $journal->verificationJournalDetails()->delete();
            // Then delete the journal
            $journal->delete();
            
            DB::commit();
            return redirect()->route('cashier.bank-transactions.index')
                ->with('success', 'Bank transaction deleted successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Error occurred: ' . $e->getMessage());
        }
    }

    public function submit($id)
    {
        $journal = VerificationJournal::findOrFail($id);
        
        // Check if journal is in draft status
        if ($journal->status != 'draft') {
            return redirect()->route('cashier.bank-transactions.index')
                ->with('error', 'Only transactions in draft status can be submitted');
        }

        DB::beginTransaction();
        try {
            // 1. Update journal status to 'submitted'
            $journal->update([
                'status' => 'submitted'
            ]);

            // 2. Create an incoming record
            $incoming = new \App\Models\Incoming();
            $incoming->nomor = $journal->nomor;
            $incoming->cashier_id = Auth::id();
            $incoming->description = 'Bank Transaction: ' . $journal->nomor . ' - ' . $journal->description;
            $incoming->amount = $journal->amount;
            $incoming->project = $journal->project;
            $incoming->receive_date = now(); // Mark as received immediately
            $incoming->will_post = true;
            $incoming->save();

            // Log the action
            \Log::info('Bank transaction submitted and incoming created', [
                'bank_transaction_id' => $journal->id,
                'incoming_id' => $incoming->id,
                'user_id' => Auth::id()
            ]);

            DB::commit();
            return redirect()->route('cashier.bank-transactions.index')
                ->with('success', 'Bank transaction submitted successfully and incoming record created');
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error submitting bank transaction', [
                'bank_transaction_id' => $id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()
                ->with('error', 'Error occurred: ' . $e->getMessage());
        }
    }
}
