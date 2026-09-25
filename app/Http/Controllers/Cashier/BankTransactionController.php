<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Incoming;
use App\Models\VerificationJournal;
use App\Models\VerificationJournalDetail;
use App\Services\CashierBankTransactionDirectSapService;
use App\Services\SapJournalSubmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class BankTransactionController extends Controller
{
    public function __construct(
        protected CashierBankTransactionDirectSapService $directSapService,
        protected SapJournalSubmissionService $journalSubmissionService
    ) {}

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
            ->editColumn('status', function ($journal) {
                $badgeClass = match ($journal->status) {
                    'draft' => 'warning',
                    'submitted' => 'info',
                    'posted' => 'success',
                    'canceled' => 'danger',
                    default => 'secondary',
                };
                $html = '<span class="badge badge-'.$badgeClass.'">'.ucfirst($journal->status).'</span>';
                if ($journal->auto_validated_by_cashier) {
                    $html .= '<br><small class="text-muted">Auto-validated by cashier</small>';
                }

                return $html;
            })
            ->addColumn('action', function ($journal) {
                $viewBtn = '<a href="'.route('cashier.bank-transactions.show', $journal->id).'" class="btn btn-info btn-xs mr-1" title="View transaction details"><i class="fas fa-eye"></i></a>';
                $editBtn = '';
                $deleteBtn = '';
                $submitBtn = '';

                if ($journal->status == 'draft') {
                    $editBtn = '<a href="'.route('cashier.bank-transactions.edit', $journal->id).'" class="btn btn-warning btn-xs mr-1" title="Edit transaction"><i class="fas fa-edit"></i></a>';
                    $deleteBtn = '<form action="'.route('cashier.bank-transactions.destroy', $journal->id).'" method="POST" style="display: inline;">
                                '.csrf_field().'
                                '.method_field('DELETE').'
                                <button type="submit" class="btn btn-danger btn-xs mr-1 delete-transaction" title="Delete transaction"><i class="fas fa-trash"></i></button>
                            </form>';
                    $submitBtn = '<form action="'.route('cashier.bank-transactions.submit', $journal->id).'" method="POST" style="display: inline;">
                                '.csrf_field().'
                                <button type="submit" class="btn btn-success btn-xs submit-transaction" title="Submit transaction"><i class="fas fa-paper-plane"></i></button>
                            </form>';
                }

                return '<div class="btn-group">'.$viewBtn.$editBtn.$deleteBtn.$submitBtn.'</div>';
            })
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    public function create()
    {
        return view('cashier.bank-transactions.create', $this->formViewData());
    }

    public function store(Request $request)
    {
        $project = is_array($request->project) ? strval($request->project[0]) : strval($request->project);

        $request->validate([
            'date' => 'required|date',
            'project' => 'required',
            'bank_account' => 'required',
            'description' => 'required|string',
            'transaction_type' => 'required|in:transfer_to_petty_cash,bank_admin_fee,bank_interest',
            'account_code.*' => 'required|string',
            'debit_credit.*' => 'required|in:debit,credit',
            'detail_description.*' => 'required|string',
            'project.*' => 'required',
            'cost_center.*' => 'required|string',
            'amount.*' => 'required|numeric',
        ]);

        $accountError = $this->directSapService->validateDebitAccountsForTransactionType(
            (string) $request->transaction_type,
            $request->account_code ?? []
        );
        if ($accountError !== null) {
            return redirect()->back()->withInput()->withErrors(['account_code' => $accountError]);
        }

        $document_number = app('App\Http\Controllers\DocumentNumberController')->generate_document_number('verification-journal', $project);

        DB::beginTransaction();
        try {
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
                ->with('error', 'Error occurred: '.$e->getMessage())
                ->withInput();
        }
    }

    public function show($id)
    {
        $journal = VerificationJournal::with(['verificationJournalDetails', 'createdBy', 'postedBy'])
            ->findOrFail($id);

        $incoming = null;
        if (in_array($journal->status, ['submitted', 'posted'], true)) {
            $incoming = Incoming::where('description', 'like', '%Bank Transaction: '.$journal->nomor.'%')
                ->latest()
                ->first();
        }

        $eligibleForDirectSap = $this->directSapService->isEligibleForDirectSapSubmission($journal, Auth::user());

        return view('cashier.bank-transactions.show', compact('journal', 'incoming', 'eligibleForDirectSap'));
    }

    public function edit($id)
    {
        $journal = VerificationJournal::with('verificationJournalDetails')
            ->findOrFail($id);

        if ($journal->status != 'draft') {
            return redirect()->route('cashier.bank-transactions.index')
                ->with('error', 'Cannot edit a transaction that is not in draft status');
        }

        return view('cashier.bank-transactions.edit', array_merge(
            ['journal' => $journal],
            $this->formViewData()
        ));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'date' => 'required|date',
            'project' => 'required',
            'bank_account' => 'required',
            'description' => 'required|string',
            'transaction_type' => 'required|in:transfer_to_petty_cash,bank_admin_fee,bank_interest',
            'account_code.*' => 'required|string',
            'debit_credit.*' => 'required|in:debit,credit',
            'detail_description.*' => 'required|string',
            'project.*' => 'required',
            'cost_center.*' => 'required|string',
            'amount.*' => 'required|numeric',
        ]);

        $accountError = $this->directSapService->validateDebitAccountsForTransactionType(
            (string) $request->transaction_type,
            $request->account_code ?? []
        );
        if ($accountError !== null) {
            return redirect()->back()->withInput()->withErrors(['account_code' => $accountError]);
        }

        $journal = VerificationJournal::findOrFail($id);

        if ($journal->status != 'draft') {
            return redirect()->route('cashier.bank-transactions.index')
                ->with('error', 'Cannot update a transaction that is not in draft status');
        }

        DB::beginTransaction();
        try {
            $bankAccount = is_array($request->bank_account) ? strval($request->bank_account[0]) : strval($request->bank_account);
            $project = is_array($request->project) ? strval($request->project[0]) : strval($request->project);

            $journal->update([
                'date' => $request->date,
                'type' => 'bank',
                'project' => $project,
                'bank_account' => $bankAccount,
                'description' => $request->description,
                'amount' => array_sum($request->amount),
            ]);

            $journal->verificationJournalDetails()->delete();

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
                ->with('success', 'Bank transaction updated successfully');
        } catch (\Exception $e) {
            DB::rollback();

            return redirect()->back()
                ->with('error', 'Error occurred: '.$e->getMessage())
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
            $journal->verificationJournalDetails()->delete();
            $journal->delete();

            DB::commit();

            return redirect()->route('cashier.bank-transactions.index')
                ->with('success', 'Bank transaction deleted successfully');
        } catch (\Exception $e) {
            DB::rollback();

            return redirect()->back()->with('error', 'Error occurred: '.$e->getMessage());
        }
    }

    public function submit($id)
    {
        $journal = VerificationJournal::with('verificationJournalDetails')->findOrFail($id);

        if (! $this->canSubmitBankTransaction($journal)) {
            return redirect()->route('cashier.bank-transactions.index')
                ->with('error', 'Only draft transactions (or failed SAP submissions) can be submitted');
        }

        if ($this->directSapService->isEligibleForDirectSapSubmission($journal, Auth::user())) {
            return $this->submitWithDirectSap($journal);
        }

        return $this->submitLegacyPendingValidation($journal);
    }

    protected function canSubmitBankTransaction(VerificationJournal $journal): bool
    {
        if ($journal->type !== 'bank') {
            return false;
        }

        if ($journal->status === 'draft') {
            return true;
        }

        if ($journal->status === 'submitted'
            && empty($journal->sap_journal_no)
            && $journal->auto_validated_by_cashier
            && in_array($journal->sap_submission_status, [null, 'failed'], true)) {
            return true;
        }

        return false;
    }

    protected function submitWithDirectSap(VerificationJournal $journal)
    {
        $user = Auth::user();

        DB::beginTransaction();
        try {
            $journal->update([
                'status' => 'submitted',
                'validation_status' => VerificationJournal::VALIDATION_VALIDATED,
                'validated_by' => $user->id,
                'validated_at' => now(),
                'auto_validated_by_cashier' => true,
                'rejection_reason' => null,
            ]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Error occurred: '.$e->getMessage());
        }

        $journal->refresh();
        $result = $this->journalSubmissionService->submit($journal, $user);

        if (! ($result['success'] ?? false)) {
            $journal->refresh();
            if ($journal->status !== 'submitted') {
                $journal->update(['status' => 'submitted']);
            }

            return redirect()->route('cashier.bank-transactions.show', $journal->id)
                ->with('error', $result['message'] ?? 'Failed to submit to SAP B1.');
        }

        DB::beginTransaction();
        try {
            $journal->refresh();

            $incoming = new Incoming;
            $incoming->nomor = $journal->nomor;
            $incoming->cashier_id = $user->id;
            $incoming->description = 'Bank Transaction: '.$journal->nomor.' - '.$journal->description;
            $incoming->amount = $journal->amount;
            $incoming->project = $journal->project;
            $incoming->receive_date = now();
            $incoming->will_post = true;
            $incoming->sap_journal_no = $journal->sap_journal_no;
            $incoming->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('cashier.bank-transactions.show', $journal->id)
                ->with('error', 'SAP posting succeeded but incoming record could not be created: '.$e->getMessage());
        }

        $sapJournalNo = $journal->sap_journal_no ?? ($result['sap_journal_no'] ?? null);

        return redirect()->route('cashier.bank-transactions.show', $journal->id)
            ->with('success', 'Bank transaction posted to SAP B1. SAP Journal Number: '.$sapJournalNo);
    }

    protected function submitLegacyPendingValidation(VerificationJournal $journal)
    {
        DB::beginTransaction();
        try {
            $journal->update([
                'status' => 'submitted',
                'validation_status' => VerificationJournal::VALIDATION_PENDING,
                'validated_at' => null,
                'validated_by' => null,
                'auto_validated_by_cashier' => false,
                'rejection_reason' => null,
            ]);

            $incoming = new Incoming;
            $incoming->nomor = $journal->nomor;
            $incoming->cashier_id = Auth::id();
            $incoming->description = 'Bank Transaction: '.$journal->nomor.' - '.$journal->description;
            $incoming->amount = $journal->amount;
            $incoming->project = $journal->project;
            $incoming->receive_date = now();
            $incoming->will_post = true;
            $incoming->save();

            DB::commit();

            return redirect()->route('cashier.bank-transactions.index')
                ->with('success', 'Bank transaction submitted successfully and incoming record created');
        } catch (\Exception $e) {
            DB::rollback();

            return redirect()->back()
                ->with('error', 'Error occurred: '.$e->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function formViewData(): array
    {
        return [
            'transactionTypeAccountMap' => CashierBankTransactionDirectSapService::transactionTypeAccountMap(),
            'cashierVjSapLimit' => $this->directSapService->getSapLimit(),
        ];
    }
}
