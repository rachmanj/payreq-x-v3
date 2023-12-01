<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Realization;
use App\Models\RealizationDetail;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function index()
    {
        $realizations = Realization::where('status', 'approved')
            ->where('project', auth()->user()->project)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('verifications.index', compact('realizations'));
    }

    public function edit($id)
    {
        $realization = Realization::findOrFail($id);
        $realization_details = $realization->realizationDetails;

        return view('verifications.edit', compact([
            'realization',
            'realization_details',
        ]));
    }

    public function save(Request $request)
    {
        //UPDATE REALIZATION DETAIL
        foreach ($request->realization_details as $item) {
            $realization_detail = RealizationDetail::findOrFail($item['id']);

            if ($item['account_number'] !== null) {
                $account = Account::where('account_number', $item['account_number'])->first();
                $realization_detail->account_id = $account->id;
            }
            $realization_detail->editable = 0;
            $realization_detail->deleteable = 0;

            $realization_detail->save();
        }

        //UPDATE REALIZATION
        $realization = Realization::findOrFail($request->realization_id);
        $realization->status = 'close'; // setelah cashier melengkapi nomor account di each realization detail, maka statusnya menjadi close
        $realization->deletable = 0;
        $realization->save();

        // UPDATE PAYREQ
        $payreq = $realization->payreq;
        $payreq->status = 'close';
        $payreq->save();

        return redirect()->route('verifications.index')->with('success', 'Verifikasi berhasil disimpan');
    }

    public function data()
    {
        $userRoles = app(UserController::class)->getUserRoles();
        $status_include = ['approved', 'reimburse-paid', 'verification', 'close'];

        if (in_array('superadmin', $userRoles) || in_array('admin', $userRoles)) {
            $realizations = Realization::whereIn('status', $status_include)
                ->whereNull('verification_journal_id')
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $realizations = Realization::whereIn('status', $status_include)
                ->where('project', auth()->user()->project)
                ->whereNull('journal_id')
                // ->where('flag', $flag)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // return $realizations;
        // die;

        return datatables()->of($realizations)
            ->addColumn('realization_no', function ($realization) {
                return $realization->nomor;
            })
            ->addColumn('requestor', function ($realization) {
                return $realization->requestor->name;
            })
            ->addColumn('payreq_no', function ($realization) {
                // return "ninja";
                return $realization->payreq->nomor;
            })
            ->addColumn('date', function ($realization) {
                return date('d-M-Y', strtotime($realization->created_at));
            })
            ->editColumn('is_complete', function ($realization) {
                if ($this->realizationDetailIsComplete($realization)) {
                    return '<span class="badge badge-success">COMPLETE</span>';
                } else {
                    return '<span class="badge badge-danger">INCOMPLETE</span>';
                }
            })
            ->addColumn('action', 'verifications.action')
            ->rawColumns(['action', 'is_complete'])
            ->addIndexColumn()
            ->toJson();
    }

    /*
    *   this to Check if all realization details have account
    */
    public function realizationDetailIsComplete($realization)
    {
        $realization_details = $realization->realizationDetails;

        foreach ($realization_details as $realization_detail) {
            if ($realization_detail->account_id == null) {
                return false;
            }
        }

        return true;
    }
}
