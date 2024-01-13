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

        return view('cashier.pcbc.print', ([
            'pcbc' => $pcbc,
            'uang_kertas_total' => $this->uang_kertas_total($id),
            'uang_logam_total' => $this->uang_logam_total($id),
        ]));
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

    public function uang_kertas_total($pcbc_id)
    {
        $pcbc = CashOpname::findOrFail($pcbc_id);
        $uang_kertas_total = $pcbc->seratus_ribu * 100000 + $pcbc->lima_puluh_ribu * 50000 + $pcbc->dua_puluh_ribu * 20000 + $pcbc->sepuluh_ribu * 10000 + $pcbc->lima_ribu * 5000 + $pcbc->dua_ribu * 2000 + $pcbc->seribu * 1000;

        return $uang_kertas_total;
    }

    public function uang_logam_total($pcbc_id)
    {
        $pcbc = CashOpname::findOrFail($pcbc_id);
        $uang_logam_total = $pcbc->coin_seribu * 1000 + $pcbc->coin_lima_ratus * 500 + $pcbc->coin_dua_ratus * 200 + $pcbc->coin_seratus * 100 + $pcbc->coin_lima_puluh * 50 + $pcbc->coin_dua_puluh_lima * 25;

        return $uang_logam_total;
    }
}
