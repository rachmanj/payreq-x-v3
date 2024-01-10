<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\CashOpname;
use Illuminate\Http\Request;

class CashOpnameController extends Controller
{
    public function index()
    {
        return view('cashier.pcbc.index');
    }

    public function create()
    {
        $app_balance = Account::where('project', auth()->user()->project)->where('type', 'cash')->first()->app_balance;
        $pcbc = CashOpname::create([
            'nomor' => app(DocumentNumberController::class)->generate_document_number('pcbc', auth()->user()->project),
            'project' => auth()->user()->project,
            'date' => date('Y-m-d'),
            'app_balance' => $app_balance,
            'cashier_id' => auth()->user()->id,
        ]);

        return redirect()->route('cashier.pcbc.edit', $pcbc->id);
    }

    public function show($id)
    {
        return view('cashier.pcbc.show');
    }

    public function edit($id)
    {
        $pcbc = CashOpname::findOrFail($id);

        return view('cashier.pcbc.edit', compact(['pcbc']));
    }

    public function update(Request $request, $id)
    {
        $pcbc = CashOpname::findOrFail($id);
        $data = array_filter($request->all(), function ($value) {
            return !is_null($value);
        });

        $pcbc->update($data);

        return redirect()->route('cashier.pcbc.index')->with('success', 'PCBC berhasil diupdate');
    }

    public function destroy($id)
    {
        $pcbc = CashOpname::findOrFail($id);
        $pcbc->delete();

        return redirect()->route('cashier.pcbc.index');
    }

    public function print($id)
    {
        $pcbc = CashOpname::findOrFail($id);

        return view('cashier.pcbc.print', compact(['pcbc']));
    }

    public function data()
    {
        if (auth()->user()->hasRole(['superadmin', 'admin'])) {
            $pcbcs = CashOpname::orderBy('project', 'asc')->orderBy('date', 'desc')->get();
        } else {
            $pcbcs = CashOpname::where('project', auth()->user()->project)->orderBy('date', 'desc')->get();
        }

        return datatables()->of($pcbcs)
            ->addColumn('date', function ($pcbc) {
                return date('d-M-Y', strtotime($pcbc->date));
            })
            ->addColumn('created_by', function ($pcbc) {
                return $pcbc->cashier->name;
            })
            ->addColumn('action', 'cashier.pcbc.action')
            ->addIndexColumn()
            ->rawColumns(['action'])
            ->toJson();
    }
}
