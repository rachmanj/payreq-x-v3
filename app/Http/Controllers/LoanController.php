<?php

namespace App\Http\Controllers;

use App\Models\Creditor;
use App\Models\Loan;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index()
    {
        return view('accounting.loans.index');
    }

    public function create()
    {
        $creditors = Creditor::get();

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

        return redirect()->route('accounting.loans.index')->with('success', 'Loan created successfully');
    }

    public function edit($id)
    {
        $loan = Loan::find($id);
        $creditors = Creditor::get();

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
        $loan->loan_code = $request->loan_code;
        $loan->creditor_id = $request->creditor_id;
        $loan->start_date = $request->start_date;
        $loan->principal = $request->principal;
        $loan->tenor = $request->tenor;
        $loan->description = $request->description;
        $loan->user_id = auth()->id();
        $loan->status = $request->status;
        $loan->save();

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
        $loans = Loan::get();

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
}
