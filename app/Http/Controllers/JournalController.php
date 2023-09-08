<?php

namespace App\Http\Controllers;

use App\Models\GeneralLedger;
use Illuminate\Http\Request;

class JournalController extends Controller
{
    public function index()
    {
        return view('journals.index');
    }

    public function data()
    {
        // get user's roles
        $userRoles = app(UserController::class)->getUserRoles();

        if (in_array('superadmin', $userRoles) || in_array('admin', $userRoles)) {
            $gls = GeneralLedger::orderBy('posting_date', 'desc')
                ->get();
        } else {
            $gls = GeneralLedger::where('project', auth()->user()->project)
                ->orderBy('posting_date', 'desc')
                ->get();
        }

        return datatables()->of($gls)
            ->editColumn('posting_date', function ($gl) {
                return date('d-M-Y', strtotime($gl->posting_date));
            })
            ->addColumn('account', function ($gl) {
                return $gl->account->account_number . ' - ' . $gl->account->account_name;
            })
            ->editColumn('debit', function ($gl) {
                return number_format($gl->debit, 2, ',', '.');
            })
            ->editColumn('credit', function ($gl) {
                return number_format($gl->credit, 2, ',', '.');
            })
            // ->addColumn('action', 'user-payreqs.action')
            // ->rawColumns(['action', 'nomor', 'status'])
            ->addIndexColumn()
            ->toJson();
    }
}
