<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Giro;
use App\Models\GiroDetail;
use App\Models\Transaksi;
use Illuminate\Http\Request;

class GiroDetailController extends Controller
{
    public function index($giro_id)
    {
        $giro = Giro::find($giro_id);
        $giro_details = GiroDetail::where('giro_id', $giro_id)->get();
        $amount = $giro_details->sum('amount');
        $accounts = Account::orderBy('account_no', 'asc')->get();

        return view('giros.details.index', compact('giro', 'giro_details', 'amount', 'accounts'));
    }

    public function store(Request $request, $giro_id)
    {
        $giro = Giro::find($giro_id);

        $request->validate([
            'remarks' => 'required',
            'amount' => 'required',
        ]);

        if ($request->account_id) {
            // UPDATE ACCOUNT BALANCE
            $account = Account::find($request->account_id);
            $account->balance += $request->amount;
            $account->save();

            //SAVE TO TRANSAKSI TABLE
            $transaksi = new Transaksi();
            $transaksi->posting_date = $giro->tanggal;
            $transaksi->account_id = $request->account_id;
            $transaksi->amount = $request->amount;
            $transaksi->type = 'plus';
            $transaksi->description = 'Penerimaan Giro ' . $giro->nomor;
            $transaksi->save();

            $is_cashin = 1;
        } else {
            $is_cashin = 0;
        }

        // SAVE TO GIRO DETAIL TABLE
        $giro_detail = new GiroDetail();
        $giro_detail->giro_id = $giro_id;
        $giro_detail->remarks = $request->remarks;
        $giro_detail->account_id = $request->account_id;
        $giro_detail->amount = $request->amount;
        $giro_detail->is_cashin = $is_cashin;
        $giro_detail->save();

        // SAVE ACTIVITY
        $activityCtrl = app(ActivityController::class);
        $activityCtrl->store(auth()->user()->id, 'Detail Giro', $giro->nomor);

        return redirect()->route('giros.detail.index', $giro_id);
    }

    public function destroy($id)
    {
        $giro_detail = GiroDetail::find($id);
        $giro_id = $giro_detail->giro_id;
        $giro_detail->delete();

        return redirect()->route('giros.detail.index', $giro_id)->with('success', 'Data berhasil dihapus');
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
            ->addColumn('action', 'giros.details.action')
            ->rawColumns(['action', 'is_cashin'])
            ->make(true);
    }
}
