<?php

namespace App\Http\Controllers;

use App\Events\LoanCreated;
use App\Events\LoanStatusChanged;
use App\Events\LoanUpdated;
use App\Models\Creditor;
use App\Models\Installment;
use App\Models\Loan;
use App\Models\LoanAudit;
use App\Services\InstallmentImport\InstallmentScheduleImportService;
use App\Services\InstallmentOpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoanController extends Controller
{
    public function index()
    {
        return view('accounting.loans.index');
    }

    public function dashboard()
    {
        $today = now()->startOfDay();
        $weekEnd = now()->addDays(7)->endOfDay();

        $dueWeekQuery = Installment::query()
            ->whereBetween('due_date', [$today, $weekEnd])
            ->unpaid();

        $dueTodayQuery = Installment::query()
            ->whereDate('due_date', $today)
            ->unpaid();

        $dueWeek = [
            'count' => (clone $dueWeekQuery)->count(),
            'total' => (float) (clone $dueWeekQuery)->sum('bilyet_amount'),
        ];

        $dueToday = [
            'count' => (clone $dueTodayQuery)->count(),
            'total' => (float) (clone $dueTodayQuery)->sum('bilyet_amount'),
        ];

        $fundsPerBank = Installment::query()
            ->select(
                DB::raw('COALESCE(installments.account_id, loans.account_id) as bank_account_id'),
                DB::raw('COUNT(*) as installment_count'),
                DB::raw('SUM(installments.bilyet_amount) as total_amount')
            )
            ->join('loans', 'loans.id', '=', 'installments.loan_id')
            ->whereBetween('installments.due_date', [$today, $weekEnd])
            ->unpaid()
            ->groupBy(DB::raw('COALESCE(installments.account_id, loans.account_id)'))
            ->get()
            ->map(function ($row) {
                $account = \App\Models\Account::query()->find($row->bank_account_id);

                return [
                    'account_id' => $row->bank_account_id,
                    'account_label' => $account
                        ? $account->account_number.' — '.($account->account_name ?? '')
                        : 'Belum di-set',
                    'count' => (int) $row->installment_count,
                    'total' => (float) $row->total_amount,
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();

        $bilyetCairTanpaOp = Installment::query()
            ->whereNotNull('sap_ap_doc_num')
            ->whereNull('sap_payment_doc_num')
            ->unpaid()
            ->where(function ($query) {
                $query->where('payment_method', 'auto_debit')
                    ->orWhereHas('bilyet', function ($bilyetQuery) {
                        $bilyetQuery->where('status', 'cair');
                    });
            })
            ->count();

        $remainingPerContract = Loan::query()
            ->withCount(['installments as unpaid_count' => function ($query) {
                $query->unpaid();
            }])
            ->withSum(['installments as unpaid_total' => function ($query) {
                $query->unpaid();
            }], 'bilyet_amount')
            ->having('unpaid_count', '>', 0)
            ->orderByDesc('unpaid_total')
            ->limit(10)
            ->get();

        return view('accounting.loans.dashboard', compact(
            'dueWeek',
            'dueToday',
            'fundsPerBank',
            'bilyetCairTanpaOp',
            'remainingPerContract'
        ));
    }

    public function create()
    {
        $creditors = Creditor::with('sapBusinessPartner')->get();

        return view('accounting.loans.create', compact('creditors'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'principal' => 'required',
            'creditor_id' => 'required',
            'start_date' => 'required',
            'tenor' => 'required',
        ]);

        $loan = new Loan;
        $loan->loan_code = $request->loan_code;
        $loan->creditor_id = $request->creditor_id;
        $loan->start_date = $request->start_date;
        $loan->principal = $request->principal;
        $loan->tenor = $request->tenor;
        $loan->description = $request->description;
        $loan->user_id = auth()->id();
        $loan->save();

        event(new LoanCreated($loan, auth()->user()));

        return redirect()->route('accounting.loans.index')->with('success', 'Loan created successfully');
    }

    public function edit($id)
    {
        $loan = Loan::find($id);
        $creditors = Creditor::with('sapBusinessPartner')->get();

        return view('accounting.loans.edit', compact(['loan', 'creditors']));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'principal' => 'required',
            'creditor_id' => 'required',
            'start_date' => 'required',
            'tenor' => 'required',
        ]);

        $loan = Loan::find($id);
        $oldValues = $loan->toArray();
        $oldStatus = $loan->status;

        $loan->loan_code = $request->loan_code;
        $loan->creditor_id = $request->creditor_id;
        $loan->start_date = $request->start_date;
        $loan->principal = $request->principal;
        $loan->tenor = $request->tenor;
        $loan->description = $request->description;
        $loan->user_id = auth()->id();
        $loan->status = $request->status;
        $loan->save();

        $newValues = $loan->toArray();

        event(new LoanUpdated($loan, auth()->user(), $oldValues, $newValues));

        if ($oldStatus !== $request->status) {
            event(new LoanStatusChanged($loan, auth()->user(), $oldStatus, $request->status));
        }

        return redirect()->route('accounting.loans.index')->with('success', 'Loan updated successfully');
    }

    public function show($id)
    {
        $loan = Loan::find($id);

        return view('accounting.loans.show', compact(['loan']));
    }

    public function destroy($id)
    {
        $loan = Loan::find($id);
        $loan->delete();

        return redirect()->route('accounting.loans.index')->with('success', 'Loan deleted successfully');
    }

    public function data()
    {
        $loans = Loan::orderBy('created_at', 'desc')->get();

        return datatables()->of($loans)
            ->editColumn('start_date', function ($loan) {
                return \Carbon\Carbon::parse($loan->start_date)->format('d-M-Y');
            })
            ->editColumn('principal', function ($loan) {
                return number_format($loan->principal, 0, ',', '.');
            })
            ->addColumn('creditor_name', function ($loan) {
                return $loan->creditor->name;
            })
            ->addColumn('created_by', function ($loan) {
                return $loan->user->name;
            })
            ->addIndexColumn()
            ->addColumn('action', 'accounting.loans.action')
            ->toJson();
    }

    public function history($id)
    {
        $loan = Loan::with(['creditor', 'user'])->findOrFail($id);
        $audits = LoanAudit::with('user')
            ->where('loan_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('accounting.loans.history', compact('loan', 'audits'));
    }

    public function auditIndex(Request $request)
    {
        $query = LoanAudit::with(['loan.creditor', 'user'])
            ->orderBy('created_at', 'desc');

        if ($request->action) {
            $query->byAction($request->action);
        }

        if ($request->date_from) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->where('created_at', '<=', $request->date_to.' 23:59:59');
        }

        $audits = $query->paginate(50);

        return view('accounting.loans.audit', compact('audits'));
    }

    public function auditShow($id)
    {
        $audit = LoanAudit::with(['loan.creditor', 'user'])->findOrFail($id);

        return view('accounting.loans.audit_detail', compact('audit'));
    }

    public function syncPaid(Request $request, InstallmentOpService $opService): JsonResponse
    {
        $validated = $request->validate([
            'loan_id' => 'nullable|integer|exists:loans,id',
        ]);

        $query = Installment::query()
            ->whereNotNull('sap_ap_doc_entry')
            ->unpaid();

        if (! empty($validated['loan_id'])) {
            $query->where('loan_id', $validated['loan_id']);
        }

        $installmentIds = $query->pluck('id')->all();

        if ($installmentIds === []) {
            return response()->json([
                'success' => true,
                'message' => 'Tidak ada angsuran dengan AP yang perlu disinkronkan.',
                'results' => [],
            ]);
        }

        $result = $opService->syncPaidFromSap($installmentIds);

        return response()->json([
            'success' => true,
            'message' => 'Sinkronisasi selesai.',
            'results' => $result['results'],
        ]);
    }

    public function importSchedulePreview(Request $request, Loan $loan, InstallmentScheduleImportService $importService): JsonResponse
    {
        $validated = $request->validate([
            'format' => 'required|string',
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $result = $importService->preview($validated['file'], $loan, $validated['format']);

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function importSchedule(Request $request, Loan $loan, InstallmentScheduleImportService $importService): JsonResponse
    {
        $validated = $request->validate([
            'preview_token' => 'required|string',
        ]);

        $result = $importService->import($validated['preview_token'], $loan);

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }
}
