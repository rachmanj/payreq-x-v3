<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Activity;
use App\Models\Payreq;
use App\Models\Rekap;
use App\Models\Transaksi;
use Illuminate\Http\Request;

class VerifyController extends Controller
{
    public function index()
    {
        return view('verify.index');
    }

    public function update(Request $request, $id)
    {
        if ($request->verify_date) {
            $verify_date = $request->verify_date;
        } else {
            $verify_date = date('Y-m-d');
        }

        $payreq = Payreq::findOrFail($id);

        //cek apakah payreq_idr berbeda dengan realization_amount
        if ($payreq->payreq_idr > $payreq->realization_amount) {
            $variant = $payreq->payreq_idr - $payreq->realization_amount;
            if ($payreq->rab_id) {
                if ($payreq->rab->department->akronim == 'DNC') {
                    $account = Account::where('account_no', '111115')->first();
                } else {
                    $account = Account::where('account_no', '111111')->first();
                }
            } else {
                $account = Account::where('account_no', '111111')->first();
            }
            $account->balance = $account->balance + $variant;
            $account->save();

            $description = 'Pengembalian PR ' . $payreq->payreq_num;

            // create new transaksi record
            $transaksi = new Transaksi();
            $transaksi->payreq_id = $payreq->id;
            $transaksi->account_id = $account->id;
            $transaksi->posting_date = $verify_date;
            $transaksi->description = $description;
            $transaksi->type = 'plus';
            $transaksi->amount = $variant;
            $transaksi->save();
        } else if ($payreq->payreq_idr < $payreq->realization_amount) {
            $variant = $payreq->realization_amount - $payreq->payreq_idr;
            if ($payreq->rab_id) {
                if ($payreq->rab->department->akronim == 'DNC') {
                    $account = Account::where('account_no', '111115')->first();
                } else {
                    $account = Account::where('account_no', '111111')->first();
                }
            } else {
                $account = Account::where('account_no', '111111')->first();
            }
            $account->balance = $account->balance - $variant;
            $account->save();

            $description = 'Kekurangan PR ' . $payreq->payreq_num;

            // create transaksi record
            $transaksi = new Transaksi();
            $transaksi->payreq_id = $payreq->id;
            $transaksi->account_id = $account->id;
            $transaksi->posting_date = $verify_date;
            $transaksi->description = $description;
            $transaksi->type = 'minus';
            $transaksi->amount = $variant;
            $transaksi->save();
        }

        // create new rekaps record
        $rekap = new Rekap();
        $rekap->posting_date = $verify_date;
        $rekap->employee = $payreq->employee->username;
        $rekap->payreq_no = $payreq->payreq_num;
        $rekap->realization_no = $payreq->realization_num;
        $rekap->amount = $payreq->realization_amount;
        $rekap->remarks = $payreq->remarks;
        $rekap->save();

        //hapus transaksi advance nya
        $adv_rekap = Rekap::where('payreq_no', $payreq->payreq_num)->where('remarks', 'like', '%Adv%')->first();
        if ($adv_rekap) {
            $adv_rekap->delete();
        }

        // count days between outgoing_date and verify_date
        $days = date_diff(date_create($payreq->outgoing_date), date_create($verify_date))->format('%a');

        $payreq->verify_date = $verify_date;
        $payreq->otvd = $days;
        $payreq->save();

        // SAVE ACTIVITY
        $activityCtrl = app(ActivityController::class);
        $activityCtrl->store(auth()->user()->id, 'Verify PR', $payreq->payreq_num);

        return redirect()->route('verify.index')->with('success', 'Payment Request updated');
    }

    public function data()
    {
        $payreqs = Payreq::select(
            'id',
            'payreq_num',
            'user_id',
            'approve_date',
            'payreq_idr',
            'outgoing_date',
            'realization_num',
            'realization_amount',
            'realization_date',
        )
            ->selectRaw('datediff(now(), realization_date) as days')
            ->whereNotNull('realization_date')
            ->whereNull('verify_date')
            ->orderBy('realization_date', 'asc')
            ->get();

        return datatables()->of($payreqs)
            ->editColumn('approve_date', function ($payreq) {
                return date('d-m-Y', strtotime($payreq->approve_date));
            })
            ->editColumn('outgoing_date', function ($payreq) {
                return date('d-m-Y', strtotime($payreq->outgoing_date));
            })
            ->editColumn('realization_date', function ($payreq) {
                return date('d-m-Y', strtotime($payreq->realization_date));
            })
            ->editColumn('payreq_idr', function ($payreq) {
                return number_format($payreq->payreq_idr, 0);
            })
            ->editColumn('realization_amount', function ($payreq) {
                if ($payreq->realization_amount == null) return '-';
                return number_format($payreq->realization_amount, 0);
            })
            ->addColumn('employee', function ($payreq) {
                return $payreq->employee->name;
            })
            ->addIndexColumn()
            ->addColumn('action', 'verify.action')
            ->rawColumns(['action'])
            ->toJson();
    }
}
