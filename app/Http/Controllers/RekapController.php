<?php

namespace App\Http\Controllers;

use App\Exports\RekapExport;
use App\Models\Rekap;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class RekapController extends Controller
{
    public function index()
    {
        return view('rekaps.index');
    }

    public function destroy($id)
    {
        $rekap = Rekap::find($id);
        $rekap->delete();
        return redirect()->route('rekaps.index')->with('success', 'Record berhasil dihapus');
    }

    public function export()
    {
        return Excel::download(new RekapExport, 'rekap_tx.xlsx');
    }

    public function data()
    {
        $rekaps = Rekap::orderBy('posting_date', 'desc')->get();

        return datatables()->of($rekaps)
            ->editColumn('posting_date', function ($rekaps) {
                return date('d-m-Y', strtotime($rekaps->posting_date));
            })
            ->editColumn('amount', function ($rekaps) {
                return number_format($rekaps->amount, 0);
            })
            ->addIndexColumn()
            ->addColumn('action', 'rekaps.action')
            ->rawColumns(['action'])
            ->toJson();
    }
}
