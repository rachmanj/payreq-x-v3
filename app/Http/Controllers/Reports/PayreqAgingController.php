<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ToolController;
use App\Models\Payreq;
use Illuminate\Http\Request;

class PayreqAgingController extends Controller
{
    public function index()
    {
        return view('reports.ongoing.payreq-aging.index');
    }

    public function data()
    {
        $status_include = ['paid', 'realization'];

        if (auth()->user()->hasRole(['superadmin', 'admin', 'cashier'])) {
            $project_include = ['000H', 'APS'];
        } else {
            $project_include = [auth()->user()->project];
        }

        $payreqs = Payreq::join('outgoings', 'payreqs.id', '=', 'outgoings.payreq_id')
            ->select('payreqs.*', 'outgoings.outgoing_date as outgoing_date')
            ->whereIn('payreqs.status', $status_include)
            ->whereIn('payreqs.project', $project_include)
            ->orderBy('outgoings.outgoing_date', 'asc') // Add this line
            ->get();

        return datatables()->of($payreqs)
            ->addColumn('employee', function ($payreq) {
                return $payreq->requestor->name;
            })
            ->addColumn('outgoing_date', function ($payreq) {
                return (new \DateTime($payreq->outgoing_date))->format('d-M-Y');
            })
            ->editColumn('nomor', function ($payreq) {
                if ($payreq->rab_id != null)
                    return '<a href="#" style="color: black" title="' . $payreq->remarks . ' | RAB No.' . $payreq->rab->rab_no . '">' . $payreq->nomor . '</a>';
                else
                    return '<a href="#" style="color: black" title="' . $payreq->remarks . '">' . $payreq->nomor . '</a>';
            })
            ->editColumn('amount', function ($payreq) {
                return number_format($payreq->amount, 0, ',', '.');
            })
            ->editColumn('status', function ($payreq) {
                if ($payreq->status == 'paid') {
                    return '<span class="badge badge-success">Paid</span>';
                } else {
                    return '<span class="badge badge-warning">Realization</span>';
                }
            })
            ->editColumn('aging', function ($payreq) {
                $now = new \DateTime();
                $outgoing_date = new \DateTime($payreq->outgoing_date);
                $interval = $now->diff($outgoing_date);
                return $interval->days;
            })
            ->rawColumns(['nomor', 'status'])
            ->make(true);
    }
}
