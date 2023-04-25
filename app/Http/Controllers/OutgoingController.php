<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Activity;
use App\Models\Payreq;
use App\Models\Rekap;
use App\Models\Split;
use App\Models\Transaksi;
use Illuminate\Http\Request;

class OutgoingController extends Controller
{
    public function index()
    {
        return view('outgoing.index');
    }

    public function update(Request $request, $id)
    {
        if ($request->outgoing_date) {
            $outgoing_date = $request->outgoing_date;
        } else {
            $outgoing_date = date('Y-m-d');
        }

        $payreq = Payreq::find($id);

        if ($request->account_id) {
            $account = Account::find($request->account_id);
            $account->balance = $account->balance - $payreq->payreq_idr;
        } else {
            if ($payreq->rab_id != null) {
                if ($payreq->rab->department->akronim == 'DNC') {
                    $account = Account::where('account_no', '111115')->first();
                    $account->balance = $account->balance - $payreq->payreq_idr;
                } else {
                    $account = Account::where('account_no', '111111')->first();
                    $account->balance = $account->balance - $payreq->payreq_idr;
                }
            } else {
                $account = Account::where('account_no', '111111')->first();
                $account->balance = $account->balance - $payreq->payreq_idr;
            }
        }

        $description = 'PR ' . $payreq->payreq_num;

        // Create new Transaksi record
        $transaksi = new Transaksi();
        $transaksi->payreq_id = $payreq->id;
        $transaksi->account_id = $account->id;
        $transaksi->posting_date = $outgoing_date;
        $transaksi->description = $description;
        $transaksi->type = 'minus';
        $transaksi->amount = $payreq->payreq_idr;

        // update payreq outgoing date
        $payreq->outgoing_date = $outgoing_date;

        // create new rekaps record
        $rekap = new Rekap();
        $rekap->posting_date = $outgoing_date;
        $rekap->employee = $payreq->employee->username;
        $rekap->payreq_no = $payreq->payreq_num;
        $rekap->amount = $payreq->payreq_idr;
        $rekap->remarks = $payreq->payreq_type == 'Advance' ? 'Adv ' . $payreq->remarks : $payreq->remarks;

        // save all
        $account->save();
        $transaksi->save();
        $payreq->save();
        $rekap->save();

        // SAVE ACTIVITY
        $activityCtrl = app(ActivityController::class);
        $activityCtrl->store(auth()->user()->id, 'Outgoing PR', $payreq->payreq_num);

        return redirect()->route('outgoing.index')->with('success', 'Payment Request updated');
    }

    public function split($id)
    {
        $payreq = Payreq::with('splits')->find($id);
        $accounts = Account::orderBy('account_no', 'asc')->get();

        return view('outgoing.split', compact('payreq', 'accounts'));
    }

    public function split_update(Request $request, $id)
    {
        $payreq = Payreq::find($id);

        $this->validate($request, [
            'split_amount' => 'required',
        ]);

        $total_splits = Split::where('payreq_id', $payreq->id)->sum('amount');
        $outstanding = $payreq->payreq_idr - $total_splits;

        if ($request->split_amount > $outstanding) {
            return redirect()->route('outgoing.split', $payreq->id)->with('error', 'Split amount cannot be greater than outstanding amount');
        }

        // create Split
        $split = new Split();
        $split->payreq_id = $payreq->id;
        if ($request->date) {
            $split_date = $request->date;
        } else {
            $split_date = date('Y-m-d');
        }
        // jika split outstanding = request->split_amount, maka Payreq juga diupdate, yaitu tanggal outgoing diisi
        if ($outstanding == $request->split_amount) {
            $payreq->outgoing_date = $split_date;
            $payreq->save();

            // create new rekap record
            $rekap = new Rekap();
            $rekap->posting_date = $split_date;
            $rekap->employee = $payreq->employee->username;
            $rekap->payreq_no = $payreq->payreq_num;
            $rekap->amount = $payreq->payreq_idr;
            $rekap->remarks = $payreq->payreq_type == 'Advance' ? 'Adv ' . $payreq->remarks : $payreq->remarks;
            $rekap->save();
        }

        $split->date = $split_date;
        $split->amount = $request->split_amount;
        $split->save();

        // Create transaksi
        if ($payreq->rab_id != null) {
            if ($payreq->rab->department->akronim == 'DNC') {
                $account = Account::where('account_no', '111115')->first();
            } else {
                $account = Account::where('account_no', '111111')->first();
            }
        } else {
            $account = Account::where('account_no', '111111')->first();
        }

        $description = 'PR ' . $payreq->payreq_num;

        // update account balance on accounts table
        $account->balance = $account->balance - $request->split_amount;
        $account->save();

        // create new transaksi
        $transaksi = new Transaksi();
        $transaksi->payreq_id = $payreq->id;
        $transaksi->account_id = $account->id;
        $transaksi->posting_date = $split_date;
        $transaksi->description = $description;
        $transaksi->type = 'minus';
        $transaksi->amount = $request->split_amount;
        $transaksi->save();

        // SAVE ACTIVITY
        $activityCtrl = app(ActivityController::class);
        $activityCtrl->store(auth()->user()->id, 'Outgoing PR', $payreq->payreq_num);

        return redirect()->route('outgoing.split', $payreq->id)->with('success', 'Split added');
    }

