<?php

namespace App\Http\Controllers\Migrasi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DocumentNumberController;
use App\Models\Account;
use App\Models\Outgoing;
use App\Models\Payreq;
use App\Models\PayreqMigrasi;
use App\Models\User;
use Illuminate\Http\Request;

class MigrasiPayreqController extends Controller
{
    public function index()
    {
        return view('migrasi.payreqs.index');
    }

    public function create()
    {
        $requestors = User::where('is_active', 1)->orderBy('name')->get();
        $cashiers = User::where('is_active', 1)->whereHas('roles', function ($query) {
            $query->where('name', 'LIKE', '%cashier%');
        })->orderBy('name')->get();

        return view('migrasi.payreqs.create', [
            'requestors' => $requestors,
            'cashiers' => $cashiers,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'remarks' => 'required',
            'amount' => 'required',
        ]);

        $project = User::findOrFail($request->requestor_id)->project;
        $department_id = User::findOrFail($request->requestor_id)->department_id;

        $payreq = Payreq::create(array_merge($validated, [
            'project' => $project,
            'status' => 'approved',
            'editable' => '0',
            'deletable' => '0',
            'nomor' => app(DocumentNumberController::class)->generate_document_number('payreq', $project),
            'department_id' => $department_id,
            'type' => 'advance',
            'remarks' => $request->remarks . ', migrasi from payreq ' . $request->old_payreq_no,
            'rab_id' => $request->rab_id,
            'user_id' => $request->requestor_id,
        ]));

        // store to payreq_migrasis
        PayreqMigrasi::create([
            'payreq_id' => $payreq->id,
            'created_by' => auth()->user()->id,
            'old_payreq_no' => $request->old_payreq_no,
        ]);

        $cashier_project = User::findOrFail($request->cashier_id)->project;
        $account_id = Account::where('type', 'cash')->where('project',  $cashier_project)->first()->id;

        $outgoing = new Outgoing();
        $outgoing->payreq_id = $payreq->id;
        $outgoing->amount = $payreq->amount;
        $outgoing->cashier_id = $request->cashier_id;
        $outgoing->project = $payreq->project;
        $outgoing->outgoing_date = $request->paid_date;
        $outgoing->account_id = $account_id;
        $outgoing->save();

        return redirect()->route('cashier.migrasi.payreqs.index')->with('success', 'Payreq has been created successfully');
    }

    public function destroy(Request $request)
    {
        $payreq = Payreq::findOrFail($request->payreq_id);
        $outgoings = Outgoing::where('payreq_id', $payreq->id)->get();
        $payreq_migrasi = PayreqMigrasi::where('payreq_id', $payreq->id)->first();
        $payreq_migrasi->delete();
        $outgoings->each->delete();
        $payreq->delete();

        return redirect()->route('cashier.migrasi.payreqs.index')->with('success', 'Payreq has been deleted successfully');
    }

    public function data()
    {
        $payreqIds = PayreqMigrasi::where('created_by', auth()->user()->id)->pluck('payreq_id');
        $payreqs = Payreq::whereIn('id', $payreqIds)->get();

        return datatables()->of($payreqs)
            ->addColumn('requestor', function ($payreq) {
                return $payreq->requestor->name;
            })
            ->editColumn('nomor', function ($payreq) {
                return '<a href="#" style="color: black" title="' . $payreq->remarks . '">' . $payreq->nomor . '</a>';
            })
            ->addColumn('days', function ($payreq) {
                $payreq_date = new \Carbon\Carbon($payreq->payreq_at);
                return $payreq_date->addHours(8)->diffInDays(now());
            })
            ->addColumn('cashier', function ($payreq) {
                return $payreq->outgoings->first()->cashier->name;
            })
            ->editColumn('amount', function ($payreq) {
                if ($payreq->status == 'split') {
                    $outgoings = Outgoing::where('payreq_id', $payreq->id)->get();
                    $amount = $payreq->amount - $outgoings->sum('amount');
                    return '<span class="badge badge-warning">split</span>' . ' ' . number_format($amount, 2);
                }
                return number_format($payreq->amount, 2);
            })
            ->addIndexColumn()
            ->addColumn('action', 'migrasi.payreqs.action')
            ->rawColumns(['action', 'amount', 'nomor'])
            ->toJson();
    }
}
