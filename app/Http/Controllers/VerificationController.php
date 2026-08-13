<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Department;
use App\Models\Project;
use App\Models\Realization;
use App\Models\RealizationDetail;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function index()
    {
        return view('verifications.index');
    }

    public function edit($id)
    {
        $realization = Realization::findOrFail($id);
        $realization_details = $realization->realizationDetails;
        $projects = Project::orderBy('code', 'asc')->get();
        $departments = Department::orderBy('akronim', 'asc')->get();

        return view('verifications.edit', compact([
            'realization',
            'realization_details',
            'projects',
            'departments',
        ]));
    }

    public function save(Request $request)
    {
        // UPDATE REALIZATION DETAIL
        foreach ($request->realization_details as $item) {
            $realization_detail = RealizationDetail::findOrFail($item['id']);

            if ($item['account_number'] !== null) {
                $account = Account::where('account_number', $item['account_number'])->first();
                $realization_detail->account_id = $account->id;
            }
            $realization_detail->editable = 0;
            $realization_detail->deleteable = 0;
            $realization_detail->project = $item['project'];
            $realization_detail->department_id = $item['department_id'];

            $realization_detail->save();
        }

        // UPDATE REALIZATION
        $realization = Realization::where('id', $request->realization_id)->first();
        $realization->deletable = 0;

        if ($this->realizationDetailIsComplete($realization)) {
            $realization->status = 'verification-complete';
        }
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
        $statusInclude = ['approved', 'reimburse-paid', 'verification', 'close', 'verification-complete'];

        $query = Realization::query()
            ->select('realizations.*')
            ->with(['requestor:id,name', 'payreq:id,nomor'])
            ->withExists([
                'realizationDetails as has_incomplete_account' => function ($detailQuery) {
                    $detailQuery->whereNull('account_id');
                },
            ])
            ->whereIn('status', $statusInclude)
            ->whereNull('verification_journal_id');

        if (array_intersect(['superadmin', 'admin'], $userRoles)) {
            // admins see all pending verifications
        } elseif (in_array('cashier', $userRoles)) {
            $query->whereIn('project', ['000H', 'APS']);
        } else {
            $query->where('project', auth()->user()->project);
        }

        $query->orderBy('created_at', 'desc');

        return datatables()->of($query)
            ->addColumn('realization_no', function ($realization) {
                return $realization->nomor;
            })
            ->addColumn('requestor', function ($realization) {
                return $realization->requestor->name;
            })
            ->addColumn('payreq_no', function ($realization) {
                return $realization->payreq->nomor;
            })
            ->addColumn('date', function ($realization) {
                return $realization->created_at->format('d-M-Y');
            })
            ->editColumn('is_complete', function ($realization) {
                if (! $realization->has_incomplete_account) {
                    return '<span class="vj-chip vj-chip-success">COMPLETE</span>';
                }

                return '<span class="vj-chip vj-chip-danger">INCOMPLETE</span>';
            })
            ->addColumn('action', 'verifications.action')
            ->filterColumn('realization_no', function ($query, $keyword) {
                $query->where('realizations.nomor', 'like', "%{$keyword}%");
            })
            ->filterColumn('payreq_no', function ($query, $keyword) {
                $query->whereHas('payreq', function ($payreqQuery) use ($keyword) {
                    $payreqQuery->where('nomor', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('requestor', function ($query, $keyword) {
                $query->whereHas('requestor', function ($requestorQuery) use ($keyword) {
                    $requestorQuery->where('name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('date', function ($query, $keyword) {
                $query->where('realizations.created_at', 'like', "%{$keyword}%");
            })
            ->orderColumn('realization_no', 'realizations.nomor $1')
            ->orderColumn('date', 'realizations.created_at $1')
            ->rawColumns(['action', 'is_complete'])
            ->addIndexColumn()
            ->toJson();
    }

    public function realizationDetailIsComplete($realization): bool
    {
        return ! $realization->realizationDetails()->whereNull('account_id')->exists();
    }
}
