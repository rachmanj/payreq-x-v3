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
        $this->validateRequest($request);

        if (Giro::where('acc_no', $request->acc_no)->exists()) {
            return redirect()->route('accounting.giros.index')->with('error', 'Giro already exists');
        }

        Giro::create($request->all());

        return redirect()->route('accounting.giros.index')->with('success', 'Giro created successfully');
    }

    public function update(Request $request, $id)
    {
        $this->validateRequest($request);

        if (Giro::where('acc_no', $request->acc_no)->where('id', '!=', $id)->exists()) {
            return redirect()->route('accounting.giros.index')->with('error', 'Giro already exists');
        }

        $giro = Giro::findOrFail($id);
        $giro->update($request->all());

        return redirect()->route('accounting.giros.index')->with('success', 'Giro updated successfully');
    }

    public function destroy($id)
    {
        $giro = Giro::with('bilyets')->findOrFail($id);

        if ($giro->bilyets->count() > 0) {
            return redirect()->route('accounting.giros.index')->with('error', 'Giro already has bilyets');
        }

        $giro->delete();

        return redirect()->route('accounting.giros.index')->with('success', 'Giro deleted successfully');
    }

    public function data()
    {
        $giros = Giro::with('bank')->get();

        return datatables()->of($giros)
            ->addColumn('bank', function ($giro) {
                return $giro->bank->name;
            })
            ->editColumn('curr', function ($giro) {
                return $giro->curr == 'IDR' ? 'IDR' : 'USD';
            })
            ->editColumn('acc_no', function ($giro) {
                return $giro->acc_no . ($giro->sap_account ? '<br><small>SAP: ' . $giro->sap_account . '</small>' : '');
            })
            ->addIndexColumn()
            ->addColumn('action', 'accounting.giros.action')
            ->rawColumns(['action', 'acc_no'])
            ->toJson();
    }

    private function validateRequest(Request $request)
    {
        $request->validate([
            'acc_no' => 'required',
            'bank_id' => 'required',
            'project' => 'required',
        ]);
    }
}
