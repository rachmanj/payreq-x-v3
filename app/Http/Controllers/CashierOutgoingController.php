<?php

namespace App\Http\Controllers;

use App\Models\Outgoing;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CashierOutgoingController extends Controller
{
    public function index()
    {
        return view('cashier.outgoings.index');
    }

    public function create()
    {
        return view('cashier.outgoings.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required',
            'amount' => 'required',
        ]);

        $outgoing = new Outgoing;
        $outgoing->cashier_id = auth()->user()->id;
        $outgoing->description = $request->description;
        $outgoing->amount = $request->amount;
        $outgoing->project = auth()->user()->project;
        if ($request->has('will_post')) {
            $outgoing->will_post = 0;
        }
        $outgoing->save();

        return redirect()->route('cashier.outgoings.index')->with('success', 'Outgoing has been created');
    }

    public function payment(Request $request)
    {
        // update incomings table
        $outgoing = Outgoing::findOrFail($request->incoming_id);

        $roles = app(ToolController::class)->getUserRoles();
        abort_if(
            ! array_intersect(['superadmin', 'admin'], $roles) && $outgoing->project !== auth()->user()->project,
            403,
            'Anda tidak memiliki akses ke outgoing project lain.'
        );

        $outgoing->outgoing_date = $request->receive_date;
        $outgoing->save();

        // update app_balance in accounts table
        app(AccountController::class)->outgoing_manual($outgoing->amount);

        return redirect()->route('cashier.outgoings.index')->with('success', 'Payment has been created');
    }

    public function data()
    {
        $roles = app(ToolController::class)->getUserRoles();

        if (array_intersect(['superadmin', 'admin'], $roles)) {
            $outgoings = Outgoing::with('attachments')
                ->orderBy('outgoing_date', 'desc')
                ->get();
        } else {
            // user hanya melihat outgoing project sendiri, histori 12 bulan terakhir
            // (outgoing manual yg belum dibayar — outgoing_date null — tetap tampil)
            $limit_date = Carbon::now()->subMonths(12)->format('Y-m-d');
            $outgoings = Outgoing::with('attachments')
                ->where('project', auth()->user()->project)
                ->where(function ($q) use ($limit_date) {
                    $q->whereNull('outgoing_date')
                        ->orWhere('outgoing_date', '>=', $limit_date);
                })
                ->orderBy('outgoing_date', 'desc')
                ->get();
        }

        return datatables()->of($outgoings)
            ->addColumn('employee', function ($outgoing) {
                if ($outgoing->payreq_id !== null) {
                    return $outgoing->payreq->requestor->name;
                } else {
                    return $outgoing->cashier->name;
                }
            })
            ->addColumn('payreq_no', function ($outgoing) {
                return $outgoing->payreq->nomor;
            })
            ->editColumn('outgoing_date', function ($outgoing) {
                $outgoing_date = new \Carbon\Carbon($outgoing->outgoing_date);

                return $outgoing_date->format('d-M-Y');
            })
            ->editColumn('amount', function ($outgoing) {
                return number_format($outgoing->amount, 2);
            })
            ->addColumn('cashier', function ($outgoing) {
                return $outgoing->cashier->name;
            })
            ->addColumn('remarks', function ($outgoing) {
                return $outgoing->payreq?->remarks;
            })
            ->addColumn('transfer_proof', function ($outgoing) {
                if ($outgoing->payment_method !== 'transfer') {
                    return '<span class="text-muted">-</span>';
                }

                $attachments = $outgoing->attachments;
                if ($attachments->isEmpty()) {
                    return '<span class="text-muted">Belum ada</span>';
                }

                $verified = $attachments->where('verification_status', 'verified')->count();
                $mismatch = $attachments->where('verification_status', 'mismatch')->count();
                $pending = $attachments->where('verification_status', 'pending')->count();
                $failed = $attachments->where('verification_status', 'failed')->count();

                $summary = [];
                if ($verified > 0) {
                    $summary[] = $verified.' ✓';
                }
                if ($mismatch > 0) {
                    $summary[] = $mismatch.' ✗';
                }
                if ($pending > 0) {
                    $summary[] = $pending.' ⏳';
                }
                if ($failed > 0) {
                    $summary[] = $failed.' ⚠️';
                }

                $first = $attachments->first();
                $downloadLink = $first
                    ? '<a href="'.route('cashier.outgoing-attachments.download', $first).'" class="btn btn-xs btn-outline-primary ml-1" title="Unduh">'
                        .'<i class="fas fa-download"></i></a>'
                    : '';

                return '<span class="vj-chip vj-chip-info">'.$attachments->count().'</span> '
                    .e(implode(', ', $summary))
                    .$downloadLink;
            })
            ->addIndexColumn()
            ->addColumn('action', 'cashier.outgoings.action')
            ->rawColumns(['action', 'amount', 'transfer_proof'])
            ->toJson();
    }
}
