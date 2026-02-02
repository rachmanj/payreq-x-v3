<?php

namespace App\Http\Controllers;

use App\Models\Creditor;
use App\Models\Loan;
use App\Models\Installment;
use App\Models\LoanAudit;
use App\Events\LoanCreated;
use App\Events\LoanUpdated;
use App\Events\LoanStatusChanged;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index()
    {
        return view('accounting.loans.index');
    }

    public function dashboard()
    {
        $statistics = [
            'total_loans' => Loan::count(),
            'installments_due_this_month' => Installment::whereMonth('due_date', now()->month)
                ->whereYear('due_date', now()->year)
                ->unpaid()
                ->count(),
            'total_outstanding' => Installment::unpaid()->sum('bilyet_amount'),
            'overdue_installments' => Installment::where('due_date', '<', now())
                ->unpaid()
                ->count(),
        ];

        $upcoming_installments = Installment::with(['loan.creditor'])
            ->whereBetween('due_date', [now(), now()->addDays(7)])
            ->unpaid()
            ->orderBy('due_date', 'asc')
            ->limit(10)
            ->get();

        $recent_payments = Installment::with(['loan.creditor', 'bilyet'])
            ->whereMonth('paid_date', now()->month)
            ->whereYear('paid_date', now()->year)
            ->paid()
            ->orderBy('paid_date', 'desc')
            ->limit(10)
            ->get();

        $payment_method_stats = Installment::whereMonth('paid_date', now()->month)
            ->whereYear('paid_date', now()->year)
            ->paid()
            ->selectRaw('payment_method, COUNT(*) as count')
            ->groupBy('payment_method')
            ->pluck('count', 'payment_method')
            ->toArray();

        $loans_by_creditor = Loan::with(['creditor', 'installments'])
            ->get()
            ->groupBy('creditor_id')
            ->map(function ($loans) {
                $creditor = $loans->first()->creditor;
                return [
                    'name' => $creditor->name,
                    'loan_count' => $loans->count(),
                    'outstanding' => $loans->sum(function ($loan) {
                        return $loan->installments()->unpaid()->sum('bilyet_amount');
                    }),
                    'unpaid_installments' => $loans->sum(function ($loan) {
                        return $loan->installments()->unpaid()->count();
                    }),
                    'loans' => $loans->map(function ($loan) {
                        return [
                            'id' => $loan->id,
                            'code' => $loan->loan_code,
                            'description' => $loan->description,
                            'principal' => $loan->principal,
                            'unpaid_count' => $loan->installments()->unpaid()->count(),
                        ];
                    })->toArray()
                ];
            })
            ->values()
            ->toArray();

        return view('accounting.loans.dashboard', compact(
            'statistics',
            'upcoming_installments',
            'recent_payments',
            'payment_method_stats',
            'loans_by_creditor'
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

        $loan = new Loan();
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
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $audits = $query->paginate(50);

        return view('accounting.loans.audit', compact('audits'));
    }

    public function auditShow($id)
    {
        $audit = LoanAudit::with(['loan.creditor', 'user'])->findOrFail($id);

        return view('accounting.loans.audit_detail', compact('audit'));
    }
}
