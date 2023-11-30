<?php

namespace App\Http\Controllers;

use App\Models\Payreq;
use Illuminate\Http\Request;

class UserOverdueController extends Controller
{
    public function index()
    {
        return view('templates.user-overdue');
    }

    public function data()
    {
        $payreqs = Payreq::all();

        return datatables()->of($payreqs)
            ->addIndexColumn()
            ->addColumn('action', 'users-overdue.action')
            ->rawColumns(['action'])
            ->toJson();
    }
}
