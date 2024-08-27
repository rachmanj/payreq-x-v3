<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Payreq;
use Illuminate\Http\Request;
use App\Http\Controllers\ToolController; // Import the missing class
use App\Http\Controllers\UserController;

class OngoingController extends Controller
{
    public function index()
    {
        $status_include = ['paid', 'realization'];
        $userRoles = app(UserController::class)->getUserRoles();

        if (array_intersect(['superadmin', 'admin', 'cashier'], $userRoles)) {
            $project_include = ['000H', 'APS'];
        } else {
            $project_include = explode(',', auth()->user()->project);
        }

        $total_amount = Payreq::whereIn('status', $status_include)
            ->whereIn('project', $project_include)
            ->sum('amount');

        return view('reports.ongoing.index', compact(['total_amount', 'project_include']));
    }

    public function project_index($int)
    {
        return $int;
    }

    public function data()
    {
        $status_include = ['paid', 'realization'];
        $userRoles = app(UserController::class)->getUserRoles();

        if (array_intersect(['superadmin', 'admin', 'cashier'], $userRoles)) {
            $project_include = ['000H', 'APS'];
        } else {
            $project_include = explode(',', auth()->user()->project);
        }

        $payreqs = Payreq::whereIn('status', $status_include)
            ->whereIn('project', $project_include)
            // ->orderBy('outgoing_date', 'asc') // Order by outgoing_date in ascending order
            ->get();

        return datatables()->of($payreqs)
            ->addColumn('employee', function ($payreq) {
                return $payreq->requestor->name;
            })
            ->addColumn('outgoing_date', function ($payreq) {
                return app(ToolController::class)->getLastOutgoing($payreq->id)->created_at->format('d-M-Y');
            })
            ->editColumn('nomor', function ($payreq) {
                if ($payreq->rab_id != null)
                    return '<a href="#" style="color: black" title="' . $payreq->remarks . ' | RAB No.' . $payreq->rab->rab_no . '">' . $payreq->nomor . '</a>';
                else
                    return '<a href="#" style="color: black" title="' . $payreq->remarks . '">' . $payreq->nomor . '</a>';
            })
            ->addColumn('days', function ($payreq) {
                $paid_date = app(ToolController::class)->getLastOutgoing($payreq->id)->created_at;
                $now = now();
                return $paid_date->diffInDays($now);
            })
            ->editColumn('amount', function ($payreq) {
                return number_format($payreq->amount, 0, ',', '.');
            })
            ->addIndexColumn()
            ->rawColumns(['nomor'])
            ->toJson();
    }

    public function payreq_list($int)
    {
        $status_include = ['paid', 'realization'];

        switch ($int) {
            case 1:
                $project_include = ['000H', 'APS'];
                break;
            case 2:
                $project_include = ['017C'];
                break;
            case 3:
                $project_include = ['021C'];
                break;
            case 4:
                $project_include = ['022C'];
                break;
            case 5:
                $project_include = ['023C'];
                break;
        }

        $payreqs = Payreq::whereIn('status', $status_include)
            ->whereIn('project', $project_include)
            ->get();

        return $payreqs;
    }
}
