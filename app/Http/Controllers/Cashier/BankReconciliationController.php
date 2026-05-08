<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use App\Http\Requests\ManualMatchBankReconciliationRequest;
use App\Http\Requests\StoreBankReconciliationRequest;
use App\Jobs\AutoMatchReconciliationJob;
use App\Jobs\FetchSapGlLinesJob;
use App\Jobs\ParseBankStatementJob;
use App\Models\BankReconciliation;
use App\Models\BankStatementLine;
use App\Models\Dokumen;
use App\Models\Giro;
use App\Models\SapGlLine;
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

    public function index(): View
    {
        $query = BankReconciliation::query()
            ->with(['giro.bank'])
            ->orderByDesc('periode')
            ->orderByDesc('id');

        $roles = app(UserController::class)->getUserRoles();
        if (! array_intersect($this->elevatedRoles, $roles)) {
            $project = Auth::user()->project;
            $query->whereHas('giro', function ($q) use ($project): void {
                $q->where('project', $project);
            });
        }

        $reconciliations = $query->paginate(20)->withQueryString();

        return view('cashier.bank-reconciliation.index', compact('reconciliations'));
    }

    public function create(Request $request): View
    {
        $giros = $this->accessibleGiros();

        $prefill = [
            'giro_id' => $request->integer('giro_id'),
            'periode' => $request->input('periode'),
            'dokumen_id' => $request->integer('dokumen_id'),
        ];

        $dokumens = collect();
        if ($prefill['giro_id'] > 0) {
            $dokumens = Dokumen::query()
                ->where('giro_id', $prefill['giro_id'])
                ->where('type', 'koran')
                ->orderByDesc('periode')
                ->get();
        }

        return view('cashier.bank-reconciliation.create', compact('giros', 'prefill', 'dokumens'));
    }

    public function store(StoreBankReconciliationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $giro = Giro::query()->findOrFail((int) $validated['giro_id']);
        $this->authorizeGiroAccess($giro);

        $periode = Carbon::parse((string) $validated['periode'])->startOfMonth()->format('Y-m-d');

        $reconciliation = BankReconciliation::create([
            'giro_id' => $giro->id,
            'dokumen_id' => (int) $validated['dokumen_id'],
            'periode' => $periode,
            'status' => BankReconciliation::STATUS_PROCESSING,
            'created_by' => Auth::id(),
        ]);

        ParseBankStatementJob::dispatch($reconciliation->id);
        FetchSapGlLinesJob::dispatch($reconciliation->id);

        return redirect()
            ->route('cashier.bank-reconciliation.show', $reconciliation)
            ->with('success', 'Bank reconciliation started. PDF parsing and SAP fetch are running in the queue.');
    }

    public function show(BankReconciliation $bankReconciliation): View
    {
        $this->authorizeReconciliationAccess($bankReconciliation);

        $bankReconciliation->load([
            'giro.bank',
            'dokumen',
            'bankStatementLines' => fn ($q) => $q->orderBy('line_order')->orderBy('id'),
            'sapGlLines' => fn ($q) => $q->orderBy('posting_date')->orderBy('id'),
            'matches',
        ]);

        return view('cashier.bank-reconciliation.show', compact('bankReconciliation'));
    }

    public function status(BankReconciliation $bankReconciliation): JsonResponse
    {
        $this->authorizeReconciliationAccess($bankReconciliation);

        return response()->json([
            'status' => $bankReconciliation->status,
            'bank_lines_count' => $bankReconciliation->bankStatementLines()->count(),
            'sap_lines_count' => $bankReconciliation->sapGlLines()->count(),
            'matches_count' => $bankReconciliation->matches()->count(),
        ]);
    }

    public function parseStatement(BankReconciliation $bankReconciliation): RedirectResponse
    {
        $this->authorizeReconciliationAccess($bankReconciliation);

        abort_unless($bankReconciliation->dokumen_id !== null, 422, 'No dokumen attached.');

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

        FetchSapGlLinesJob::dispatch($bankReconciliation->id);

        return back()->with('success', 'SAP GL fetch job queued.');
    }

    public function autoMatch(BankReconciliation $bankReconciliation): RedirectResponse
    {
        $this->authorizeReconciliationAccess($bankReconciliation);

        abort_if($bankReconciliation->status === BankReconciliation::STATUS_COMPLETED, 422);

        AutoMatchReconciliationJob::dispatch($bankReconciliation->id);

        return back()->with('success', 'Auto-match job queued.');
    }

    public function manualMatch(ManualMatchBankReconciliationRequest $request, BankReconciliation $bankReconciliation, ReconciliationMatchingService $matchingService): RedirectResponse
    {
        $this->authorizeReconciliationAccess($bankReconciliation);

        abort_if($bankReconciliation->status === BankReconciliation::STATUS_COMPLETED, 422);

        $bankLine = BankStatementLine::query()->findOrFail((int) $request->validated()['bank_statement_line_id']);
        $sapLine = SapGlLine::query()->findOrFail((int) $request->validated()['sap_gl_line_id']);

        $matchingService->manualPair($bankReconciliation, $bankLine, $sapLine);

        return back()->with('success', 'Lines matched.');
    }

    public function complete(BankReconciliation $bankReconciliation): RedirectResponse
    {
        $this->authorizeReconciliationAccess($bankReconciliation);

        abort_if($bankReconciliation->status === BankReconciliation::STATUS_COMPLETED, 422);

        $bankReconciliation->update([
            'status' => BankReconciliation::STATUS_COMPLETED,
            'reconciled_by' => Auth::id(),
            'reconciled_at' => now(),
        ]);

        return redirect()
            ->route('cashier.bank-reconciliation.report', $bankReconciliation)
            ->with('success', 'Reconciliation marked complete.');
    }

    public function report(BankReconciliation $bankReconciliation): View
    {
        $this->authorizeReconciliationAccess($bankReconciliation);

        $bankReconciliation->load([
            'giro.bank',
            'bankStatementLines',
            'sapGlLines',
            'matches',
        ]);

        $unmatchedBank = $bankReconciliation->bankStatementLines->where('matched_status', BankStatementLine::MATCH_UNMATCHED);
        $unmatchedSap = $bankReconciliation->sapGlLines->where('matched_status', SapGlLine::MATCH_UNMATCHED);

        $outstandingBankNet = $unmatchedBank->sum(fn ($line) => (float) $line->debit - (float) $line->credit);
        $outstandingSapNet = $unmatchedSap->sum(fn ($line) => (float) $line->debit - (float) $line->credit);

        return view('cashier.bank-reconciliation.report', compact(
            'bankReconciliation',
            'unmatchedBank',
            'unmatchedSap',
            'outstandingBankNet',
            'outstandingSapNet'
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
}
