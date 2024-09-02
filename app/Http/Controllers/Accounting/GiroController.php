<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\Giro;
use App\Models\Project;
use Illuminate\Http\Request;

class GiroController extends Controller
{
    public function index()
    {
        $banks = Bank::orderBy('name')->get();
        $projects = Project::orderBy('code')->get();

        return view('accounting.giros.index', compact('banks', 'projects'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'acc_no' => 'required',
            'bank_id' => 'required',
            'project' => 'required',
        ]);

        Giro::create($request->all());

        return redirect()->route('accounting.giros.index');
    }

    public function data()
    {
        $giros = Giro::all();

        return datatables()->of($giros)
            ->addColumn('bank', function ($giro) {
                return $giro->bank->name;
            })
            ->editColumn('curr', function ($giro) {
                return $giro->curr == 'idr' ? 'IDR' : 'USD';
            })
            ->addIndexColumn()
            ->addColumn('action', 'accounting.giros.action')
            ->rawColumns(['action'])
            ->toJson();
    }
}
