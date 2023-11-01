<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Realization;
use App\Models\RealizationDetail;
use App\Models\Verification;
use Illuminate\Http\Request;

class VerificationOldController extends Controller
{
    public function index()
    {
        $realizations = Realization::where('status', 'approved')
            ->where('project', auth()->user()->project)
            ->whereDoesntHave('verification')
            ->get();

        return view('verifications.index', compact('realizations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'realization_id' => 'required',
            'date' => 'required',
        ]);

        $verification = Verification::create([
            'realization_id' => $request->realization_id,
            'date' => $request->date,
            'flag' => 'VERTEMP' . auth()->user()->id,   //verification temporary
            'user_id' => auth()->user()->id,
            'project' => auth()->user()->project,
            'status' => 'DRAFT'
        ]);

        return redirect()->route('verifications.create', $verification->id);
    }

    public function create($id)
    {
        $verification = Verification::findOrFail($id);
        $realization = $verification->realization;
        $realization_details = $realization->realizationDetails;
        $accounts = Account::orderBy('account_number')->get();

        return view('verifications.create', compact([
            'verification',
            'realization',
            'realization_details',
            'accounts'
        ]));
    }

    public function edit($id)
    {
        $verification = Verification::findOrFail($id);
        $realization = $verification->realization;
        $realization_details = $realization->realizationDetails;
        $accounts = Account::orderBy('account_number')->get();

        return view('verifications.edit', compact([
            'verification',
            'realization',
            'realization_details',
            'accounts'
        ]));
    }

    public function account_list($realization_detail_id)
    {
        $realization_detail = RealizationDetail::findOrFail($realization_detail_id);
        $accounts = Account::orderBy('account_number')->get();

        return view('verifications.account_list', compact([
            'realization_detail',
            'accounts'
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
            // $realization_detail->account_id = $account->id;
            $realization_detail->verification_id = $request->verification_id;
            $realization_detail->verification_id = $request->verification_id;
            $realization_detail->editable = 0;
            $realization_detail->deleteable = 0;

            $realization_detail->save();
        }

        //UPDATE REALIZATION
        $realization = Realization::findOrFail($request->realization_id);
        $realization->status = 'verified';
        $realization->save();

        //UPDATE VERIFICATION
        $verification = Verification::findOrFail($request->verification_id);
        $verification->status = 'verified';
        $verification->flag = null;
        $verification->save();

        return redirect()->route('verifications.index')->with('success', 'Verifikasi berhasil disimpan');
    }

    public function data()
    {
        $userRoles = app(UserController::class)->getUserRoles();

        if (in_array('superadmin', $userRoles) || in_array('admin', $userRoles)) {
            $verifications = Verification::orderBy('created_at', 'desc')
                ->get();
        } else {
            $flag = 'VERTEMP' . auth()->user()->id;
            $verifications = Verification::where('project', auth()->user()->project)
                // ->where('flag', $flag)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return datatables()->of($verifications)
            ->addColumn('realization_no', function ($verification) {
                return $verification->realization->nomor;
            })
            ->addColumn('requestor', function ($verification) {
                return $verification->realization->requestor->name;
            })
            ->addColumn('payreq_no', function ($verification) {
                return $verification->realization->payreq->nomor;
            })
            ->addColumn('date', function ($verification) {
                return date('d-M-Y', strtotime($verification->realization->created_at));
            })
            ->editColumn('status', function ($verification) {
                if ($verification->status == 'DRAFT') {
                    return '<span class="badge badge-warning">' . $verification->status . '</span>';
                } elseif ($verification->status == 'VERIFIED') {
                    return '<span class="badge badge-danger">NOT POSTED YET</span>';
                } elseif ($verification->status == 'REJECTED') {
                    return '<span class="badge badge-success">' . $verification->status . '</span>';
                }
            })
            ->addColumn('action', 'verifications.action')
            ->rawColumns(['action', 'status'])
            ->addIndexColumn()
            ->toJson();
    }
}
