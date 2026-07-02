<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use App\Http\Requests\ManualMatchGroupBankReconciliationRequest;
use App\Http\Requests\RejectBankReconciliationRequest;
use App\Http\Requests\StoreBankReconciliationRequest;
use App\Http\Requests\StoreBankStatementLineRequest;
use App\Jobs\AutoMatchReconciliationJob;
use App\Jobs\FetchSapGlLinesJob;
use App\Jobs\ParseBankStatementJob;
use App\Models\BankReconciliation;
use App\Models\BankStatementLine;
use App\Models\Dokumen;
use App\Models\Giro;
use App\Models\ReconciliationMatchGroup;
use App\Models\SapGlLine;
use App\Services\ReconciliationBalanceService;
use App\Services\ReconciliationMatchingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BankReconciliationController extends Controller
{
    protected array $elevatedRoles = ['admin', 'superadmin', 'cashier', 'approver_bo', 'cashier_bo', 'corsec'];

    public function index(Request $request): View
    {
        $view = $request->query('view', 'all');
        if (! in_array($view, ['all', 'pending_validation'], true)) {
            $view = 'all';
        }

        $query = BankReconciliation::query()
            ->with(['giro.bank', 'submittedBy'])
            ->orderByDesc('periode')
            ->orderByDesc('id');

        $roles = app(UserController::class)->getUserRoles();
        if (! array_intersect($this->elevatedRoles, $roles)) {
            $project = Auth::user()->project;
            $query->whereHas('giro', function ($q) use ($project): void {
                $q->where('project', $project);
            });
        }

        $canValidate = Auth::user()?->can('validate_bank_reconciliation') ?? false;

        $pendingValidationCountQuery = (clone $query)
            ->pendingValidation()
            ->excludingPreparer((int) Auth::id());

        $pendingValidationCount = $canValidate ? $pendingValidationCountQuery->count() : 0;

        if ($view === 'pending_validation' && $canValidate) {
            $query->pendingValidation()->excludingPreparer((int) Auth::id());
        }

        $reconciliations = $query->paginate(20)->withQueryString();

        return view('cashier.bank-reconciliation.index', compact(
            'reconciliations',
            'view',
            'canValidate',
            'pendingValidationCount',
        ));
    }

    public function create(Request $request): View
    {
        $giros = $this->accessibleGiros();

        $prefill = [
            'giro_id' => (int) old('giro_id', $request->integer('giro_id')),
            'periode' => old('periode', $request->input('periode')),
            'dokumen_id' => (int) old('dokumen_id', $request->integer('dokumen_id')),
            'source_mode' => old('source_mode', $request->input('source_mode', BankReconciliation::SOURCE_AI)),
        ];

        $dokumens = collect();
        if ($prefill['giro_id'] > 0) {
            $query = Dokumen::query()
                ->where('giro_id', $prefill['giro_id'])
                ->where('type', 'koran');

            if (filled($prefill['periode'])) {
                $periodeMonth = Carbon::parse(
                    is_string($prefill['periode']) && preg_match('/^\d{4}-\d{2}$/', $prefill['periode'])
                        ? $prefill['periode'].'-01'
                        : $prefill['periode']
                )->startOfMonth();

                $query->whereYear('periode', $periodeMonth->year)
                    ->whereMonth('periode', $periodeMonth->month);
            }

            $dokumens = $query->orderByDesc('periode')->orderByDesc('id')->get();
        }

        $hasPeriodeFilter = filled($prefill['periode']);

        return view('cashier.bank-reconciliation.create', compact('giros', 'prefill', 'dokumens', 'hasPeriodeFilter'));
    }

    public function store(StoreBankReconciliationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $giro = Giro::query()->findOrFail((int) $validated['giro_id']);
        $this->authorizeGiroAccess($giro);

        $periode = Carbon::parse((string) $validated['periode'])->startOfMonth()->format('Y-m-d');
        $sourceMode = $validated['source_mode'];

        $reconciliation = BankReconciliation::create([
            'giro_id' => $giro->id,
            'dokumen_id' => isset($validated['dokumen_id']) ? (int) $validated['dokumen_id'] : null,
            'periode' => $periode,
            'source_mode' => $sourceMode,
            'status' => $sourceMode === BankReconciliation::SOURCE_MANUAL
                ? BankReconciliation::STATUS_IN_REVIEW
                : BankReconciliation::STATUS_PROCESSING,
            'created_by' => Auth::id(),
        ]);

        if ($sourceMode === BankReconciliation::SOURCE_AI) {
            ParseBankStatementJob::dispatch($reconciliation->id);
        }

        FetchSapGlLinesJob::dispatch($reconciliation->id);

        $message = $sourceMode === BankReconciliation::SOURCE_MANUAL
            ? 'Bank reconciliation created in manual mode. Add bank statement lines and match against SAP.'
            : 'Bank reconciliation started. PDF parsing and SAP fetch are running in the queue.';

        return redirect()
            ->route('cashier.bank-reconciliation.show', $reconciliation)
            ->with('success', $message);
    }

    public function show(BankReconciliation $bankReconciliation, ReconciliationBalanceService $balanceService): View
    {
        $this->authorizeReconciliationAccess($bankReconciliation);

        $bankReconciliation->load([
            'giro.bank',
            'dokumen',
            'submittedBy',
            'validatedBy',
            'creator',
            'bankStatementLines' => fn ($q) => $q->orderBy('line_order')->orderBy('id'),
            'sapGlLines' => fn ($q) => $q->orderBy('posting_date')->orderBy('id'),
            'matchGroups' => fn ($q) => $q->with([
                'matchGroupBankLines.bankStatementLine',
                'matchGroupSapLines.sapGlLine',
            ])->orderBy('id'),
        ]);

        $balanceSummary = $balanceService->summary($bankReconciliation);

        $koranDokumens = collect();
        if ($bankReconciliation->giro_id && ! $bankReconciliation->isLockedForEditing()) {
            $koranDokumens = Dokumen::query()
                ->where('giro_id', $bankReconciliation->giro_id)
                ->where('type', 'koran')
                ->when($bankReconciliation->periode, function ($q) use ($bankReconciliation): void {
                    $q->whereYear('periode', $bankReconciliation->periode->year)
                        ->whereMonth('periode', $bankReconciliation->periode->month);
                })
                ->orderByDesc('periode')
                ->orderByDesc('id')
                ->get();
        }

        return view('cashier.bank-reconciliation.show', compact('bankReconciliation', 'balanceSummary', 'koranDokumens'));
    }

    public function status(BankReconciliation $bankReconciliation, ReconciliationBalanceService $balanceService): JsonResponse
    {
        $this->authorizeReconciliationAccess($bankReconciliation);

        $groupCount = $bankReconciliation->matchGroups()->count();
        $summary = $balanceService->summary($bankReconciliation);

        return response()->json([
            'status' => $bankReconciliation->status,
            'validation_status' => $bankReconciliation->validation_status,
            'bank_lines_count' => $bankReconciliation->bankStatementLines()->count(),
            'sap_lines_count' => $bankReconciliation->sapGlLines()->count(),
            'match_groups_count' => $groupCount,
            'matches_count' => $groupCount,
            'bank_net' => $summary['bank_net'],
            'book_net' => $summary['book_net'],
            'difference' => $summary['difference'],
            'is_balanced' => $summary['is_balanced'],
        ]);
    }

    public function parseStatement(Request $request, BankReconciliation $bankReconciliation): RedirectResponse
    {
        $this->authorizeReconciliationAccess($bankReconciliation);
        $this->assertEditable($bankReconciliation);

        if ($bankReconciliation->dokumen_id === null) {
            $validated = $request->validate([
                'dokumen_id' => ['required', 'integer', 'exists:dokumens,id'],
            ]);

            $dokumen = Dokumen::query()->findOrFail((int) $validated['dokumen_id']);
            abort_unless($dokumen->type === 'koran', 422, 'Selected dokumen is not a koran.');
            abort_unless((int) $dokumen->giro_id === (int) $bankReconciliation->giro_id, 422, 'Koran does not belong to this giro.');

            if ($bankReconciliation->periode !== null && $dokumen->getRawOriginal('periode') !== null) {
                $recMonth = $bankReconciliation->periode->format('Y-m');
                $docMonth = Carbon::parse($dokumen->getRawOriginal('periode'))->format('Y-m');
                abort_unless($recMonth === $docMonth, 422, 'Koran period must match reconciliation period.');
            }

            $bankReconciliation->update(['dokumen_id' => $dokumen->id]);
            $bankReconciliation->refresh();
        }

        $bankReconciliation->update([
            'status' => BankReconciliation::STATUS_PROCESSING,
            'notes' => null,
        ]);

        ParseBankStatementJob::dispatch($bankReconciliation->id);

        return back()->with('success', 'PDF parsing job queued.');
    }

    public function fetchSapLines(BankReconciliation $bankReconciliation): RedirectResponse
    {
        $this->authorizeReconciliationAccess($bankReconciliation);
        $this->assertEditable($bankReconciliation);

        FetchSapGlLinesJob::dispatch($bankReconciliation->id);

        return back()->with('success', 'SAP GL fetch job queued.');
    }

    public function autoMatch(BankReconciliation $bankReconciliation): RedirectResponse
    {
        $this->authorizeReconciliationAccess($bankReconciliation);
        $this->assertEditable($bankReconciliation);

        AutoMatchReconciliationJob::dispatch($bankReconciliation->id);

        return back()->with('success', 'Auto-match job queued.');
    }

    public function manualMatch(ManualMatchGroupBankReconciliationRequest $request, BankReconciliation $bankReconciliation, ReconciliationMatchingService $matchingService): RedirectResponse
    {
        $this->authorizeReconciliationAccess($bankReconciliation);
        $this->assertEditable($bankReconciliation);

        $validated = $request->validated();

        $bankLines = BankStatementLine::query()
            ->whereIn('id', array_map('intval', $validated['bank_statement_line_ids']))
            ->orderBy('id')
            ->get()
            ->all();

        $sapLines = SapGlLine::query()
            ->whereIn('id', array_map('intval', $validated['sap_gl_line_ids']))
            ->orderBy('id')
            ->get()
            ->all();

        $matchingService->manualGroup($bankReconciliation, $bankLines, $sapLines);

        return back()->with('success', 'Lines matched as one group.');
    }

    public function unmatch(BankReconciliation $bankReconciliation, ReconciliationMatchGroup $reconciliationMatchGroup, ReconciliationMatchingService $matchingService): RedirectResponse
    {
        $this->authorizeReconciliationAccess($bankReconciliation);
        $this->assertEditable($bankReconciliation);

        abort_unless((int) $reconciliationMatchGroup->bank_reconciliation_id === (int) $bankReconciliation->id, 404);

        $matchingService->deleteMatchGroup($reconciliationMatchGroup);

        return back()->with('success', 'Match group removed.');
    }

    public function storeLine(StoreBankStatementLineRequest $request, BankReconciliation $bankReconciliation): RedirectResponse
    {
        $this->authorizeReconciliationAccess($bankReconciliation);
        $this->assertEditable($bankReconciliation);

        $validated = $request->validated();
        $maxOrder = (int) $bankReconciliation->bankStatementLines()->max('line_order');

        BankStatementLine::create([
            'bank_reconciliation_id' => $bankReconciliation->id,
            'transaction_date' => $validated['transaction_date'] ?? null,
            'value_date' => $validated['value_date'] ?? null,
            'description' => $validated['description'] ?? null,
            'reference' => $validated['reference'] ?? null,
            'debit' => number_format((float) $validated['debit'], 2, '.', ''),
            'credit' => number_format((float) $validated['credit'], 2, '.', ''),
            'balance' => isset($validated['balance']) ? number_format((float) $validated['balance'], 2, '.', '') : null,
            'line_notes' => $validated['line_notes'] ?? null,
            'is_ai_extracted' => false,
            'matched_status' => BankStatementLine::MATCH_UNMATCHED,
            'line_order' => $maxOrder + 1,
        ]);

        return back()->with('success', 'Bank statement line added.');
    }

    public function updateLine(StoreBankStatementLineRequest $request, BankReconciliation $bankReconciliation, BankStatementLine $bankStatementLine): RedirectResponse
    {
        $this->authorizeReconciliationAccess($bankReconciliation);
        $this->assertEditable($bankReconciliation);

        abort_unless((int) $bankStatementLine->bank_reconciliation_id === (int) $bankReconciliation->id, 404);
        abort_unless($bankStatementLine->matched_status === BankStatementLine::MATCH_UNMATCHED, 422, 'Only unmatched lines can be edited.');

        $validated = $request->validated();

        $bankStatementLine->update([
            'transaction_date' => $validated['transaction_date'] ?? null,
            'value_date' => $validated['value_date'] ?? null,
            'description' => $validated['description'] ?? null,
            'reference' => $validated['reference'] ?? null,
            'debit' => number_format((float) $validated['debit'], 2, '.', ''),
            'credit' => number_format((float) $validated['credit'], 2, '.', ''),
            'balance' => isset($validated['balance']) ? number_format((float) $validated['balance'], 2, '.', '') : null,
            'line_notes' => $validated['line_notes'] ?? null,
            'is_ai_extracted' => false,
        ]);

        return back()->with('success', 'Bank statement line updated.');
    }

    public function destroyLine(BankReconciliation $bankReconciliation, BankStatementLine $bankStatementLine): RedirectResponse
    {
        $this->authorizeReconciliationAccess($bankReconciliation);
        $this->assertEditable($bankReconciliation);

        abort_unless((int) $bankStatementLine->bank_reconciliation_id === (int) $bankReconciliation->id, 404);
        abort_unless($bankStatementLine->matched_status === BankStatementLine::MATCH_UNMATCHED, 422, 'Only unmatched lines can be deleted.');

        $bankStatementLine->delete();

        return back()->with('success', 'Bank statement line deleted.');
    }

    public function excludeBankLine(Request $request, BankReconciliation $bankReconciliation, BankStatementLine $bankStatementLine): RedirectResponse
    {
        $this->authorizeReconciliationAccess($bankReconciliation);
        $this->assertEditable($bankReconciliation);

        abort_unless((int) $bankStatementLine->bank_reconciliation_id === (int) $bankReconciliation->id, 404);

        if ($bankStatementLine->matched_status === BankStatementLine::MATCH_EXCLUDED) {
            $bankStatementLine->update([
                'matched_status' => BankStatementLine::MATCH_UNMATCHED,
                'exclude_reason' => null,
            ]);

            return back()->with('success', 'Bank line included again.');
        }

        abort_unless(
            in_array($bankStatementLine->matched_status, [BankStatementLine::MATCH_UNMATCHED], true),
            422,
            'Only unmatched lines can be excluded.'
        );

        $request->validate([
            'exclude_reason' => ['required', 'string', 'min:1', 'max:500'],
        ]);

        $bankStatementLine->update([
            'matched_status' => BankStatementLine::MATCH_EXCLUDED,
            'exclude_reason' => $request->input('exclude_reason'),
        ]);

        return back()->with('success', 'Bank line excluded from reconciliation totals.');
    }

    public function excludeSapLine(Request $request, BankReconciliation $bankReconciliation, SapGlLine $sapGlLine): RedirectResponse
    {
        $this->authorizeReconciliationAccess($bankReconciliation);
        $this->assertEditable($bankReconciliation);

        abort_unless((int) $sapGlLine->bank_reconciliation_id === (int) $bankReconciliation->id, 404);

        if ($sapGlLine->matched_status === SapGlLine::MATCH_EXCLUDED) {
            $sapGlLine->update([
                'matched_status' => SapGlLine::MATCH_UNMATCHED,
                'exclude_reason' => null,
            ]);

            return back()->with('success', 'SAP line included again.');
        }

        abort_unless(
            in_array($sapGlLine->matched_status, [SapGlLine::MATCH_UNMATCHED], true),
            422,
            'Only unmatched lines can be excluded.'
        );

        $request->validate([
            'exclude_reason' => ['required', 'string', 'min:1', 'max:500'],
        ]);

        $sapGlLine->update([
            'matched_status' => SapGlLine::MATCH_EXCLUDED,
            'exclude_reason' => $request->input('exclude_reason'),
        ]);

        return back()->with('success', 'SAP line excluded from reconciliation totals.');
    }

    public function submitForValidation(BankReconciliation $bankReconciliation, ReconciliationBalanceService $balanceService): RedirectResponse
    {
        $this->authorizeReconciliationAccess($bankReconciliation);
        $this->assertEditable($bankReconciliation);

        abort_if(
            $bankReconciliation->validation_status === BankReconciliation::VALIDATION_PENDING,
            422,
            'Reconciliation is already pending validation.'
        );

        if (! $balanceService->isBalanced($bankReconciliation)) {
            $summary = $balanceService->summary($bankReconciliation);

            return back()->withErrors([
                'balance' => 'Cannot submit: bank net ('.number_format($summary['bank_net'], 2)
                    .') must equal book net ('.number_format($summary['book_net'], 2)
                    .'). Difference: '.number_format($summary['difference'], 2),
            ]);
        }

        $bankReconciliation->update([
            'validation_status' => BankReconciliation::VALIDATION_PENDING,
            'submitted_by' => Auth::id(),
            'submitted_at' => now(),
            'rejection_reason' => null,
            'status' => BankReconciliation::STATUS_IN_REVIEW,
        ]);

        return back()->with('success', 'Reconciliation submitted for validation.');
    }

    public function validateReconciliation(BankReconciliation $bankReconciliation): RedirectResponse
    {
        $this->authorizeReconciliationAccess($bankReconciliation);
        $this->assertCanValidate($bankReconciliation);

        abort_unless(
            $bankReconciliation->validation_status === BankReconciliation::VALIDATION_PENDING,
            422,
            'Reconciliation is not pending validation.'
        );

        $bankReconciliation->update([
            'validation_status' => BankReconciliation::VALIDATION_VALIDATED,
            'status' => BankReconciliation::STATUS_COMPLETED,
            'validated_by' => Auth::id(),
            'validated_at' => now(),
            'reconciled_by' => Auth::id(),
            'reconciled_at' => now(),
            'rejection_reason' => null,
        ]);

        return redirect()
            ->route('cashier.bank-reconciliation.report', $bankReconciliation)
            ->with('success', 'Reconciliation validated and marked complete.');
    }

    public function reject(RejectBankReconciliationRequest $request, BankReconciliation $bankReconciliation): RedirectResponse
    {
        $this->authorizeReconciliationAccess($bankReconciliation);
        $this->assertCanValidate($bankReconciliation);

        abort_unless(
            $bankReconciliation->validation_status === BankReconciliation::VALIDATION_PENDING,
            422,
            'Reconciliation is not pending validation.'
        );

        $bankReconciliation->update([
            'validation_status' => BankReconciliation::VALIDATION_REJECTED,
            'status' => BankReconciliation::STATUS_IN_REVIEW,
            'rejection_reason' => $request->validated('rejection_reason'),
            'validated_by' => Auth::id(),
            'validated_at' => now(),
            'submitted_by' => null,
            'submitted_at' => null,
        ]);

        return back()->with('success', 'Reconciliation rejected and returned for revision.');
    }

    public function report(BankReconciliation $bankReconciliation, ReconciliationBalanceService $balanceService): View
    {
        $this->authorizeReconciliationAccess($bankReconciliation);

        $bankReconciliation->load([
            'giro.bank',
            'bankStatementLines',
            'sapGlLines',
            'submittedBy',
            'validatedBy',
            'creator',
        ]);

        $unmatchedBank = $bankReconciliation->bankStatementLines->where('matched_status', BankStatementLine::MATCH_UNMATCHED);
        $unmatchedSap = $bankReconciliation->sapGlLines->where('matched_status', SapGlLine::MATCH_UNMATCHED);

        $outstandingBankNet = $unmatchedBank->sum(fn ($line) => (float) $line->debit - (float) $line->credit);
        $outstandingSapNet = $unmatchedSap->sum(fn ($line) => (float) $line->debit - (float) $line->credit);

        $balanceSummary = $balanceService->summary($bankReconciliation);

        return view('cashier.bank-reconciliation.report', compact(
            'bankReconciliation',
            'unmatchedBank',
            'unmatchedSap',
            'outstandingBankNet',
            'outstandingSapNet',
            'balanceSummary'
        ));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Giro>
     */
    protected function accessibleGiros()
    {
        $roles = app(UserController::class)->getUserRoles();

        if (array_intersect($this->elevatedRoles, $roles)) {
            return Giro::query()->with('bank')->orderBy('bank_id')->orderBy('acc_no')->get();
        }

        return Giro::query()->with('bank')->where('project', Auth::user()->project)->orderBy('bank_id')->orderBy('acc_no')->get();
    }

    protected function authorizeGiroAccess(Giro $giro): void
    {
        $roles = app(UserController::class)->getUserRoles();
        if (array_intersect($this->elevatedRoles, $roles)) {
            return;
        }

        abort_unless($giro->project === Auth::user()->project, 403);
    }

    protected function authorizeReconciliationAccess(BankReconciliation $bankReconciliation): void
    {
        $bankReconciliation->loadMissing('giro');
        if ($bankReconciliation->giro === null) {
            abort(404);
        }

        $this->authorizeGiroAccess($bankReconciliation->giro);
    }

    protected function assertEditable(BankReconciliation $bankReconciliation): void
    {
        abort_if($bankReconciliation->isLockedForEditing(), 422, 'Reconciliation is locked and cannot be edited.');
    }

    protected function assertCanValidate(BankReconciliation $bankReconciliation): void
    {
        abort_unless(Auth::user()?->can('validate_bank_reconciliation'), 403);

        abort_if(
            $bankReconciliation->isPreparer((int) Auth::id()),
            403,
            'You cannot validate a reconciliation you prepared or submitted.'
        );
    }
}
