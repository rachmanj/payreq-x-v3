<?php

namespace App\Http\Controllers\Accounting;

use App\Exports\VerificationJournalExport;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Department;
use App\Models\Realization;
use App\Models\SapSubmissionLog;
use App\Models\User;
use App\Models\VerificationJournal;
use App\Models\VerificationJournalDetail;
use App\Services\SapJournalSubmissionService;
use App\Services\SapService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class SapSyncController extends Controller
{
    public function __construct(
        protected SapJournalSubmissionService $journalSubmissionService
    ) {}

    public function index()
    {
        $user = auth()->user();
        $page = request()->query('page', 'dashboard');

        if ($this->isBoRestrictedUser($user) && ! in_array($page, ['001H', 'reversal-log'], true)) {
            return redirect()->route('accounting.sap-sync.index', ['page' => '001H']);
        }

        $views = [
            'dashboard' => 'accounting.sap-sync.dashboard',
            '000H' => 'accounting.sap-sync.000H',
            '001H' => 'accounting.sap-sync.001H',
            '017C' => 'accounting.sap-sync.017C',
            '021C' => 'accounting.sap-sync.021C',
            '022C' => 'accounting.sap-sync.022C',
            '023C' => 'accounting.sap-sync.023C',
            '025C' => 'accounting.sap-sync.025C',
            '026C' => 'accounting.sap-sync.026C',
            'reversal-log' => 'accounting.sap-sync.reversal-log',
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
        $user = auth()->user();
        $vj = VerificationJournal::find($id);

        if (! $vj) {
            abort(404);
        }

        $this->assertProjectAccessible($user, $vj->project);

        $canSubmitToSap = $this->canSubmitToSap($user, $vj);
        $canReverseSap = $this->canReverseSap($user);

        $vj_details = VerificationJournalDetail::where('verification_journal_id', $id)
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($detail) {
                $account = Account::where('account_number', $detail->account_code)->first();
                $detail->account_name = $account ? $account->account_name : 'not found';

                return $detail;
            });

        // Get submission logs for history display
        $submissionLogs = SapSubmissionLog::where('verification_journal_id', $vj->id)
            ->orderBy('created_at', 'desc')
            ->with('user')
            ->get();

        return view('accounting.sap-sync.show', compact([
            'vj',
            'vj_details',
            'submissionLogs',
            'canSubmitToSap',
            'canReverseSap',
        ]));
    }

    public function update_sap_info(Request $request)
    {
        try {
            // Begin database transaction
            DB::beginTransaction();

            // Get the verification journal
            $verification_journal = VerificationJournal::findOrFail($request->verification_journal_id);
            $this->assertProjectAccessible(auth()->user(), $verification_journal->project);

            // Update verification journal SAP info
            $verification_journal->sap_journal_no = $request->sap_journal_no;
            $verification_journal->sap_posting_date = $request->sap_posting_date;
            $verification_journal->posted_by = auth()->user()->id;
            $verification_journal->save();

            // Get all verification journal details
            $verification_journal_details = VerificationJournalDetail::where('verification_journal_id', $request->verification_journal_id)->get();

            // Update SAP info for each detail and account balances
            foreach ($verification_journal_details as $detail) {
                $detail->sap_journal_no = $request->sap_journal_no;
                $detail->save();

                // Update SAP balance on accounts table
                $account = Account::where('account_number', $detail->account_code)->first();
                if ($account) {
                    $account->sap_balance = $account->sap_balance - $detail->amount;
                    $account->save();
                }
            }

            // Handle bank type verification journals specially
            if ($verification_journal->type === 'bank') {
                // Update incoming record if exists
                $incoming = \App\Models\Incoming::where('nomor', $verification_journal->nomor)->first();
                if ($incoming) {
                    $incoming->sap_journal_no = $request->sap_journal_no;
                    $incoming->save();
                }

                // Update verification journal status to posted
                $verification_journal->status = 'posted';
                $verification_journal->save();
            }

            // Get and update realizations
            $realizations = Realization::whereIn('nomor', $verification_journal_details->pluck('realization_no')->toArray())->get();
            foreach ($realizations as $realization) {
                $realization->status = 'close';
                $realization->save();
            }

            // Commit the transaction
            DB::commit();

            return redirect()->route('accounting.sap-sync.show', $request->verification_journal_id)
                ->with('success', 'SAP Info Updated Successfully');
        } catch (\Exception $e) {
            // Rollback the transaction in case of error
            DB::rollBack();

            // Log the error
            Log::error('Error updating SAP info: '.$e->getMessage());

            return redirect()->route('accounting.sap-sync.show', $request->verification_journal_id)
                ->with('error', 'Failed to update SAP Info. Please try again.');
        }
    }

    public function cancel_sap_info(Request $request)
    {
        // check if user is the one who posted the SAP Info
        $verification_journal = VerificationJournal::find($request->verification_journal_id);

        if (! $verification_journal) {
            abort(404);
        }

        $this->assertProjectAccessible(auth()->user(), $verification_journal->project);

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
        $user = auth()->user();
        $query = request()->query('project');

        if ($query === 'HO') {
            $project = ['000H', 'APS'];
        } else {
            $project = [$query];
        }

        $this->assertProjectAccessible($user, $query);

        $verification_journals = VerificationJournal::whereIn('project', $project)
            ->orderByRaw('sap_journal_no IS NULL DESC')
            ->orderBy('date', 'desc')
            ->limit(300)
            ->get();

        return datatables()->of($verification_journals)
            ->addColumn('select', function ($journal) {
                if ($journal->sap_journal_no) {
                    return '';
                }

                return '<input type="checkbox" class="bulk-select" value="'.$journal->id.'">';
            })
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
            ->rawColumns(['select', 'status', 'action'])
            ->toJson();
    }

    public function reversalLogData()
    {
        $user = auth()->user();
        $project = request()->query('project');

        $query = SapSubmissionLog::query()
            ->where('action', 'reversal')
            ->whereNotNull('verification_journal_id')
            ->with(['verificationJournal', 'user']);

        if ($this->isBoRestrictedUser($user)) {
            $query->whereHas('verificationJournal', function ($q) {
                $q->where('project', '001H');
            });
        } elseif ($project) {
            $query->whereHas('verificationJournal', function ($q) use ($project) {
                $q->where('project', $project);
            });
        }

        $logs = $query->orderBy('created_at', 'desc')->limit(500)->get();

        return datatables()->of($logs)
            ->addIndexColumn()
            ->addColumn('journal_no', function ($log) {
                return $log->verificationJournal->nomor ?? 'N/A';
            })
            ->addColumn('project', function ($log) {
                return $log->verificationJournal->project ?? 'N/A';
            })
            ->addColumn('type', function ($log) {
                return str_starts_with((string) $log->error_message, '[Manual]') ? 'Manual' : 'Automated';
            })
            ->editColumn('created_at', function ($log) {
                return date('d-M-Y H:i', strtotime($log->created_at.'+8 hours')).' wita';
            })
            ->editColumn('error_message', function ($log) {
                return ltrim(str_replace('[Manual]', '', (string) $log->error_message));
            })
            ->addColumn('status_badge', function ($log) {
                return $log->status === 'success'
                    ? '<span class="badge badge-secondary">REVERSED</span>'
                    : '<span class="badge badge-danger">FAILED</span>';
            })
            ->addColumn('reversed_by', function ($log) {
                return $log->user->name ?? 'N/A';
            })
            ->addColumn('action', function ($log) {
                if (! $log->verification_journal_id) {
                    return '';
                }

                return '<a href="'.route('accounting.sap-sync.show', $log->verification_journal_id).'" class="btn btn-xs btn-info" title="View journal"><i class="fas fa-eye"></i></a>';
            })
            ->rawColumns(['status_badge', 'action'])
            ->toJson();
    }

    public function export()
    {
        $vj_id = request()->query('vj_id');
        $vj = VerificationJournal::find($vj_id);

        if (! $vj) {
            abort(404);
        }

        $this->assertProjectAccessible(auth()->user(), $vj->project);

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

        // Process journal details based on verification journal type
        if ($vj && $vj->type === 'bank') {
            $this->processBankTransactionDetails($journal_details, $vj);

            return Excel::download(new \App\Exports\BankTransactionExport($journal_details), 'bank_transaction.xlsx');
        }

        // Process regular realization journal details
        $this->processRealizationDetails($journal_details);

        // Default export for other types
        return Excel::download(new VerificationJournalExport($journal_details), 'journal.xlsx');
    }

    public function submitToSap(Request $request)
    {
        $request->validate([
            'verification_journal_id' => 'required|exists:verification_journals,id',
        ]);

        $vj = VerificationJournal::findOrFail($request->verification_journal_id);
        $user = auth()->user();

        if (! $this->canSubmitToSap($user, $vj)) {
            return redirect()->route('accounting.sap-sync.show', $vj->id)
                ->with('error', 'You do not have permission to submit to SAP B1.');
        }

        if ($vj->sap_journal_no) {
            return redirect()->route('accounting.sap-sync.show', $vj->id)
                ->with('error', 'This journal has already been submitted to SAP B1.');
        }

        $result = $this->processSapSubmission($vj, $user);

        $flashKey = $result['success'] ? 'success' : 'error';

        return redirect()->route('accounting.sap-sync.show', $vj->id)
            ->with($flashKey, $result['message']);
    }

    public function bulkSubmit(Request $request)
    {
        $request->validate([
            'verification_journal_ids' => 'required|array|min:1',
            'verification_journal_ids.*' => 'distinct|exists:verification_journals,id',
        ]);

        $user = auth()->user();

        $ids = collect($request->verification_journal_ids)->unique()->values();
        $journals = VerificationJournal::whereIn('id', $ids)->get()->keyBy('id');

        $success = [];
        $failed = [];
        $skipped = [];

        foreach ($ids as $id) {
            $journal = $journals->get($id);

            if (! $journal) {
                $failed[] = ['nomor' => $id, 'message' => 'Journal not found'];

                continue;
            }

            if (! $this->canSubmitToSap($user, $journal)) {
                $failed[] = [
                    'nomor' => $journal->nomor ?? $journal->id,
                    'message' => 'You do not have permission to submit this journal to SAP B1.',
                ];

                continue;
            }

            if ($journal->sap_journal_no) {
                $skipped[] = $journal->nomor ?? $journal->id;

                continue;
            }

            $result = $this->processSapSubmission($journal, $user);

            if ($result['success']) {
                $success[] = [
                    'nomor' => $journal->nomor,
                    'sap_journal_no' => $result['sap_journal_no'] ?? 'N/A',
                ];
            } else {
                $failed[] = [
                    'nomor' => $journal->nomor,
                    'message' => $result['message'],
                ];
            }
        }

        $message = $this->buildBulkSubmissionMessage($success, $failed, $skipped);
        $flashKey = ! empty($failed) ? 'error' : 'success';

        return redirect()->back()->with($flashKey, $message);
    }

    protected function isBoRestrictedUser($user): bool
    {
        $fullAccessRoles = ['superadmin', 'admin', 'cashier', 'approver'];
        $boRoles = ['approver_bo', 'cashier_bo'];

        return $user->hasAnyRole($boRoles) && ! $user->hasAnyRole($fullAccessRoles);
    }

    protected function assertProjectAccessible($user, string $project): void
    {
        if ($this->isBoRestrictedUser($user) && $project !== '001H') {
            abort(403, 'You do not have permission to access this project.');
        }
    }

    protected function canSubmitToSap($user, ?VerificationJournal $vj = null): bool
    {
        $allowedRoles = ['superadmin', 'admin', 'cashier', 'approver'];

        if ($user->hasAnyRole($allowedRoles)) {
            return true;
        }

        if ($user->hasAnyRole(['approver_bo', 'cashier_bo'])) {
            return $vj !== null && $vj->project === '001H';
        }

        return false;
    }

    protected function canReverseSap($user): bool
    {
        return $user->can('cancel_sap_journal');
    }

    public function reverseToSap(Request $request)
    {
        $request->validate([
            'verification_journal_id' => 'required|exists:verification_journals,id',
            'reason' => 'required|string|max:1000',
        ]);

        $vj = VerificationJournal::findOrFail($request->verification_journal_id);
        $user = auth()->user();

        if (! $this->canReverseSap($user)) {
            return redirect()->route('accounting.sap-sync.show', $vj->id)
                ->with('error', 'You do not have permission to reverse journals in SAP B1.');
        }

        $this->assertProjectAccessible($user, $vj->project);

        if (empty($vj->sap_journal_no)) {
            return redirect()->route('accounting.sap-sync.show', $vj->id)
                ->with('error', 'This journal has not been posted to SAP B1.');
        }

        if ($vj->delivery_id) {
            return redirect()->route('accounting.sap-sync.show', $vj->id)
                ->with('error', 'This journal is attached to a Delivery batch. Detach it first before reversing.');
        }

        if (empty($vj->sap_je_jdt_num)) {
            return redirect()->route('accounting.sap-sync.show', $vj->id)
                ->with('error', 'This journal was posted before automatic reversal tracking. Please use the manual reversal form.');
        }

        $originalSapJournalNo = $vj->sap_journal_no;
        $sapService = app(SapService::class);

        try {
            $result = $sapService->cancelJournalEntry((string) $vj->sap_je_jdt_num);
        } catch (\Exception $e) {
            $this->recordReversalFailure($vj, $user, $originalSapJournalNo, $e->getMessage());

            Log::error('SAP B1 journal reversal failed', [
                'verification_journal_id' => $vj->id,
                'sap_je_jdt_num' => $vj->sap_je_jdt_num,
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return redirect()->route('accounting.sap-sync.show', $vj->id)
                ->with('error', 'Failed to reverse journal in SAP B1: '.$e->getMessage());
        }

        if (! ($result['success'] ?? false)) {
            $message = $result['message'] ?? 'SAP reversal returned unsuccessful result';
            $this->recordReversalFailure($vj, $user, $originalSapJournalNo, $message);

            return redirect()->route('accounting.sap-sync.show', $vj->id)
                ->with('error', 'Failed to reverse journal in SAP B1: '.$message);
        }

        try {
            DB::transaction(function () use ($vj, $user, $request, $originalSapJournalNo, $result) {
                $vj->sap_reversed_at = Carbon::now();
                $vj->sap_reversed_by = $user->id;
                $vj->sap_reversal_reason = $request->reason;
                $vj->sap_reversal_journal_no = $result['reversal_journal_no'] ?? null;
                $vj->save();

                $this->applyReversalUnlock($vj);

                SapSubmissionLog::create([
                    'verification_journal_id' => $vj->id,
                    'user_id' => $user->id,
                    'status' => 'success',
                    'action' => 'reversal',
                    'error_message' => $request->reason,
                    'sap_response' => null,
                    'sap_journal_number' => $originalSapJournalNo,
                    'sap_doc_num' => $result['reversal_journal_no'] ?? null,
                    'attempt_number' => 1,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('SAP B1 journal reversal save failed', [
                'verification_journal_id' => $vj->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('accounting.sap-sync.show', $vj->id)
                ->with('error', 'Journal was cancelled in SAP B1 but failed to update local records: '.$e->getMessage());
        }

        Log::info('SAP B1 journal reversal successful', [
            'verification_journal_id' => $vj->id,
            'original_sap_journal_no' => $originalSapJournalNo,
            'reversal_journal_no' => $result['reversal_journal_no'] ?? null,
            'user_id' => $user->id,
        ]);

        return redirect()->route('accounting.sap-sync.show', $vj->id)
            ->with('success', 'Journal successfully reversed in SAP B1. Original Journal Number: '.$originalSapJournalNo);
    }

    public function recordManualReversal(Request $request)
    {
        $request->validate([
            'verification_journal_id' => 'required|exists:verification_journals,id',
            'reason' => 'required|string|max:1000',
            'sap_reversal_journal_no' => 'nullable|string|max:100',
        ]);

        $vj = VerificationJournal::findOrFail($request->verification_journal_id);
        $user = auth()->user();

        if (! $this->canReverseSap($user)) {
            return redirect()->route('accounting.sap-sync.show', $vj->id)
                ->with('error', 'You do not have permission to reverse journals in SAP B1.');
        }

        $this->assertProjectAccessible($user, $vj->project);

        if (empty($vj->sap_journal_no)) {
            return redirect()->route('accounting.sap-sync.show', $vj->id)
                ->with('error', 'This journal has not been posted to SAP B1.');
        }

        if ($vj->delivery_id) {
            return redirect()->route('accounting.sap-sync.show', $vj->id)
                ->with('error', 'This journal is attached to a Delivery batch. Detach it first before reversing.');
        }

        if (! empty($vj->sap_je_jdt_num)) {
            return redirect()->route('accounting.sap-sync.show', $vj->id)
                ->with('error', 'This journal supports automatic reversal. Please use the Reverse in SAP B1 button.');
        }

        $originalSapJournalNo = $vj->sap_journal_no;

        try {
            DB::transaction(function () use ($vj, $user, $request, $originalSapJournalNo) {
                $vj->sap_reversed_at = Carbon::now();
                $vj->sap_reversed_by = $user->id;
                $vj->sap_reversal_reason = $request->reason;
                $vj->sap_reversal_journal_no = $request->sap_reversal_journal_no;
                $vj->save();

                $this->applyReversalUnlock($vj);

                SapSubmissionLog::create([
                    'verification_journal_id' => $vj->id,
                    'user_id' => $user->id,
                    'status' => 'success',
                    'action' => 'reversal',
                    'error_message' => '[Manual] '.$request->reason,
                    'sap_response' => null,
                    'sap_journal_number' => $originalSapJournalNo,
                    'sap_doc_num' => $request->sap_reversal_journal_no,
                    'attempt_number' => 1,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Manual SAP journal reversal failed', [
                'verification_journal_id' => $vj->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('accounting.sap-sync.show', $vj->id)
                ->with('error', 'Failed to record manual reversal: '.$e->getMessage());
        }

        return redirect()->route('accounting.sap-sync.show', $vj->id)
            ->with('success', 'Manual reversal recorded. Original Journal Number: '.$originalSapJournalNo);
    }

    protected function applyReversalUnlock(VerificationJournal $vj): void
    {
        $vj->sap_journal_no = null;
        $vj->sap_posting_date = null;
        $vj->posted_by = null;
        $vj->sap_je_jdt_num = null;
        $vj->sap_submission_status = null;
        $vj->sap_submission_error = null;
        $vj->save();

        $vjDetails = VerificationJournalDetail::where('verification_journal_id', $vj->id)->get();
        foreach ($vjDetails as $detail) {
            $detail->sap_journal_no = null;
            $detail->save();
        }

        if ($vj->type === 'bank') {
            $incoming = \App\Models\Incoming::where('nomor', $vj->nomor)->first();
            if ($incoming) {
                $incoming->sap_journal_no = null;
                $incoming->save();
            }
            $vj->status = 'submitted';
            $vj->save();
        }

        $realizationNos = $vjDetails->pluck('realization_no')->filter()->toArray();
        if (! empty($realizationNos)) {
            $realizations = Realization::whereIn('nomor', $realizationNos)->get();
            foreach ($realizations as $realization) {
                $realization->status = 'verification-complete';
                $realization->save();
            }
        }
    }

    protected function recordReversalFailure(VerificationJournal $vj, User $user, string $originalSapJournalNo, string $errorMessage): void
    {
        SapSubmissionLog::create([
            'verification_journal_id' => $vj->id,
            'user_id' => $user->id,
            'status' => 'failed',
            'action' => 'reversal',
            'error_message' => $errorMessage,
            'sap_response' => null,
            'sap_journal_number' => $originalSapJournalNo,
            'attempt_number' => 1,
        ]);
    }

    protected function processSapSubmission(VerificationJournal $vj, User $user): array
    {
        return $this->journalSubmissionService->submit($vj, $user);
    }

    protected function recordSubmissionFailure(VerificationJournal $vj, User $user, int $attemptNumber, string $errorMessage): void
    {
        $this->journalSubmissionService->recordFailure($vj, $user, $attemptNumber, $errorMessage);
    }

    protected function buildBulkSubmissionMessage(array $success, array $failed, array $skipped): string
    {
        $parts = ['Bulk submission completed.'];

        if (! empty($success)) {
            $summary = collect($success)->map(function ($item) {
                return ($item['nomor'] ?? 'N/A').'→'.($item['sap_journal_no'] ?? 'N/A');
            })->implode(', ');

            $parts[] = 'Success: '.count($success).' ('.$summary.')';
        }

        if (! empty($failed)) {
            $summary = collect($failed)->map(function ($item) {
                return ($item['nomor'] ?? 'N/A').' - '.$item['message'];
            })->implode('; ');

            $parts[] = 'Failed: '.count($failed).' ('.$summary.')';
        }

        if (! empty($skipped)) {
            $parts[] = 'Skipped (already posted): '.implode(', ', $skipped);
        }

        return implode(' ', $parts);
    }

    /**
     * Process details for bank transaction exports
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $journal_details
     * @param  \App\Models\VerificationJournal  $vj
     * @return void
     */
    private function processBankTransactionDetails($journal_details, $vj)
    {
        foreach ($journal_details as $detail) {
            $detail->vj_no = $vj->nomor;
            // Add bank-specific data processing here
        }
    }

    /**
     * Process details for realization-based exports
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $journal_details
     * @return void
     */
    private function processRealizationDetails($journal_details)
    {
        foreach ($journal_details as $detail) {
            $realization = Realization::where('nomor', $detail->realization_no)->first();
            if ($realization) {
                $payreq = $realization->payreq()->first();
                $detail->payreq_no = $payreq ? $payreq->nomor : null;
            }
            $detail->vj_no = VerificationJournal::where('id', $detail->verification_journal_id)->first()->nomor;
        }
    }

    public function edit_vjdetail_display()
    {
        $vj_id = request()->query('vj_id');
        $vj = VerificationJournal::find($vj_id);

        if (! $vj) {
            abort(404);
        }

        $this->assertProjectAccessible(auth()->user(), $vj->project);

        return view('accounting.sap-sync.edit-vjdetail.index', [
            'vj' => $vj,
        ]);
    }

    public function edit_vjdetail_data()
    {
        $vj_id = request()->query('vj_id');
        $vj = VerificationJournal::find($vj_id);

        if (! $vj) {
            return response()->json(['error' => 'Verification Journal not found'], 404);
        }

        $this->assertProjectAccessible(auth()->user(), $vj->project);

        $vj_details = VerificationJournalDetail::with('verificationJournal')
            ->where('verification_journal_id', $vj_id)
            ->get();

        return datatables()->of($vj_details)
            ->addColumn('akun', function ($vj_detail) {
                return $vj_detail->account_code.' <br><small><b> '.Account::where('account_number', $vj_detail->account_code)->first()->account_name.'</b></small>';
            })
            ->addColumn('cost_center', function ($vj_detail) {
                return $vj_detail->cost_center.' <br><small><b> '.Department::where('sap_code', $vj_detail->cost_center)->first()->akronim.'</b></small>';
            })
            ->addColumn('debit_credit_badge', function ($vj_detail) {
                $badgeClass = $vj_detail->debit_credit === 'debit' ? 'badge-primary' : 'badge-danger';
                $badgeText = strtoupper($vj_detail->debit_credit);

                return '<span class="badge '.$badgeClass.'">'.$badgeText.'</span>';
            })
            ->addIndexColumn()
            ->addColumn('action', function ($vj_detail) use ($vj) {
                return view('accounting.sap-sync.edit-vjdetail.action', [
                    'model' => $vj_detail,
                    'vj' => $vj,
                ])->render();
            })
            ->rawColumns(['akun', 'action', 'cost_center', 'debit_credit_badge'])
            ->toJson();
    }

    public function update_detail(Request $request)
    {
        $vj_detail = VerificationJournalDetail::find($request->vj_detail_id);
        $vj = VerificationJournal::find($vj_detail->verification_journal_id);

        $this->assertProjectAccessible(auth()->user(), $vj->project);

        if ($vj->sap_journal_no) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot edit. This verification journal has already been posted to SAP.',
            ], 422);
        }

        if ($vj_detail->debit_credit === 'credit') {
            $account = Account::where('account_number', $request->account_code)->first();

            if (! $account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected account not found.',
                ], 422);
            }

            if (! in_array($account->type, ['cash', 'bank'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'For credit entries, only cash or bank accounts can be selected.',
                ], 422);
            }

            if ($account->project !== $vj->project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected account must belong to the same project as the verification journal.',
                ], 422);
            }
        }

        $vj_detail->account_code = $request->account_code;
        $vj_detail->project = $request->project;
        $vj_detail->cost_center = $request->cost_center;
        $vj_detail->description = $request->description;

        $vj_detail->save();

        return response()->json([
            'success' => true,
            'message' => 'Detail Updated',
        ]);
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
            DB::raw('(COUNT(*)) as total_count')
        )
            ->whereYear('updated_at', Carbon::now())
            ->whereNotNull('sap_journal_no') // Added filter for sap_journal_no not null
            ->groupBy(DB::raw('posted_by'))
            ->get();

        // convert user_id to name
        foreach ($activities as $activity) {
            $activity->posted_name = User::find($activity->posted_by) ? User::find($activity->posted_by)->name : 'not found';
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
            'sap_journal_file' => 'required|mimes:pdf,jpg,jpeg,png,gif,bmp,webp|max:10240',
        ]);

        $vj = VerificationJournal::findOrFail($request->verification_journal_id);

        $this->assertProjectAccessible(auth()->user(), $vj->project);

        // Check if journal has been posted
        if (empty($vj->sap_journal_no)) {
            return back()->with('error', 'Cannot upload document. Journal has not been posted to SAP B1 yet.');
        }

        $file = $request->file('sap_journal_file');
        $extension = $file->getClientOriginalExtension();
        $filename = 'sapj_'.$vj->sap_journal_no.'_'.time().'_'.rand(1000, 9999).'.'.$extension;
        $file->move(public_path('file_upload'), $filename);

        // Delete old file if exists
        if ($vj->sap_filename && file_exists(public_path('file_upload/'.$vj->sap_filename))) {
            @unlink(public_path('file_upload/'.$vj->sap_filename));
        }

        $vj->update([
            'sap_filename' => $filename,
        ]);

        return back()->with('success', 'Document uploaded successfully.');
    }

    public function print_sapj()
    {
        $vj_id = request()->query('vj_id');
        $vj = VerificationJournal::find($vj_id);

        if (! $vj) {
            abort(404);
        }

        $this->assertProjectAccessible(auth()->user(), $vj->project);

        return view('accounting.sap-sync.print-sapj', [
            'vj' => $vj,
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
                'user_totals' => [], // Add array for user totals
            ];

            // Initialize user totals
            foreach ($users as $user) {
                $yearArray['user_totals'][$user->id] = [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'total_count' => 0,
                ];
            }

            // Initialize months with all users
            for ($month = 1; $month <= 12; $month++) {
                $monthData = [
                    'month' => str_pad($month, 2, '0', STR_PAD_LEFT),
                    'month_name' => date('M', mktime(0, 0, 0, $month, 1)),
                    'users' => [],
                ];

                // Add all users with zero count by default
                foreach ($users as $user) {
                    $monthData['users'][] = [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'count' => 0,
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
        // Define all projects that should appear in the report (matching the tab links)
        $allProjects = ['000H', '001H', '017C', '021C', '022C', '023C', '025C', '026C'];

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

        // Get all unique projects from data and merge with predefined list
        $projectsFromData = $counts->pluck('project')->unique()->values()->toArray();
        $mergedProjects = array_unique(array_merge($allProjects, $projectsFromData));

        // Sort projects: predefined projects first in their order, then any other projects
        $projects = collect($mergedProjects)->sortBy(function ($project) use ($allProjects) {
            $index = array_search($project, $allProjects);

            return $index !== false ? $index : 999 + ord($project[0]);
        })->values();

        // Get unique years
        $years = $counts->pluck('year')->unique()->sortDesc()->values();

        $data = [];

        // Initialize data structure
        foreach ($years as $year) {
            $yearArray = [
                'year' => $year,
                'month_data' => [],
                'project_totals' => [], // Add array for project totals
            ];

            // Initialize project totals
            foreach ($projects as $project) {
                $yearArray['project_totals'][$project] = [
                    'project' => $project,
                    'total_count' => 0,
                    'total_amount' => 0,
                ];
            }

            // Initialize months with all projects
            for ($month = 1; $month <= 12; $month++) {
                $monthData = [
                    'month' => str_pad($month, 2, '0', STR_PAD_LEFT),
                    'month_name' => date('M', mktime(0, 0, 0, $month, 1)),
                    'projects' => [],
                ];

                // Add all projects with zero count by default
                foreach ($projects as $project) {
                    $monthData['projects'][] = [
                        'project' => $project,
                        'count' => 0,
                        'amount' => 0,
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
