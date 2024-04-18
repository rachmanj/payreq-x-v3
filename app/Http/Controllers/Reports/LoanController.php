<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Installment;
use App\Models\Parameter;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index()
    {
        $saldo = Parameter::where('name1', 'account_balance')->where('name2', '1490004194751')->first();

        return view('reports.loan.index', compact('saldo'));
    }

    public function index_7997()
    {
        return view('reports.loan.index_7997');
    }

    public function index_all()
    {
        return view('reports.loan.index_all');
    }

    public function update(Request $request)
    {
        $installment = Installment::find($request->installment_id);

        $status = $request->paid_date ? 'paid' : null;

        $installment->paid_date = $request->paid_date;
        $installment->status = $status;
        $installment->bilyet_no = $request->bilyet_no;
        $installment->account_id = $request->account_id;
        $installment->save();

        return redirect()->route('reports.loan.index');
    }

    public function data(Request $request)
    {
        if ($request->akun_no === 'all') {
            $installments = Installment::with('loan')
                ->where('due_date', '<=', date('Y-m-d', strtotime('last day of this month')))
                ->whereNull('paid_date')
                ->orderBy('due_date', 'asc')
                ->get();
        } else {
            $akun = Account::where('account_number', $request->akun_no)->first();

            if ($akun) {
                $akun_id = $akun->id;
            } else {
                $akun_id = "";
            }

            $installments = Installment::with('loan')
                ->where('account_id', $akun_id)
                ->where('due_date', '<=', date('Y-m-d', strtotime('last day of this month')))
                ->whereNull('paid_date')
                ->orderBy('due_date', 'asc')
                ->get();
        }

        return datatables()->of($installments)
            ->addColumn('creditor', function ($installment) {
                return $installment->loan->creditor->name;
            })
            ->editColumn('due_date', function ($installment) {
                return date('d-M-Y', strtotime($installment->due_date));
            })
            ->editColumn('bilyet_amount', function ($installment) {
                return number_format($installment->bilyet_amount, 2, ',', '.');
            })
            ->addIndexColumn()
            ->addColumn('action', 'reports.loan.action')
            ->toJson();
    }

    public function paid_data(Request $request)
    {
        if ($request->akun_no === 'all') {
            $installments = Installment::with('loan')
                ->whereMonth('paid_date', date('m'))
                ->whereNotNull('paid_date')
                ->orderBy('due_date', 'asc')
                ->get();
        } else {
            $akun = Account::where('account_number', $request->akun_no)->first();

            if ($akun) {
                $akun_id = $akun->id;
            } else {
                $akun_id = "";
            }

            $installments = Installment::with('loan')
                ->where('account_id', $akun_id)
                ->whereMonth('paid_date', date('m'))
                ->whereNotNull('paid_date')
                ->orderBy('due_date', 'asc')
                ->get();
        }

        return datatables()->of($installments)
            ->addColumn('creditor', function ($installment) {
                return $installment->loan->creditor->name;
            })
            ->editColumn('due_date', function ($installment) {
                return date('d-M-Y', strtotime($installment->due_date));
            })
            ->editColumn('paid_date', function ($installment) {
                return date('d-M-Y', strtotime($installment->paid_date));
            })
            ->editColumn('bilyet_amount', function ($installment) {
                return number_format($installment->bilyet_amount, 2, ',', '.');
            })
            ->addIndexColumn()
            ->addColumn('action', 'reports.loan.action')
            ->toJson();
    }

    public function dashboard()
    {
        $dashboard_data = $this->dashboard_data();

        // return $dashboard_data;

        return view('reports.loan.dashboard', compact('dashboard_data'));
    }

    public function dashboard_data()
    {
        $dashboard_data = [
            'outstanding_installment_amount_this_month' => $this->outstanding_installment_amount_this_month(),
            'outstanding_installment_amount' => $this->outstanding_installment_amount(),
            'paid_this_month' => $this->paid_this_month(),
            // 'outstanding_installment_amount_by_creditors' => $this->outstanding_installment_amount_by_creditors(),
            // 'outstanding_installment_amount_by_loan_code' => $this->outstanding_installment_amount_by_loan_code(),
            'outstanding_installment_amount_by_creditors_detail' => $this->outstanding_installment_amount_by_creditors_detail(),
        ];

        return $dashboard_data;
    }

    public function outstanding_installment_amount_this_month()
    {
        $installments = Installment::where('due_date', '<=', date('Y-m-d', strtotime('last day of this month')))
            ->whereNull('paid_date')
            ->sum('bilyet_amount');

        return number_format($installments, 2);
    }

    public function paid_this_month()
    {
        $installments = Installment::whereMonth('paid_date', date('m'))
            ->whereNotNull('paid_date')
            ->sum('bilyet_amount');

        return number_format($installments, 2);
    }

    public function outstanding_installment_amount()
    {
        $installments = Installment::whereNull('paid_date')
            ->sum('bilyet_amount');

        return number_format($installments, 2);
    }

    public function outstanding_installment_amount_by_loan_code()
    {
        $installments = Installment::whereNull('paid_date')
            ->join('loans', 'installments.loan_id', '=', 'loans.id')
            ->join('creditors', 'loans.creditor_id', '=', 'creditors.id')
            ->groupBy('installments.loan_id', 'creditors.name', 'loans.loan_code', 'loans.description')
            ->selectRaw('sum(installments.bilyet_amount) as total, installments.loan_id, creditors.name as lessor_name, loans.loan_code, loans.description, count(installments.id) as number_of_installments_left')
            ->orderBy('creditors.name', 'asc')
            ->get();

        return $installments;
    }

    public function outstanding_installment_amount_by_creditors()
    {
        $installments = Installment::whereNull('paid_date')
            ->join('loans', 'installments.loan_id', '=', 'loans.id')
            ->join('creditors', 'loans.creditor_id', '=', 'creditors.id')
            ->groupBy('creditors.name', 'creditors.id')
            ->selectRaw('sum(installments.bilyet_amount) as total, creditors.name as creditor_name, creditors.id as creditor_id, count(distinct loans.id) as number_of_loans')
            ->get();

        // Add index to the collection
        $installments = $installments->map(function ($item, $index) {
            $item->index = $index + 1;
            return $item;
        });

        return $installments;
    }

    public function outstanding_installment_amount_by_creditors_detail()
    {
        $creditors = $this->outstanding_installment_amount_by_creditors();

        $creditors_detail = [];

        foreach ($creditors as $creditor) {
            $installments = Installment::whereNull('paid_date')
                ->join('loans', 'installments.loan_id', '=', 'loans.id')
                ->where('loans.creditor_id', $creditor->creditor_id)
                ->groupBy('loans.loan_code', 'loans.description')
                ->selectRaw('sum(installments.bilyet_amount) as total, count(installments.id) as number_of_installments_left, loans.loan_code, loans.description')
                ->get();

            // Add index to the collection
            $installments = $installments->map(function ($item, $index) {
                $item->index = $index + 1;
                return $item;
            });

            $creditor->installments = $installments;
        }

        return $creditors;
    }
}
