<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Giro;
use App\Models\GiroDetail;
use Illuminate\Http\Request;

class CashierGiroDetailController extends Controller
{

    public function index($giro_id)
    {
        $giro = Giro::find($giro_id);
        $giro_details = GiroDetail::where('giro_id', $giro_id)->get();
        $amount = $giro_details->sum('amount');
        $accounts = Account::where('type', 'bank')->where('project', auth()->user()->project)->get();

        return view('cashier.giros.details.index', compact('giro', 'giro_details', 'amount', 'accounts'));
    }

    public function store(Request $request, $giro_id)
    {
        $giro = Giro::find($giro_id);

        $request->validate([
            'remarks' => 'required',
            'amount' => 'required',
        ]);

        // SAVE TO GIRO DETAIL TABLE
        $giro_detail = new GiroDetail();
        $giro_detail->giro_id = $giro->id;
        $giro_detail->remarks = $request->remarks;
        // $giro_detail->account_id = $request->account_id;
        $giro_detail->amount = $request->amount;
        $giro_detail->save();

        return redirect()->route('cashier.giros.detail.index', $giro_id);
    }

    public function destroy($id)
    {
        $giro_detail = GiroDetail::find($id);
        $giro_id = $giro_detail->giro_id;
        $giro_detail->delete();

        return redirect()->route('cashier.giros.detail.index', $giro_id)->with('success', 'Data berhasil dihapus');
    }

    public function data($giro_id)
    {
        $giro_details = GiroDetail::where('giro_id', $giro_id)->get();

        return datatables()->of($giro_details)
            ->editColumn('amount', function ($giro_details) {
                return number_format($giro_details->amount, 0, ',', '.');
            })
            ->editColumn('is_cashin', function ($giro_details) {
                return $giro_details->is_cashin ? '<span class="right badge badge-primary">Yes</span>' : '<span class="right badge badge-info">No</span>';
            })
            ->addIndexColumn()
            ->addColumn('action', 'cashier.giros.details.action')
            ->rawColumns(['action', 'is_cashin'])
            ->make(true);
    }
}
