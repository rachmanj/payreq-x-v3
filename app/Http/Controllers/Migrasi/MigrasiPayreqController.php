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
            'status' => 'paid',
            'editable' => '0',
            'deletable' => '0',
            'nomor' => app(DocumentNumberController::class)->generate_document_number('payreq', $project),
            'department_id' => $department_id,
            'type' => 'advance',
            'remarks' => $request->remarks . ', migrasi payreq ' . $request->old_payreq_no,
            'rab_id' => null,
            'approved_at' => $request->paid_date,
            'submit_at' => $request->paid_date,
            'draft_no' => $request->old_payreq_no,
            'user_id' => $request->requestor_id,
            'due_date' => '2024-05-09',
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

    public function edit($id)
    {
        $payreq = Payreq::findOrFail($id);
        $cashier = $payreq->outgoings->count() > 0 ? $payreq->outgoings->first()->cashier->name : 'n/a';

        return view('migrasi.payreqs.edit', [
            'payreq' => $payreq,
            'cashier' => $cashier,
        ]);
    }

    public function update(Request $request, $id) // just to update paid_date
    {
        $payreq = Payreq::findOrFail($id);
        $approved_at = strtotime($request->paid_date);
        $payreq->update([
            'approved_at' => date('Y-m-d H:i:s', $approved_at),
            'submit_at' => date('Y-m-d H:i:s', $approved_at),
            'due_date' => $request->paid_date,
        ]);

        return redirect()->route('cashier.migrasi.payreqs.index')->with('success', 'Payreq has been updated successfully');
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
        if (auth()->user()->hasRole('superadmin')) {
            $payreqIds = PayreqMigrasi::pluck('payreq_id');
            $payreqs = Payreq::whereIn('id', $payreqIds)->get();
        } else {
            $payreqIds = PayreqMigrasi::where('created_by', auth()->user()->id)->pluck('payreq_id');
            $payreqs = Payreq::whereIn('id', $payreqIds)->whereIn('status', ['paid'])->get();
        }

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
                // if payreq has outgoings, get the cashier name
                if ($payreq->outgoings->count() > 0) {
                    return $payreq->outgoings->first()->cashier->name;
                }
                return 'no outgoing found';
            })
            ->editColumn('amount', function ($payreq) {
                if ($payreq->status == 'split') {
                    $outgoings = Outgoing::where('payreq_id', $payreq->id)->get();
                    $amount = $payreq->amount - $outgoings->sum('amount');
                    return '<span class="badge badge-warning">split</span>' . ' ' . number_format($amount, 2);
                }
                return number_format($payreq->amount, 2);
            })
            // ->addColumn('approved_at', function ($payreq) {
            //     return $payreq->approved_at ? $payreq->approved_at->format('d-m-Y') : '-';
            // })
            ->addIndexColumn()
            ->addColumn('action', 'migrasi.payreqs.action')
            ->rawColumns(['action', 'amount', 'nomor'])
            ->toJson();
    }

    public function update_status()
    {
        $payreqIds = PayreqMigrasi::pluck('payreq_id');
        $payreqs = Payreq::whereIn('id', $payreqIds)->get();

        foreach ($payreqs as $payreq) {
            $payreq->update([
                'status' => 'paid',
            ]);
        }

        return redirect()->route('cashier.migrasi.payreqs.index')->with('success', 'Payreqs status has been updated successfully');
    }

    public function update_koreksi()
    {
        $payreqIds = PayreqMigrasi::pluck('payreq_id');
        $payreqs = Payreq::whereIn('id', $payreqIds)->whereNull('approved_at')->get();

        foreach ($payreqs as $payreq) {
            // get created_at and convert to date
            $created_date = $payreq->created_at->format('Y-m-d');
            // return $created_date;
            $payreq->update([
                'approved_at' => $payreq->$created_date,
                'submit_at' => $payreq->$created_date,
                'due_date' => '2024-05-09',
            ]);
        }
    }

    public function update_no(Request $request, $id)
    {
        $payreq = Payreq::findOrFail($id);
        $payreq->update([
            'nomor' => $request->payreq_no,
        ]);

        return redirect()->route('cashier.migrasi.payreqs.index')->with('success', 'Payreq notes has been updated successfully');
    }
}