    public function auto_update($id)
    {
        $outgoing_date = date('Y-m-d');
        $payreq = Payreq::findOrFail($id);
        $payreq->outgoing_date = $outgoing_date;

        if ($payreq->rab_id != null) {
            if ($payreq->rab->department->akronim == 'DNC') {
                $account = Account::where('account_no', '111115')->first();
                $account->balance = $account->balance - $payreq->payreq_idr;
            } else {
                $account = Account::where('account_no', '111111')->first();
                $account->balance = $account->balance - $payreq->payreq_idr;
            }
        } else {
            $account = Account::where('account_no', '111111')->first();
            $account->balance = $account->balance - $payreq->payreq_idr;
        }

        $description = 'PR ' . $payreq->payreq_num;

        // Create new Transaksi record
        $transaksi = new Transaksi();
        $transaksi->payreq_id = $payreq->id;
        $transaksi->account_id = $account->id;
        $transaksi->posting_date = $outgoing_date;
        $transaksi->description = $description;
        $transaksi->type = 'minus';
        $transaksi->amount = $payreq->payreq_idr;

        // create new rekaps record
        $rekap = new Rekap();
        $rekap->posting_date = $outgoing_date;
        $rekap->employee = $payreq->employee->username;
        $rekap->payreq_no = $payreq->payreq_num;
        $rekap->amount = $payreq->payreq_idr;
        $rekap->remarks = $payreq->payreq_type == 'Advance' ? 'Adv ' . $payreq->remarks : $payreq->remarks;

        // save all
        $payreq->save();
        $transaksi->save();
        $account->save();
        $rekap->save();

        // SAVE ACTIVITY
        $activityCtrl = app(ActivityController::class);
        $activityCtrl->store(auth()->user()->id, 'Outgoing PR', $payreq->payreq_num);

        return redirect()->route('outgoing.index')->with('success', 'Payment Request updated');
    }

    public function data()
    {
        $payreqs = Payreq::select('id', 'payreq_num', 'user_id', 'approve_date', 'payreq_type', 'payreq_idr', 'outgoing_date', 'rab_id')
            ->selectRaw('datediff(now(), approve_date) as days')
            ->whereNull('outgoing_date')
            ->orderBy('approve_date', 'asc')
            ->get();

        return datatables()->of($payreqs)
            ->editColumn('payreq_num', function ($payreq) {
                if ($payreq->buc_id) {
                    return $payreq->payreq_num . ' ' . '<i class="fas fa-check"></i>';
                }
                return $payreq->payreq_num;
            })
            ->editColumn('approve_date', function ($payreq) {
                return date('d-m-Y', strtotime($payreq->approve_date));
            })
            ->editColumn('payreq_idr', function ($payreq) {
                if ($payreq->splits->count() > 0) {
                    $total_splits = Split::where('payreq_id', $payreq->id)->sum('amount');
                    $outstanding = $payreq->payreq_idr - $total_splits;
                    return number_format($outstanding, 0) . ' of ' . number_format($payreq->payreq_idr, 0);
                }
                return number_format($payreq->payreq_idr, 0);
            })
            ->addColumn('employee', function ($payreq) {
                return $payreq->employee->name;
            })
            ->addIndexColumn()
            ->addColumn('action', 'outgoing.action')
            ->rawColumns(['action', 'payreq_num'])
            ->toJson();
    }
}
