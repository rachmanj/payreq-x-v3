<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Installment;
use App\Models\Loan;
use Illuminate\Http\Request;

class InstallmentController extends Controller
{
    public function generate($loan_id)
    {
        $loan = Loan::find($loan_id);
        $accounts = Account::where('type', 'bank')->get();

        return view('accounting.loans.installments.generate', compact(['loan', 'accounts']));
    }

    public function store_generate(Request $request)
    {
        $loan_id = $request->loan_id;
        $first_installment_date = $request->start_due_date;
        $tenor = $request->tenor;
        $installment_amount = $request->installment_amount;
        $start_angsuran_ke = $request->start_angsuran_ke;
        $batch = Installment::max('batch') + 1;

        $first_installment_date = new \DateTime($first_installment_date);
        for ($i = 1; $i <= $tenor; $i++) {
            $installment = new Installment();
            $installment->loan_id = $loan_id;
            $installment->due_date = $first_installment_date->format('Y-m-d');
            $installment->bilyet_amount = $installment_amount;
            $installment->angsuran_ke = $start_angsuran_ke;
            $installment->created_by = auth()->user()->id;
            $installment->batch = $batch;
            $installment->account_id = $request->account_id;
            $installment->save();

            $first_installment_date->add(new \DateInterval('P1M'));
            $start_angsuran_ke++;
        }

        return redirect()->route('accounting.loans.show', $loan_id)->with('success', 'Angsuran berhasil di-generate');
    }

    public function update(Request $request)
    {
        $installment = Installment::find($request->installment_id);

        $status = $request->paid_date ? 'paid' : null;

        $installment->due_date = $request->due_date;
        $installment->paid_date = $request->paid_date;
        $installment->bilyet_no = $request->bilyet_no;
        $installment->bilyet_amount = $request->bilyet_amount;
        $installment->account_id = $request->account_id;
        $installment->status = $status;
        $installment->save();

        return redirect()->route('accounting.loans.show', $installment->loan_id)->with('success', 'Angsuran berhasil di-update');
    }

    public function destroy($id)
    {
        $installment = Installment::find($id);
        $loan_id = $installment->loan_id;
        $installment->delete();

        return redirect()->route('accounting.loans.show', $loan_id)->with('success', 'Angsuran berhasil dihapus');
    }


    public function data($loan_id)
    {
        $instalments = Installment::where('loan_id', $loan_id)->orderBy('due_date', 'asc')->get();

        return datatables()->of($instalments)
            ->editColumn('due_date', function ($instalment) {
                return \Carbon\Carbon::parse($instalment->due_date)->format('d-M-Y');
            })
            ->editColumn('paid_date', function ($instalment) {
                return $instalment->paid_date ? \Carbon\Carbon::parse($instalment->paid_date)->format('d-M-Y') : '';
            })
            ->editColumn('bilyet_amount', function ($instalment) {
                return number_format($instalment->bilyet_amount, 2, ',', '.');
            })
            ->addColumn('created_by', function ($instalment) {
                return $instalment->user->name;
            })
            ->addColumn('account', function ($instalment) {
                return $instalment->account->account_number;
            })
            ->addIndexColumn()
            ->addColumn('action', 'accounting.loans.installments.action')
            ->toJson();
    }
}
